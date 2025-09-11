from fastapi import FastAPI, HTTPException, BackgroundTasks, Depends
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
from pydantic import BaseModel, Field
from typing import Dict, List, Optional, Any
import asyncio
import redis.asyncio as aioredis
import asyncpg
import json
import time
import logging
from datetime import datetime, timedelta
import os
from prometheus_client import Counter, Histogram, Gauge, generate_latest
from fastapi.responses import PlainTextResponse

# Configure logging
logging.basicConfig(
    level=os.getenv('LOG_LEVEL', 'INFO'),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Metrics
prediction_counter = Counter('ml_predictions_total', 'Total predictions made', ['model_type', 'status'])
prediction_duration = Histogram('ml_prediction_duration_seconds', 'Prediction duration', ['model_type'])
model_accuracy = Gauge('ml_model_accuracy', 'Current model accuracy', ['model_type'])
active_models = Gauge('ml_active_models', 'Number of active models')

# Global connections
redis_pool = None
postgres_pool = None

# Request/Response Models
class PredictionRequest(BaseModel):
    tenant_id: str = Field(..., description="Tenant identifier")
    prediction_type: str = Field(..., description="Type of prediction: usage, fraud, churn")
    features: Dict[str, Any] = Field(..., description="Feature dictionary for prediction")
    async_mode: bool = Field(default=True, description="Process asynchronously")
    
class PredictionResponse(BaseModel):
    job_id: str = Field(..., description="Job identifier for async tracking")
    status: str = Field(..., description="Job status: queued, processing, completed, failed")
    prediction: Optional[Dict[str, Any]] = Field(None, description="Prediction results if available")
    confidence: Optional[float] = Field(None, description="Prediction confidence score")
    model_version: Optional[str] = Field(None, description="Model version used")
    
class TrainingRequest(BaseModel):
    model_type: str = Field(..., description="Model type to train")
    start_date: str = Field(..., description="Training data start date")
    end_date: str = Field(..., description="Training data end date")
    hyperparameters: Optional[Dict[str, Any]] = Field(default={}, description="Model hyperparameters")
    
class HealthResponse(BaseModel):
    status: str
    timestamp: str
    models: Dict[str, Any]
    redis: str
    postgres: str
    memory_usage_mb: float

@asynccontextmanager
async def lifespan(app: FastAPI):
    """Manage application lifecycle"""
    global redis_pool, postgres_pool
    
    # Startup
    logger.info("Starting ML Service...")
    
    # Initialize Redis connection
    redis_pool = aioredis.from_url(
        os.getenv('REDIS_URL', 'redis://localhost:6379'),
        encoding='utf-8',
        decode_responses=True
    )
    
    # Initialize PostgreSQL connection
    postgres_pool = await asyncpg.create_pool(
        os.getenv('DATABASE_URL', 'postgresql://ml_user:password@localhost:5432/ml_predictions'),
        min_size=10,
        max_size=20
    )
    
    # Load models
    await load_models()
    
    # Start background tasks
    asyncio.create_task(model_drift_monitor())
    asyncio.create_task(process_prediction_queue())
    
    logger.info("ML Service started successfully")
    
    yield
    
    # Shutdown
    logger.info("Shutting down ML Service...")
    
    if redis_pool:
        await redis_pool.close()
    
    if postgres_pool:
        await postgres_pool.close()
    
    logger.info("ML Service shutdown complete")

# Create FastAPI app
app = FastAPI(
    title="AskPro AI ML Service",
    description="Machine Learning service for billing predictions and fraud detection",
    version="1.0.0",
    lifespan=lifespan
)

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost", "https://api.askproai.de"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Models storage
models = {}

async def load_models():
    """Load ML models from storage"""
    global models
    
    try:
        # Load from PostgreSQL or filesystem
        models['usage_predictor'] = await load_usage_predictor()
        models['fraud_detector'] = await load_fraud_detector()
        models['churn_predictor'] = await load_churn_predictor()
        
        active_models.set(len(models))
        logger.info(f"Loaded {len(models)} models")
        
    except Exception as e:
        logger.error(f"Failed to load models: {e}")
        raise

async def load_usage_predictor():
    """Load usage prediction model"""
    # Placeholder - would load actual model
    return {
        'version': '1.0.0',
        'loaded_at': datetime.utcnow().isoformat(),
        'type': 'RandomForestRegressor',
        'features': ['hour', 'day_of_week', 'usage_lag_7d', 'usage_mean_30d']
    }

async def load_fraud_detector():
    """Load fraud detection model"""
    return {
        'version': '1.0.0',
        'loaded_at': datetime.utcnow().isoformat(),
        'type': 'IsolationForest',
        'threshold': 0.1
    }

async def load_churn_predictor():
    """Load churn prediction model"""
    return {
        'version': '1.0.0',
        'loaded_at': datetime.utcnow().isoformat(),
        'type': 'GradientBoostingClassifier',
        'features': ['days_since_last_use', 'usage_trend', 'payment_failures']
    }

@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    import psutil
    
    # Check Redis
    redis_status = "healthy"
    try:
        await redis_pool.ping()
    except:
        redis_status = "unhealthy"
    
    # Check PostgreSQL
    postgres_status = "healthy"
    try:
        async with postgres_pool.acquire() as conn:
            await conn.fetchval("SELECT 1")
    except:
        postgres_status = "unhealthy"
    
    # Get memory usage
    process = psutil.Process()
    memory_mb = process.memory_info().rss / 1024 / 1024
    
    return HealthResponse(
        status="healthy" if redis_status == "healthy" and postgres_status == "healthy" else "degraded",
        timestamp=datetime.utcnow().isoformat(),
        models={name: model.get('version', 'unknown') for name, model in models.items()},
        redis=redis_status,
        postgres=postgres_status,
        memory_usage_mb=round(memory_mb, 2)
    )

@app.post("/predict", response_model=PredictionResponse)
async def predict(request: PredictionRequest, background_tasks: BackgroundTasks):
    """Make a prediction"""
    
    # Validate model type
    if request.prediction_type not in models:
        raise HTTPException(status_code=400, detail=f"Unknown prediction type: {request.prediction_type}")
    
    # Generate job ID
    job_id = f"pred_{request.tenant_id}_{int(time.time() * 1000)}"
    
    if request.async_mode:
        # Queue for async processing
        await queue_prediction(job_id, request)
        
        # Add background task
        background_tasks.add_task(process_single_prediction, job_id, request)
        
        return PredictionResponse(
            job_id=job_id,
            status="queued",
            model_version=models[request.prediction_type].get('version')
        )
    else:
        # Process synchronously
        with prediction_duration.labels(model_type=request.prediction_type).time():
            result = await make_prediction(request)
        
        return PredictionResponse(
            job_id=job_id,
            status="completed",
            prediction=result['prediction'],
            confidence=result['confidence'],
            model_version=models[request.prediction_type].get('version')
        )

async def queue_prediction(job_id: str, request: PredictionRequest):
    """Queue prediction for processing"""
    await redis_pool.set(
        f"prediction:queue:{job_id}",
        json.dumps({
            'tenant_id': request.tenant_id,
            'prediction_type': request.prediction_type,
            'features': request.features,
            'queued_at': time.time()
        }),
        ex=300  # 5 minute TTL
    )
    
    # Add to processing queue
    await redis_pool.lpush('prediction_queue', job_id)

async def make_prediction(request: PredictionRequest) -> Dict[str, Any]:
    """Make actual prediction"""
    
    # Simulate prediction based on type
    if request.prediction_type == 'usage':
        prediction = await predict_usage(request.features)
    elif request.prediction_type == 'fraud':
        prediction = await detect_fraud(request.features)
    elif request.prediction_type == 'churn':
        prediction = await predict_churn(request.features)
    else:
        raise ValueError(f"Unknown prediction type: {request.prediction_type}")
    
    # Update metrics
    prediction_counter.labels(
        model_type=request.prediction_type,
        status='success'
    ).inc()
    
    return prediction

async def predict_usage(features: Dict[str, Any]) -> Dict[str, Any]:
    """Predict usage for next period"""
    
    # Simulate prediction logic
    # In production, this would use the actual trained model
    import random
    
    base_usage = features.get('usage_mean_30d', 100)
    hour = features.get('hour', 12)
    day_of_week = features.get('day_of_week', 1)
    
    # Simple heuristic for demo
    if 9 <= hour <= 17 and day_of_week < 5:  # Business hours on weekday
        multiplier = 1.5
    else:
        multiplier = 0.7
    
    predicted_usage = base_usage * multiplier + random.uniform(-10, 10)
    confidence = 0.85 + random.uniform(-0.1, 0.1)
    
    return {
        'prediction': {
            'usage_next_hour': round(predicted_usage, 2),
            'usage_next_day': round(predicted_usage * 24, 2),
            'usage_next_week': round(predicted_usage * 24 * 7, 2)
        },
        'confidence': round(confidence, 3),
        'factors': {
            'time_of_day': 'peak' if 9 <= hour <= 17 else 'off-peak',
            'day_type': 'weekday' if day_of_week < 5 else 'weekend',
            'trend': 'increasing' if random.random() > 0.5 else 'stable'
        }
    }

async def detect_fraud(features: Dict[str, Any]) -> Dict[str, Any]:
    """Detect potential fraud"""
    
    import random
    
    amount = features.get('amount', 0)
    hour = features.get('time', 12)
    
    # Simple fraud detection heuristic
    risk_score = 0.1  # Base risk
    
    if amount > 50000:  # Over 500€
        risk_score += 0.3
    
    if hour < 6 or hour > 22:  # Unusual hours
        risk_score += 0.2
    
    # Add some randomness for demo
    risk_score += random.uniform(-0.05, 0.05)
    risk_score = min(max(risk_score, 0), 1)  # Clamp to [0, 1]
    
    is_fraud = risk_score > 0.7
    
    return {
        'prediction': {
            'is_fraud': is_fraud,
            'risk_score': round(risk_score, 3),
            'risk_level': 'high' if risk_score > 0.7 else 'medium' if risk_score > 0.4 else 'low'
        },
        'confidence': 0.9 if is_fraud else 0.95,
        'factors': {
            'amount_unusual': amount > 50000,
            'time_unusual': hour < 6 or hour > 22,
            'pattern_match': False
        }
    }

async def predict_churn(features: Dict[str, Any]) -> Dict[str, Any]:
    """Predict customer churn probability"""
    
    import random
    
    days_inactive = features.get('days_since_last_use', 0)
    payment_failures = features.get('payment_failures', 0)
    
    # Simple churn prediction
    churn_probability = 0.1  # Base probability
    
    if days_inactive > 30:
        churn_probability += 0.3
    if days_inactive > 60:
        churn_probability += 0.3
    if payment_failures > 2:
        churn_probability += 0.2
    
    churn_probability = min(churn_probability, 0.95)
    
    return {
        'prediction': {
            'will_churn': churn_probability > 0.5,
            'churn_probability': round(churn_probability, 3),
            'retention_score': round(1 - churn_probability, 3)
        },
        'confidence': 0.8,
        'recommendations': [
            'Send retention offer' if churn_probability > 0.7 else None,
            'Schedule follow-up' if days_inactive > 30 else None,
            'Review payment issues' if payment_failures > 0 else None
        ]
    }

@app.get("/predict/{job_id}")
async def get_prediction_status(job_id: str):
    """Get prediction job status"""
    
    # Check if result exists
    result = await redis_pool.get(f"prediction:result:{job_id}")
    
    if result:
        data = json.loads(result)
        return PredictionResponse(
            job_id=job_id,
            status="completed",
            prediction=data.get('prediction'),
            confidence=data.get('confidence'),
            model_version=data.get('model_version')
        )
    
    # Check if still in queue
    queued = await redis_pool.get(f"prediction:queue:{job_id}")
    
    if queued:
        return PredictionResponse(
            job_id=job_id,
            status="processing",
            model_version=None
        )
    
    # Not found
    raise HTTPException(status_code=404, detail="Prediction job not found")

async def process_single_prediction(job_id: str, request: PredictionRequest):
    """Process a single prediction job"""
    
    try:
        # Make prediction
        result = await make_prediction(request)
        
        # Store result
        await redis_pool.set(
            f"prediction:result:{job_id}",
            json.dumps({
                'prediction': result['prediction'],
                'confidence': result['confidence'],
                'model_version': models[request.prediction_type].get('version'),
                'completed_at': time.time()
            }),
            ex=3600  # 1 hour TTL
        )
        
        # Clean up queue entry
        await redis_pool.delete(f"prediction:queue:{job_id}")
        
        logger.info(f"Completed prediction {job_id}")
        
    except Exception as e:
        logger.error(f"Failed to process prediction {job_id}: {e}")
        
        # Store error
        await redis_pool.set(
            f"prediction:error:{job_id}",
            str(e),
            ex=3600
        )

async def process_prediction_queue():
    """Background task to process prediction queue"""
    
    while True:
        try:
            # Get job from queue
            result = await redis_pool.brpop('prediction_queue', timeout=1)
            
            if result:
                job_id = result[1]  # brpop returns (key, value)
                
                # Get job data
                job_data = await redis_pool.get(f"prediction:queue:{job_id}")
                
                if job_data:
                    data = json.loads(job_data)
                    request = PredictionRequest(
                        tenant_id=data['tenant_id'],
                        prediction_type=data['prediction_type'],
                        features=data['features'],
                        async_mode=False
                    )
                    
                    await process_single_prediction(job_id, request)
            
            await asyncio.sleep(0.1)
            
        except Exception as e:
            logger.error(f"Queue processing error: {e}")
            await asyncio.sleep(5)

async def model_drift_monitor():
    """Monitor model drift and trigger retraining"""
    
    while True:
        try:
            # Check model performance every hour
            await asyncio.sleep(3600)
            
            for model_name, model in models.items():
                # Check drift (simplified)
                drift_score = await calculate_drift(model_name)
                
                if drift_score > float(os.getenv('DRIFT_CHECK_THRESHOLD', '0.15')):
                    logger.warning(f"Model drift detected for {model_name}: {drift_score}")
                    
                    # Trigger retraining
                    await trigger_retraining(model_name)
            
        except Exception as e:
            logger.error(f"Drift monitoring error: {e}")
            await asyncio.sleep(300)  # Retry in 5 minutes

async def calculate_drift(model_name: str) -> float:
    """Calculate model drift score"""
    # Simplified drift calculation
    # In production, would use PSI, KL divergence, etc.
    import random
    return random.uniform(0, 0.2)

async def trigger_retraining(model_name: str):
    """Trigger model retraining"""
    logger.info(f"Triggering retraining for {model_name}")
    
    # Queue retraining job
    await redis_pool.lpush('retraining_queue', json.dumps({
        'model_name': model_name,
        'triggered_at': time.time(),
        'reason': 'drift_detected'
    }))

@app.post("/train", response_model=Dict[str, Any])
async def train_model(request: TrainingRequest, background_tasks: BackgroundTasks):
    """Trigger model training"""
    
    job_id = f"train_{request.model_type}_{int(time.time())}"
    
    # Queue training job
    background_tasks.add_task(
        run_training,
        job_id,
        request.model_type,
        request.start_date,
        request.end_date,
        request.hyperparameters
    )
    
    return {
        'job_id': job_id,
        'status': 'queued',
        'estimated_duration': '15-30 minutes'
    }

async def run_training(job_id: str, model_type: str, start_date: str, end_date: str, hyperparameters: Dict):
    """Run model training job"""
    
    logger.info(f"Starting training job {job_id}")
    
    try:
        # In production, would:
        # 1. Fetch training data from MySQL
        # 2. Preprocess and engineer features
        # 3. Train model with cross-validation
        # 4. Evaluate and compare with current model
        # 5. Save if performance improves
        
        await asyncio.sleep(10)  # Simulate training
        
        logger.info(f"Completed training job {job_id}")
        
    except Exception as e:
        logger.error(f"Training failed for {job_id}: {e}")

@app.get("/metrics")
async def metrics():
    """Prometheus metrics endpoint"""
    return PlainTextResponse(generate_latest())

@app.get("/models")
async def list_models():
    """List available models"""
    return {
        'models': [
            {
                'name': name,
                'version': model.get('version'),
                'type': model.get('type'),
                'loaded_at': model.get('loaded_at')
            }
            for name, model in models.items()
        ]
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)