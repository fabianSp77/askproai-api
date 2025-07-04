"""
AskProAI ML Sentiment Analyzer
Analyzes call transcripts and predicts sentiment with missing data handling
"""

import json
import logging
import re
from typing import Dict, List, Optional, Tuple
import numpy as np
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler
from sklearn.pipeline import Pipeline
from sklearn.impute import SimpleImputer
from sklearn.compose import ColumnTransformer
import spacy
from textblob_de import TextBlobDE
import joblib
from datetime import datetime

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class SentimentAnalyzer:
    """ML-based sentiment analyzer for German call transcripts"""
    
    def __init__(self, model_path: Optional[str] = None):
        self.model_path = model_path
        self.model = None
        self.vectorizer = None
        self.feature_pipeline = None
        self.nlp = None
        self._load_nlp_model()
        
        if model_path:
            self.load_model(model_path)
    
    def _load_nlp_model(self):
        """Load German spaCy model"""
        try:
            self.nlp = spacy.load("de_core_news_sm")
        except:
            logger.warning("German spaCy model not found. Install with: python -m spacy download de_core_news_sm")
            self.nlp = None
    
    def extract_features(self, call_data: Dict) -> Dict:
        """Extract features from call data with missing data handling"""
        features = {}
        
        # Text features (if transcript available)
        transcript = call_data.get('transcript', '')
        if transcript:
            features.update(self._extract_text_features(transcript))
        else:
            # Use default values for missing transcript
            features.update({
                'word_count': 0,
                'sentence_count': 0,
                'positive_word_ratio': 0.0,
                'negative_word_ratio': 0.0,
                'question_count': 0,
                'exclamation_count': 0,
                'avg_sentence_length': 0.0,
                'text_blob_polarity': 0.0,
            })
        
        # Metadata features (always available)
        features.update(self._extract_metadata_features(call_data))
        
        # Call outcome features
        features.update(self._extract_outcome_features(call_data))
        
        return features
    
    def _extract_text_features(self, transcript: str) -> Dict:
        """Extract features from transcript text"""
        features = {}
        
        # Basic text statistics
        words = transcript.split()
        sentences = [s.strip() for s in re.split(r'[.!?]+', transcript) if s.strip()]
        
        features['word_count'] = len(words)
        features['sentence_count'] = len(sentences)
        features['avg_sentence_length'] = len(words) / max(len(sentences), 1)
        
        # Sentiment keywords
        positive_words = ['danke', 'super', 'toll', 'perfekt', 'gut', 'gerne', 
                         'freue', 'klasse', 'wunderbar', 'ja', 'prima', 'schön']
        negative_words = ['problem', 'schlecht', 'nein', 'nicht', 'leider', 
                         'schwierig', 'ärger', 'beschwerde', 'unzufrieden', 'falsch']
        
        transcript_lower = transcript.lower()
        positive_count = sum(transcript_lower.count(word) for word in positive_words)
        negative_count = sum(transcript_lower.count(word) for word in negative_words)
        
        features['positive_word_ratio'] = positive_count / max(len(words), 1)
        features['negative_word_ratio'] = negative_count / max(len(words), 1)
        
        # Question and exclamation marks
        features['question_count'] = transcript.count('?')
        features['exclamation_count'] = transcript.count('!')
        
        # TextBlob sentiment (German)
        try:
            blob = TextBlobDE(transcript)
            features['text_blob_polarity'] = blob.sentiment.polarity
        except:
            features['text_blob_polarity'] = 0.0
        
        # Advanced NLP features if spaCy available
        if self.nlp:
            doc = self.nlp(transcript)
            features['entity_count'] = len(doc.ents)
            features['noun_ratio'] = len([t for t in doc if t.pos_ == 'NOUN']) / max(len(doc), 1)
            features['verb_ratio'] = len([t for t in doc if t.pos_ == 'VERB']) / max(len(doc), 1)
        else:
            features['entity_count'] = 0
            features['noun_ratio'] = 0.0
            features['verb_ratio'] = 0.0
        
        return features
    
    def _extract_metadata_features(self, call_data: Dict) -> Dict:
        """Extract features from call metadata"""
        features = {}
        
        # Duration features
        duration = call_data.get('duration_sec', 0)
        features['duration_seconds'] = duration
        features['duration_minutes'] = duration / 60.0
        features['is_long_call'] = 1 if duration > 300 else 0  # > 5 minutes
        features['is_short_call'] = 1 if duration < 60 else 0   # < 1 minute
        
        # Time features
        start_time = call_data.get('start_timestamp')
        if start_time:
            if isinstance(start_time, str):
                try:
                    dt = datetime.fromisoformat(start_time.replace('Z', '+00:00'))
                except:
                    dt = datetime.now()
            else:
                dt = start_time
            
            features['hour_of_day'] = dt.hour
            features['day_of_week'] = dt.weekday()
            features['is_business_hours'] = 1 if 8 <= dt.hour < 18 else 0
            features['is_weekend'] = 1 if dt.weekday() >= 5 else 0
        else:
            features['hour_of_day'] = 12  # Default to noon
            features['day_of_week'] = 1   # Default to Tuesday
            features['is_business_hours'] = 1
            features['is_weekend'] = 0
        
        # Cost features
        cost = call_data.get('cost', 0)
        features['call_cost'] = float(cost) if cost else 0.0
        features['is_expensive_call'] = 1 if cost and float(cost) > 5.0 else 0
        
        return features
    
    def _extract_outcome_features(self, call_data: Dict) -> Dict:
        """Extract features related to call outcome"""
        features = {}
        
        # Appointment booking
        features['has_appointment'] = 1 if call_data.get('appointment_id') else 0
        
        # Customer information
        features['has_customer'] = 1 if call_data.get('customer_id') else 0
        features['is_repeat_customer'] = call_data.get('is_repeat_customer', 0)
        
        # Call success
        features['call_successful'] = call_data.get('call_successful', 0)
        
        # Disconnection reason
        disconnection = call_data.get('disconnection_reason', '')
        features['normal_hangup'] = 1 if disconnection in ['agent_hangup', 'user_hangup'] else 0
        features['abnormal_disconnect'] = 1 if disconnection and disconnection not in ['agent_hangup', 'user_hangup', ''] else 0
        
        return features
    
    def analyze_sentiment(self, call_data: Dict) -> Dict:
        """Analyze sentiment for a single call"""
        # Extract features
        features = self.extract_features(call_data)
        
        # Convert to DataFrame for model prediction
        features_df = pd.DataFrame([features])
        
        # Make prediction
        if self.model and self.feature_pipeline:
            try:
                features_transformed = self.feature_pipeline.transform(features_df)
                sentiment_score = self.model.predict_proba(features_transformed)[0]
                
                # Get sentiment label
                sentiment_class = self.model.predict(features_transformed)[0]
                sentiment_label = ['negative', 'neutral', 'positive'][sentiment_class]
                
                # Calculate confidence
                confidence = max(sentiment_score)
                
                result = {
                    'sentiment': sentiment_label,
                    'sentiment_score': float(sentiment_score[2] - sentiment_score[0]),  # Range -1 to 1
                    'confidence': float(confidence),
                    'positive_probability': float(sentiment_score[2]),
                    'neutral_probability': float(sentiment_score[1]),
                    'negative_probability': float(sentiment_score[0]),
                    'features': features
                }
            except Exception as e:
                logger.error(f"Model prediction failed: {e}")
                result = self._fallback_sentiment_analysis(call_data, features)
        else:
            # Use rule-based analysis as fallback
            result = self._fallback_sentiment_analysis(call_data, features)
        
        # Add sentence-level analysis if transcript available
        if call_data.get('transcript'):
            result['sentence_sentiments'] = self.analyze_sentences(call_data.get('transcript', ''))
        
        return result
    
    def _fallback_sentiment_analysis(self, call_data: Dict, features: Dict) -> Dict:
        """Rule-based sentiment analysis as fallback"""
        # Simple rule-based sentiment
        positive_score = features.get('positive_word_ratio', 0) * 10
        negative_score = features.get('negative_word_ratio', 0) * 10
        
        # Boost scores based on outcomes
        if features.get('has_appointment'):
            positive_score += 0.3
        if features.get('call_successful'):
            positive_score += 0.2
        if features.get('abnormal_disconnect'):
            negative_score += 0.3
        
        # Normalize scores
        total_score = positive_score + negative_score
        if total_score > 0:
            positive_prob = positive_score / total_score
            negative_prob = negative_score / total_score
        else:
            positive_prob = 0.5
            negative_prob = 0.5
        
        # Determine sentiment
        if positive_prob > 0.6:
            sentiment = 'positive'
        elif negative_prob > 0.6:
            sentiment = 'negative'
        else:
            sentiment = 'neutral'
        
        return {
            'sentiment': sentiment,
            'sentiment_score': positive_prob - negative_prob,
            'confidence': 0.5,  # Low confidence for rule-based
            'positive_probability': positive_prob,
            'neutral_probability': 0.0,
            'negative_probability': negative_prob,
            'features': features,
            'method': 'rule_based'
        }
    
    def analyze_sentences(self, transcript: str) -> List[Dict]:
        """Analyze sentiment for each sentence"""
        sentences = [s.strip() for s in re.split(r'[.!?]+', transcript) if s.strip()]
        sentence_sentiments = []
        
        for i, sentence in enumerate(sentences):
            try:
                blob = TextBlobDE(sentence)
                polarity = blob.sentiment.polarity
                
                # Map polarity to sentiment
                if polarity > 0.3:
                    sentiment = 'positive'
                elif polarity < -0.3:
                    sentiment = 'negative'
                else:
                    sentiment = 'neutral'
                
                sentence_sentiments.append({
                    'text': sentence,
                    'sentiment': sentiment,
                    'score': float(polarity),
                    'index': i
                })
            except:
                # Fallback to keyword-based
                positive_words = ['danke', 'super', 'gut', 'gerne', 'ja']
                negative_words = ['problem', 'nein', 'nicht', 'leider']
                
                sentence_lower = sentence.lower()
                pos_count = sum(word in sentence_lower for word in positive_words)
                neg_count = sum(word in sentence_lower for word in negative_words)
                
                if pos_count > neg_count:
                    sentiment = 'positive'
                    score = 0.5
                elif neg_count > pos_count:
                    sentiment = 'negative'
                    score = -0.5
                else:
                    sentiment = 'neutral'
                    score = 0.0
                
                sentence_sentiments.append({
                    'text': sentence,
                    'sentiment': sentiment,
                    'score': score,
                    'index': i
                })
        
        return sentence_sentiments
    
    def train_model(self, training_data: pd.DataFrame, target_column: str = 'sentiment'):
        """Train the ML model with the provided data"""
        # Prepare features and target
        feature_columns = [col for col in training_data.columns if col != target_column]
        X = training_data[feature_columns]
        y = training_data[target_column]
        
        # Convert sentiment labels to numeric
        sentiment_map = {'negative': 0, 'neutral': 1, 'positive': 2}
        y_numeric = y.map(sentiment_map)
        
        # Create feature pipeline
        numeric_features = X.columns.tolist()
        numeric_transformer = Pipeline(steps=[
            ('imputer', SimpleImputer(strategy='median')),
            ('scaler', StandardScaler())
        ])
        
        self.feature_pipeline = ColumnTransformer(
            transformers=[
                ('num', numeric_transformer, numeric_features)
            ])
        
        # Create and train model
        self.model = RandomForestClassifier(
            n_estimators=100,
            max_depth=10,
            min_samples_split=5,
            min_samples_leaf=2,
            class_weight='balanced',
            random_state=42
        )
        
        # Fit pipeline and model
        X_transformed = self.feature_pipeline.fit_transform(X)
        self.model.fit(X_transformed, y_numeric)
        
        logger.info("Model training completed")
        
        return self
    
    def save_model(self, path: str):
        """Save the trained model and preprocessors"""
        model_data = {
            'model': self.model,
            'feature_pipeline': self.feature_pipeline,
            'version': '1.0.0',
            'trained_at': datetime.now().isoformat()
        }
        joblib.dump(model_data, path)
        logger.info(f"Model saved to {path}")
    
    def load_model(self, path: str):
        """Load a trained model and preprocessors"""
        try:
            model_data = joblib.load(path)
            self.model = model_data['model']
            self.feature_pipeline = model_data['feature_pipeline']
            logger.info(f"Model loaded from {path}")
        except Exception as e:
            logger.error(f"Failed to load model: {e}")
            raise