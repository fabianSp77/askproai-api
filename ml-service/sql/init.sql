-- ML Service Database Schema
-- PostgreSQL initialization script

-- Models table for versioning and metadata
CREATE TABLE IF NOT EXISTS ml_models (
    id SERIAL PRIMARY KEY,
    model_type VARCHAR(50) NOT NULL,
    version VARCHAR(50) NOT NULL,
    path TEXT,
    config JSONB,
    metrics JSONB,
    training_data_start DATE,
    training_data_end DATE,
    training_duration_seconds INTEGER,
    is_active BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(model_type, version)
);

-- Predictions log for auditing and analysis
CREATE TABLE IF NOT EXISTS predictions_log (
    id SERIAL PRIMARY KEY,
    job_id VARCHAR(100) UNIQUE,
    tenant_id VARCHAR(100) NOT NULL,
    prediction_type VARCHAR(50) NOT NULL,
    features JSONB NOT NULL,
    prediction JSONB,
    confidence FLOAT,
    model_version VARCHAR(50),
    processing_time_ms INTEGER,
    status VARCHAR(20) DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    completed_at TIMESTAMP
);

-- Training jobs table
CREATE TABLE IF NOT EXISTS training_jobs (
    id SERIAL PRIMARY KEY,
    job_id VARCHAR(100) UNIQUE,
    model_type VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'queued',
    hyperparameters JSONB,
    training_metrics JSONB,
    validation_metrics JSONB,
    feature_importance JSONB,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Model drift monitoring
CREATE TABLE IF NOT EXISTS model_drift (
    id SERIAL PRIMARY KEY,
    model_type VARCHAR(50) NOT NULL,
    model_version VARCHAR(50) NOT NULL,
    drift_score FLOAT NOT NULL,
    psi_score FLOAT,
    feature_drift JSONB,
    detected_at TIMESTAMP DEFAULT NOW(),
    retraining_triggered BOOLEAN DEFAULT false
);

-- Feature store for training data
CREATE TABLE IF NOT EXISTS feature_store (
    id SERIAL PRIMARY KEY,
    tenant_id VARCHAR(100) NOT NULL,
    timestamp TIMESTAMP NOT NULL,
    features JSONB NOT NULL,
    target_value FLOAT,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(tenant_id, timestamp)
);

-- Anomaly detections
CREATE TABLE IF NOT EXISTS anomaly_detections (
    id SERIAL PRIMARY KEY,
    tenant_id VARCHAR(100) NOT NULL,
    detection_type VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL,
    z_score FLOAT,
    expected_range JSONB,
    actual_value FLOAT,
    metadata JSONB,
    detected_at TIMESTAMP DEFAULT NOW()
);

-- Create indexes for performance
CREATE INDEX idx_predictions_tenant ON predictions_log(tenant_id);
CREATE INDEX idx_predictions_created ON predictions_log(created_at);
CREATE INDEX idx_predictions_status ON predictions_log(status);
CREATE INDEX idx_training_status ON training_jobs(status);
CREATE INDEX idx_feature_store_tenant ON feature_store(tenant_id);
CREATE INDEX idx_feature_store_timestamp ON feature_store(timestamp);
CREATE INDEX idx_anomaly_tenant ON anomaly_detections(tenant_id);
CREATE INDEX idx_anomaly_detected ON anomaly_detections(detected_at);
CREATE INDEX idx_drift_detected ON model_drift(detected_at);

-- Create function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create trigger for ml_models
CREATE TRIGGER update_ml_models_updated_at BEFORE UPDATE
    ON ml_models FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert default model configurations
INSERT INTO ml_models (model_type, version, config, is_active) VALUES
('usage_predictor', 'v1.0.0', '{"algorithm": "ensemble", "features": 30}', true),
('fraud_detector', 'v1.0.0', '{"algorithm": "isolation_forest", "threshold": 0.1}', true),
('churn_predictor', 'v1.0.0', '{"algorithm": "gradient_boosting", "features": 15}', true)
ON CONFLICT (model_type, version) DO NOTHING;