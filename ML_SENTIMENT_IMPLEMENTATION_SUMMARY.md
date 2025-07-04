# ML Sentiment Analysis Implementation Summary

## Overview
We have successfully implemented the foundation for a comprehensive ML-based sentiment analysis and visualization system for the AskProAI platform. This system analyzes customer calls, provides sentiment insights, and displays performance metrics.

## What Has Been Implemented

### 1. **Python ML Infrastructure**
- **Location**: `/ml/`
- **Components**:
  - `requirements.txt` - Python dependencies including scikit-learn, spaCy, textblob-de
  - `sentiment_analyzer.py` - Core ML sentiment analysis class with:
    - Feature extraction from transcripts and metadata
    - Missing data handling
    - Rule-based fallback for when ML model unavailable
    - Sentence-level sentiment analysis
  - `analyze_call.py` - Standalone script for Laravel integration

### 2. **Database Schema**
- **Migration**: `2025_06_27_create_ml_sentiment_tables.php`
- **New Tables**:
  - `ml_call_predictions` - Stores ML predictions for each call
  - `agent_performance_metrics` - Daily aggregated performance metrics
  - `ml_models` - Model versioning and management

### 3. **Laravel Models & Services**
- **Models**:
  - `MLCallPrediction.php` - Model for ML predictions with helper methods
  - `AgentPerformanceMetric.php` - Model for agent performance tracking
  - Updated `Call.php` model with mlPrediction relationship

- **Services**:
  - `SentimentAnalysisService.php` - Laravel service that:
    - Integrates with Python ML scripts
    - Provides fallback rule-based analysis
    - Calculates performance metrics and correlations

### 4. **Enhanced UI Components**

#### a) **Transcript Sentiment Viewer**
- **File**: `transcript-sentiment-viewer.blade.php`
- **Features**:
  - Sentence-level sentiment highlighting
  - Overall sentiment summary with confidence scores
  - Sentiment distribution visualization
  - Interactive sentence selection

#### b) **Audio Player with Sentiment Timeline**
- **File**: `audio-player-sentiment.blade.php`
- **Features**:
  - Streams audio from CloudFront URLs
  - Visual sentiment timeline overlay
  - Synchronized with transcript viewer
  - Playback speed controls

#### c) **Agent Performance Dashboard Widget**
- **Files**: 
  - `AgentPerformanceWidget.php`
  - `agent-performance.blade.php`
- **Features**:
  - Performance score calculation (0-100)
  - Multi-metric trend charts
  - Correlation analysis
  - Top performer leaderboard
  - Actionable insights

### 5. **Background Processing**
- **Job**: `AnalyzeCallSentimentJob.php`
- Processes sentiment analysis asynchronously
- Updates agent performance metrics
- Handles failures gracefully

## Key Features Implemented

### 1. **Robust ML Pipeline**
- Handles missing data (only 20% of calls have transcripts)
- Feature extraction from multiple sources:
  - Text features (TF-IDF, sentiment keywords, sentence structure)
  - Metadata features (duration, time of day, cost)
  - Outcome features (appointment booking, customer info)
- Fallback to rule-based analysis when ML unavailable

### 2. **Real-time Visualization**
- Sentence-level sentiment with color coding:
  - Green: Positive sentiment
  - Red: Negative sentiment  
  - Gray: Neutral sentiment
- Audio timeline with sentiment regions
- Performance trends over time

### 3. **Performance Analytics**
- Agent performance scoring combining:
  - Sentiment scores (40% weight)
  - Conversion rates (50% weight)
  - Customer satisfaction (10% weight)
- Correlation analysis between metrics
- Hourly performance patterns

### 4. **CloudFront Audio Support**
- Direct streaming from external URLs
- No local download required
- Loading states and error handling

## Current Limitations & Next Steps

### Limitations:
1. **Limited Training Data**: Only 45 calls in last 30 days, 9 with transcripts
2. **No Trained ML Model**: Currently using rule-based fallback
3. **Missing Audio Analysis**: Audio features not yet implemented
4. **Limited Word Timestamps**: Only 2 calls have transcript_object with timestamps

### Immediate Next Steps:
1. **Create Training Data**:
   ```bash
   php artisan ml:generate-training-data
   python ml/train_model.py
   ```

2. **Integrate with Webhook Processing**:
   ```php
   // In RetellWebhookHandler
   AnalyzeCallSentimentJob::dispatch($call);
   ```

3. **Update CallResource to Use New Components**:
   ```php
   // In CallResource infolist
   ViewEntry::make('transcript')
       ->view('filament.infolists.transcript-sentiment-viewer')
   ```

4. **Add Performance Dashboard to Navigation**:
   ```php
   // In AdminPanelProvider
   ->widgets([
       AgentPerformanceWidget::class,
   ])
   ```

## Usage Instructions

### 1. **Install Python Dependencies**:
```bash
cd /var/www/api-gateway/ml
pip install -r requirements.txt
python -m spacy download de_core_news_sm
```

### 2. **Run Database Migration**:
```bash
php artisan migrate
```

### 3. **Process Existing Calls**:
```bash
php artisan calls:analyze-sentiment --all
```

### 4. **View Dashboard**:
- Navigate to `/admin`
- Agent Performance widget will appear on dashboard
- Click on individual calls to see sentiment analysis

## Technical Architecture

```
User Request → CallResource → View Call Details
                                  ↓
                    ┌─────────────────────────────┐
                    │   Transcript Sentiment      │
                    │        Viewer               │
                    └─────────────┬───────────────┘
                                  ↓
                    ┌─────────────────────────────┐
                    │    Audio Player with        │
                    │   Sentiment Timeline        │
                    └─────────────────────────────┘
                    
Webhook → ProcessRetellCallEndedJob → AnalyzeCallSentimentJob
                                            ↓
                                    SentimentAnalysisService
                                            ↓
                                    Python ML Script
                                            ↓
                                    Store Predictions
                                            ↓
                                    Update Metrics
```

## Configuration

Add to `.env`:
```env
# ML Configuration
ML_PYTHON_PATH=/usr/bin/python3
ML_SERVICE_URL=http://localhost:5000
ML_MODEL_PATH=/var/www/api-gateway/ml/models/sentiment_model.pkl
```

## Monitoring & Debugging

### Check ML Processing:
```sql
-- Check predictions
SELECT * FROM ml_call_predictions ORDER BY created_at DESC LIMIT 10;

-- Check agent metrics
SELECT * FROM agent_performance_metrics WHERE date = CURDATE();

-- Check processing times
SELECT AVG(processing_time_ms) as avg_ms, MAX(processing_time_ms) as max_ms 
FROM ml_call_predictions;
```

### Debug Failed Analysis:
```bash
# Check logs
tail -f storage/logs/laravel.log | grep -i sentiment

# Test Python script directly
cd ml
python analyze_call.py /tmp/test_call.json
```

## Performance Considerations

1. **Caching**: ML predictions are cached in database
2. **Async Processing**: All analysis done in background jobs
3. **Batch Processing**: Agent metrics updated in batches
4. **Streaming Audio**: No local storage required

## Security Considerations

1. **External URL Access**: Audio URLs are CloudFront signed URLs
2. **Data Privacy**: Sentiment analysis runs locally, no external APIs
3. **Multi-tenancy**: All queries respect company_id scoping

This implementation provides a solid foundation for ML-based sentiment analysis while handling the reality of limited data availability in the current system.