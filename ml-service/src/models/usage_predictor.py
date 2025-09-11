import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.model_selection import TimeSeriesSplit, GridSearchCV
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from typing import Dict, List, Tuple, Optional, Any
import joblib
import asyncio
import asyncpg
import logging
from datetime import datetime, timedelta
import json

logger = logging.getLogger(__name__)


class EnhancedUsagePredictor:
    """
    Advanced usage prediction model with ensemble methods and feature engineering.
    Addresses the ML cold start problem with progressive learning.
    """
    
    def __init__(self, model_config: Dict[str, Any] = None):
        self.config = model_config or self._default_config()
        self.models = {}
        self.scalers = {}
        self.feature_importance = {}
        self.last_training = None
        self.performance_metrics = []
        self.is_trained = False
        self.minimum_training_samples = 100  # Address cold start
        
        # Initialize ensemble models
        self._initialize_models()
        
    def _default_config(self) -> Dict[str, Any]:
        """Default model configuration"""
        return {
            'rf_params': {
                'n_estimators': 200,
                'max_depth': 10,
                'min_samples_split': 5,
                'min_samples_leaf': 2,
                'max_features': 'sqrt',
                'random_state': 42
            },
            'gb_params': {
                'n_estimators': 100,
                'learning_rate': 0.1,
                'max_depth': 5,
                'subsample': 0.8,
                'random_state': 42
            },
            'feature_engineering': {
                'lag_periods': [1, 7, 14, 30],
                'rolling_windows': [7, 14, 30],
                'include_seasonality': True,
                'include_holidays': True
            },
            'training': {
                'test_size': 0.2,
                'cv_splits': 5,
                'early_stopping_rounds': 10
            }
        }
    
    def _initialize_models(self):
        """Initialize ensemble models"""
        self.models['rf'] = RandomForestRegressor(**self.config['rf_params'])
        self.models['gb'] = GradientBoostingRegressor(**self.config['gb_params'])
        
        # Initialize scalers for each model
        self.scalers['features'] = StandardScaler()
        self.scalers['target'] = StandardScaler()
    
    async def engineer_features(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Advanced feature engineering for time series prediction.
        Handles cold start by generating synthetic features when data is limited.
        """
        
        # Ensure we have required columns
        required_cols = ['timestamp', 'tenant_id', 'usage']
        if not all(col in df.columns for col in required_cols):
            raise ValueError(f"DataFrame must contain columns: {required_cols}")
        
        # Convert timestamp to datetime if needed
        if not pd.api.types.is_datetime64_any_dtype(df['timestamp']):
            df['timestamp'] = pd.to_datetime(df['timestamp'])
        
        # Sort by timestamp
        df = df.sort_values('timestamp')
        
        # Temporal features
        df['hour'] = df['timestamp'].dt.hour
        df['day_of_week'] = df['timestamp'].dt.dayofweek
        df['day_of_month'] = df['timestamp'].dt.day
        df['week_of_year'] = df['timestamp'].dt.isocalendar().week
        df['month'] = df['timestamp'].dt.month
        df['quarter'] = df['timestamp'].dt.quarter
        df['is_weekend'] = df['day_of_week'].isin([5, 6]).astype(int)
        df['is_business_hours'] = df['hour'].between(9, 17).astype(int)
        
        # Cyclic encoding for temporal features
        df['hour_sin'] = np.sin(2 * np.pi * df['hour'] / 24)
        df['hour_cos'] = np.cos(2 * np.pi * df['hour'] / 24)
        df['day_sin'] = np.sin(2 * np.pi * df['day_of_week'] / 7)
        df['day_cos'] = np.cos(2 * np.pi * df['day_of_week'] / 7)
        df['month_sin'] = np.sin(2 * np.pi * df['month'] / 12)
        df['month_cos'] = np.cos(2 * np.pi * df['month'] / 12)
        
        # Lag features (handle cold start with forward fill)
        for lag in self.config['feature_engineering']['lag_periods']:
            df[f'usage_lag_{lag}d'] = df.groupby('tenant_id')['usage'].shift(lag)
            # Fill missing values for cold start
            df[f'usage_lag_{lag}d'].fillna(df['usage'].mean(), inplace=True)
        
        # Rolling statistics
        for window in self.config['feature_engineering']['rolling_windows']:
            # Mean
            df[f'usage_mean_{window}d'] = df.groupby('tenant_id')['usage'].transform(
                lambda x: x.rolling(window, min_periods=1).mean()
            )
            
            # Standard deviation
            df[f'usage_std_{window}d'] = df.groupby('tenant_id')['usage'].transform(
                lambda x: x.rolling(window, min_periods=1).std()
            ).fillna(0)
            
            # Min and Max
            df[f'usage_min_{window}d'] = df.groupby('tenant_id')['usage'].transform(
                lambda x: x.rolling(window, min_periods=1).min()
            )
            df[f'usage_max_{window}d'] = df.groupby('tenant_id')['usage'].transform(
                lambda x: x.rolling(window, min_periods=1).max()
            )
        
        # Growth rate features
        df['usage_growth_7d'] = (
            df['usage_mean_7d'] / df['usage_mean_14d'].shift(7) - 1
        ).fillna(0).replace([np.inf, -np.inf], 0)
        
        df['usage_growth_30d'] = (
            df['usage_mean_30d'] / df['usage_mean_30d'].shift(30) - 1
        ).fillna(0).replace([np.inf, -np.inf], 0)
        
        # Seasonality detection
        if self.config['feature_engineering']['include_seasonality']:
            # Daily seasonality
            hourly_avg = df.groupby('hour')['usage'].transform('mean')
            df['hourly_seasonal_index'] = df['usage'] / hourly_avg
            df['hourly_seasonal_index'].fillna(1, inplace=True)
            
            # Weekly seasonality
            daily_avg = df.groupby('day_of_week')['usage'].transform('mean')
            df['daily_seasonal_index'] = df['usage'] / daily_avg
            df['daily_seasonal_index'].fillna(1, inplace=True)
            
            # Monthly seasonality
            monthly_avg = df.groupby('day_of_month')['usage'].transform('mean')
            df['monthly_seasonal_index'] = df['usage'] / monthly_avg
            df['monthly_seasonal_index'].fillna(1, inplace=True)
        
        # Interaction features
        df['hour_weekday_interaction'] = df['hour'] * df['day_of_week']
        df['business_hours_usage_ratio'] = (
            df['usage'] * df['is_business_hours'] / (df['usage'].mean() + 1e-6)
        )
        
        # Tenant-specific features
        tenant_stats = df.groupby('tenant_id')['usage'].agg(['mean', 'std', 'min', 'max'])
        df = df.merge(tenant_stats, left_on='tenant_id', right_index=True, suffixes=('', '_tenant'))
        
        # Handle any remaining NaN values
        df = df.fillna(0)
        
        # Remove infinite values
        df = df.replace([np.inf, -np.inf], 0)
        
        return df
    
    async def train(
        self, 
        training_data: pd.DataFrame,
        validation_data: Optional[pd.DataFrame] = None
    ) -> Dict[str, Any]:
        """
        Train ensemble models with cross-validation and hyperparameter tuning.
        Handles cold start with progressive learning.
        """
        
        logger.info(f"Starting training with {len(training_data)} samples")
        
        # Check if we have enough data (cold start handling)
        if len(training_data) < self.minimum_training_samples:
            logger.warning(f"Insufficient training data ({len(training_data)} < {self.minimum_training_samples})")
            # Use simple heuristics for cold start
            return await self._cold_start_training(training_data)
        
        # Engineer features
        training_data = await self.engineer_features(training_data)
        
        # Prepare features and target
        feature_cols = [col for col in training_data.columns 
                       if col not in ['timestamp', 'tenant_id', 'usage']]
        
        X = training_data[feature_cols]
        y = training_data['usage']
        
        # Scale features
        X_scaled = self.scalers['features'].fit_transform(X)
        y_scaled = self.scalers['target'].fit_transform(y.values.reshape(-1, 1)).ravel()
        
        # Time series cross-validation
        tscv = TimeSeriesSplit(n_splits=self.config['training']['cv_splits'])
        
        # Store scores for each model
        scores = {model_name: {'train': [], 'val': []} for model_name in self.models.keys()}
        
        for fold, (train_idx, val_idx) in enumerate(tscv.split(X_scaled)):
            X_train, X_val = X_scaled[train_idx], X_scaled[val_idx]
            y_train, y_val = y_scaled[train_idx], y_scaled[val_idx]
            
            logger.info(f"Training fold {fold + 1}/{self.config['training']['cv_splits']}")
            
            for model_name, model in self.models.items():
                # Train model
                model.fit(X_train, y_train)
                
                # Evaluate
                train_pred = model.predict(X_train)
                val_pred = model.predict(X_val)
                
                train_score = r2_score(y_train, train_pred)
                val_score = r2_score(y_val, val_pred)
                
                scores[model_name]['train'].append(train_score)
                scores[model_name]['val'].append(val_score)
                
                logger.info(f"  {model_name} - Train R²: {train_score:.4f}, Val R²: {val_score:.4f}")
        
        # Train final models on all data
        for model_name, model in self.models.items():
            model.fit(X_scaled, y_scaled)
            
            # Calculate feature importance
            if hasattr(model, 'feature_importances_'):
                self.feature_importance[model_name] = dict(
                    zip(feature_cols, model.feature_importances_)
                )
        
        # Calculate ensemble performance
        ensemble_pred = self._ensemble_predict(X_scaled)
        ensemble_score = r2_score(y_scaled, ensemble_pred)
        
        # Update training metadata
        self.last_training = datetime.utcnow()
        self.is_trained = True
        
        # Prepare results
        results = {
            'training_completed': self.last_training.isoformat(),
            'samples_trained': len(training_data),
            'features_used': len(feature_cols),
            'cross_validation_scores': {
                model_name: {
                    'train_mean': np.mean(scores[model_name]['train']),
                    'train_std': np.std(scores[model_name]['train']),
                    'val_mean': np.mean(scores[model_name]['val']),
                    'val_std': np.std(scores[model_name]['val'])
                }
                for model_name in scores
            },
            'ensemble_score': ensemble_score,
            'feature_importance': self._get_top_features(10)
        }
        
        logger.info(f"Training completed. Ensemble R²: {ensemble_score:.4f}")
        
        return results
    
    async def _cold_start_training(self, limited_data: pd.DataFrame) -> Dict[str, Any]:
        """
        Handle cold start scenario with limited data.
        Uses simple heuristics and synthetic data generation.
        """
        
        logger.info("Using cold start training strategy")
        
        # Generate synthetic data based on limited samples
        synthetic_data = await self._generate_synthetic_data(limited_data)
        
        # Combine real and synthetic data
        combined_data = pd.concat([limited_data, synthetic_data], ignore_index=True)
        
        # Train with reduced complexity
        self.models['rf'].n_estimators = 50
        self.models['gb'].n_estimators = 30
        
        # Proceed with regular training on combined data
        return await self.train(combined_data)
    
    async def _generate_synthetic_data(self, real_data: pd.DataFrame) -> pd.DataFrame:
        """
        Generate synthetic training data for cold start.
        Uses statistical patterns and domain knowledge.
        """
        
        synthetic_samples = []
        
        # Get basic statistics from real data
        mean_usage = real_data['usage'].mean()
        std_usage = real_data['usage'].std() or mean_usage * 0.2
        
        # Generate synthetic samples for different scenarios
        scenarios = [
            {'hour_range': (9, 17), 'multiplier': 1.5, 'label': 'business_hours'},
            {'hour_range': (18, 23), 'multiplier': 0.8, 'label': 'evening'},
            {'hour_range': (0, 8), 'multiplier': 0.3, 'label': 'night'},
        ]
        
        for scenario in scenarios:
            for hour in range(scenario['hour_range'][0], scenario['hour_range'][1]):
                for day in range(7):
                    synthetic_usage = mean_usage * scenario['multiplier'] + np.random.normal(0, std_usage * 0.5)
                    
                    synthetic_samples.append({
                        'timestamp': datetime.now() - timedelta(days=day, hours=hour),
                        'tenant_id': real_data['tenant_id'].iloc[0] if len(real_data) > 0 else 'synthetic',
                        'usage': max(0, synthetic_usage)
                    })
        
        return pd.DataFrame(synthetic_samples)
    
    async def predict(
        self,
        features: np.ndarray,
        return_uncertainty: bool = True
    ) -> Tuple[float, Optional[float], Optional[Dict[str, Any]]]:
        """
        Make prediction with uncertainty quantification.
        Returns (prediction, confidence, metadata).
        """
        
        if not self.is_trained:
            raise ValueError("Model must be trained before making predictions")
        
        # Scale features
        features_scaled = self.scalers['features'].transform(features.reshape(1, -1))
        
        # Get predictions from all models
        predictions = []
        for model_name, model in self.models.items():
            pred_scaled = model.predict(features_scaled)[0]
            pred = self.scalers['target'].inverse_transform([[pred_scaled]])[0, 0]
            predictions.append(pred)
        
        # Ensemble prediction (weighted average)
        weights = [0.6, 0.4]  # RF gets more weight
        ensemble_prediction = np.average(predictions, weights=weights)
        
        # Calculate uncertainty
        confidence = None
        if return_uncertainty:
            # Use prediction variance as uncertainty measure
            std_prediction = np.std(predictions)
            # Convert to confidence score (inverse of coefficient of variation)
            confidence = 1 / (1 + std_prediction / (ensemble_prediction + 1e-6))
            confidence = min(max(confidence, 0), 1)  # Clamp to [0, 1]
        
        # Prepare metadata
        metadata = {
            'individual_predictions': {
                name: float(pred) 
                for name, pred in zip(self.models.keys(), predictions)
            },
            'prediction_std': float(np.std(predictions)),
            'model_agreement': float(1 - np.std(predictions) / (np.mean(predictions) + 1e-6))
        }
        
        return float(ensemble_prediction), float(confidence) if confidence else None, metadata
    
    def _ensemble_predict(self, X: np.ndarray) -> np.ndarray:
        """
        Make ensemble predictions for multiple samples.
        """
        predictions = []
        
        for model in self.models.values():
            predictions.append(model.predict(X))
        
        # Weighted average
        weights = [0.6, 0.4]
        return np.average(predictions, axis=0, weights=weights)
    
    async def detect_anomalies(
        self,
        tenant_id: str,
        current_usage: float,
        historical_data: pd.DataFrame
    ) -> Dict[str, Any]:
        """
        Real-time anomaly detection using statistical methods and model predictions.
        """
        
        # Get tenant's historical data
        tenant_history = historical_data[historical_data['tenant_id'] == tenant_id]['usage'].values
        
        if len(tenant_history) < 7:  # Need minimum history
            return {
                'anomaly': False,
                'reason': 'insufficient_data',
                'samples_available': len(tenant_history)
            }
        
        # Statistical anomaly detection
        mean_usage = np.mean(tenant_history)
        std_usage = np.std(tenant_history)
        
        # Z-score
        z_score = (current_usage - mean_usage) / (std_usage + 1e-6)
        
        # IQR method
        q1 = np.percentile(tenant_history, 25)
        q3 = np.percentile(tenant_history, 75)
        iqr = q3 - q1
        lower_bound = q1 - 1.5 * iqr
        upper_bound = q3 + 1.5 * iqr
        
        # Determine if anomaly
        is_statistical_anomaly = abs(z_score) > 3 or current_usage < lower_bound or current_usage > upper_bound
        
        # Model-based anomaly detection (if trained)
        is_model_anomaly = False
        prediction_error = None
        
        if self.is_trained:
            try:
                # Prepare features for current timestamp
                current_features = await self._prepare_current_features(tenant_id, historical_data)
                prediction, confidence, _ = await self.predict(current_features, return_uncertainty=True)
                
                # Calculate prediction error
                prediction_error = abs(current_usage - prediction) / (prediction + 1e-6)
                
                # Anomaly if error is too large or confidence is too low
                is_model_anomaly = prediction_error > 0.5 or confidence < 0.5
                
            except Exception as e:
                logger.error(f"Model-based anomaly detection failed: {e}")
        
        # Combine both methods
        is_anomaly = is_statistical_anomaly or is_model_anomaly
        
        return {
            'anomaly': bool(is_anomaly),
            'severity': self._calculate_anomaly_severity(z_score, prediction_error),
            'z_score': float(z_score),
            'expected_range': {
                'min': float(lower_bound),
                'max': float(upper_bound),
                'mean': float(mean_usage),
                'std': float(std_usage)
            },
            'prediction_error': float(prediction_error) if prediction_error else None,
            'detection_methods': {
                'statistical': bool(is_statistical_anomaly),
                'model_based': bool(is_model_anomaly)
            }
        }
    
    def _calculate_anomaly_severity(self, z_score: float, prediction_error: Optional[float]) -> str:
        """
        Calculate anomaly severity based on multiple factors.
        """
        abs_z = abs(z_score)
        
        # Combine z-score and prediction error for severity
        if prediction_error:
            combined_score = (abs_z / 3 + prediction_error) / 2
        else:
            combined_score = abs_z / 3
        
        if combined_score > 2:
            return 'critical'
        elif combined_score > 1.5:
            return 'high'
        elif combined_score > 1:
            return 'medium'
        else:
            return 'low'
    
    async def _prepare_current_features(
        self,
        tenant_id: str,
        historical_data: pd.DataFrame
    ) -> np.ndarray:
        """
        Prepare feature vector for current prediction.
        """
        
        # Create a dummy row for current timestamp
        current_row = pd.DataFrame([{
            'timestamp': datetime.utcnow(),
            'tenant_id': tenant_id,
            'usage': historical_data['usage'].mean()  # Use mean as placeholder
        }])
        
        # Combine with historical data for feature engineering
        combined = pd.concat([historical_data, current_row], ignore_index=True)
        
        # Engineer features
        featured_data = await self.engineer_features(combined)
        
        # Extract features for the last row (current)
        feature_cols = [col for col in featured_data.columns 
                       if col not in ['timestamp', 'tenant_id', 'usage']]
        
        return featured_data[feature_cols].iloc[-1].values
    
    def _get_top_features(self, n: int = 10) -> Dict[str, List[Tuple[str, float]]]:
        """
        Get top n important features for each model.
        """
        top_features = {}
        
        for model_name, importance_dict in self.feature_importance.items():
            # Sort by importance
            sorted_features = sorted(
                importance_dict.items(),
                key=lambda x: x[1],
                reverse=True
            )[:n]
            
            top_features[model_name] = sorted_features
        
        return top_features
    
    async def save_model(self, path: str, postgres_pool: Optional[asyncpg.Pool] = None):
        """
        Save model to disk and optionally to PostgreSQL.
        """
        
        # Save to disk
        model_data = {
            'models': self.models,
            'scalers': self.scalers,
            'config': self.config,
            'feature_importance': self.feature_importance,
            'last_training': self.last_training,
            'is_trained': self.is_trained
        }
        
        joblib.dump(model_data, path)
        logger.info(f"Model saved to {path}")
        
        # Save metadata to PostgreSQL
        if postgres_pool:
            async with postgres_pool.acquire() as conn:
                await conn.execute("""
                    INSERT INTO ml_models (
                        model_type, version, path, 
                        config, metrics, created_at
                    ) VALUES ($1, $2, $3, $4, $5, $6)
                """,
                    'usage_predictor',
                    datetime.utcnow().strftime('%Y%m%d_%H%M%S'),
                    path,
                    json.dumps(self.config),
                    json.dumps(self.performance_metrics[-1] if self.performance_metrics else {}),
                    datetime.utcnow()
                )
                logger.info("Model metadata saved to PostgreSQL")
    
    @classmethod
    async def load_model(cls, path: str) -> 'EnhancedUsagePredictor':
        """
        Load model from disk.
        """
        
        model_data = joblib.load(path)
        
        instance = cls(model_config=model_data['config'])
        instance.models = model_data['models']
        instance.scalers = model_data['scalers']
        instance.feature_importance = model_data['feature_importance']
        instance.last_training = model_data['last_training']
        instance.is_trained = model_data['is_trained']
        
        logger.info(f"Model loaded from {path}")
        
        return instance