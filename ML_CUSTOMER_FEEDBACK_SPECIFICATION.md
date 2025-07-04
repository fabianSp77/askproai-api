# Technical Specification: ML Customer Feedback Classification

## 1. System Architecture

### 1.1 Overview
```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Call Webhook  │────▶│  Queue/Job       │────▶│  ML Pipeline    │
│   (Retell.ai)   │     │  Processing      │     │  Service        │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                                                           │
                                ┌──────────────────────────┴───────────┐
                                │                                      │
                        ┌───────▼────────┐                   ┌────────▼────────┐
                        │ Feature        │                   │ Model           │
                        │ Extraction     │                   │ Inference       │
                        └────────────────┘                   └─────────────────┘
                                                                      │
                                                            ┌─────────▼────────┐
                                                            │ Database         │
                                                            │ Storage          │
                                                            └──────────────────┘
```

### 1.2 Components

#### 1.2.1 ML Pipeline Service
```php
namespace App\Services\ML;

class CustomerFeedbackClassifier
{
    private FeatureExtractor $featureExtractor;
    private ModelInference $modelInference;
    private MetricsCollector $metricsCollector;
    
    public function classifyCall(Call $call): Classification
    {
        // Extract features
        $features = $this->featureExtractor->extract($call);
        
        // Run inference
        $prediction = $this->modelInference->predict($features);
        
        // Collect metrics
        $this->metricsCollector->record($call, $prediction);
        
        return $prediction;
    }
}
```

#### 1.2.2 Feature Extraction Pipeline
```php
namespace App\Services\ML;

class FeatureExtractor
{
    private TextFeatureExtractor $textExtractor;
    private AudioFeatureExtractor $audioExtractor;
    private ContextFeatureExtractor $contextExtractor;
    
    public function extract(Call $call): FeatureVector
    {
        $textFeatures = $this->textExtractor->extract($call->transcript);
        $contextFeatures = $this->contextExtractor->extract($call);
        
        // Optional audio features
        $audioFeatures = null;
        if ($call->audio_url) {
            $audioFeatures = $this->audioExtractor->extract($call->audio_url);
        }
        
        return new FeatureVector([
            'text' => $textFeatures,
            'context' => $contextFeatures,
            'audio' => $audioFeatures
        ]);
    }
}
```

## 2. Database Schema

### 2.1 New Tables

```sql
-- ML Model Versions
CREATE TABLE ml_models (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    version VARCHAR(50) NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'sentiment', 'goal_achievement', 'quality'
    model_path TEXT NOT NULL,
    metrics JSON,
    is_active BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Call Classifications
CREATE TABLE call_classifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    call_id UUID NOT NULL REFERENCES calls(id),
    model_id UUID NOT NULL REFERENCES ml_models(id),
    
    -- Sentiment Analysis
    sentiment VARCHAR(20), -- 'positive', 'neutral', 'negative'
    sentiment_score DECIMAL(3,2), -- 0.00 to 1.00
    
    -- Goal Achievement (Multi-label)
    goals_achieved JSON, -- ['appointment_booked', 'information_provided']
    goal_scores JSON, -- {'appointment_booked': 0.95, 'information_provided': 0.87}
    
    -- Quality Metrics
    clarity_score DECIMAL(3,2),
    efficiency_score DECIMAL(3,2),
    professionalism_score DECIMAL(3,2),
    
    -- Business Metrics
    conversion_probability DECIMAL(3,2),
    urgency_level VARCHAR(20), -- 'low', 'medium', 'high'
    
    -- Metadata
    features JSON, -- Store feature vector for debugging
    processing_time_ms INTEGER,
    confidence_scores JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_call_classifications_call_id (call_id),
    INDEX idx_call_classifications_sentiment (sentiment),
    INDEX idx_call_classifications_created (created_at)
);

-- Training Data Annotations
CREATE TABLE call_annotations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    call_id UUID NOT NULL REFERENCES calls(id),
    annotator_id UUID REFERENCES users(id),
    
    -- Manual labels for training
    sentiment VARCHAR(20),
    goals_achieved JSON,
    quality_scores JSON,
    
    notes TEXT,
    is_verified BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_call_annotator (call_id, annotator_id)
);

-- ML Metrics Tracking
CREATE TABLE ml_metrics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    model_id UUID NOT NULL REFERENCES ml_models(id),
    metric_type VARCHAR(50), -- 'accuracy', 'f1_score', 'precision', 'recall'
    metric_value DECIMAL(5,4),
    metadata JSON,
    measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_ml_metrics_model (model_id, metric_type)
);
```

### 2.2 Migrations

```php
// database/migrations/2025_06_25_create_ml_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMlTables extends Migration
{
    public function up()
    {
        // ML Models table
        Schema::create('ml_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('version', 50);
            $table->string('type', 50);
            $table->text('model_path');
            $table->json('metrics')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->unique(['name', 'version']);
        });
        
        // Call Classifications table
        Schema::create('call_classifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('call_id');
            $table->uuid('model_id');
            
            // Sentiment
            $table->string('sentiment', 20)->nullable();
            $table->decimal('sentiment_score', 3, 2)->nullable();
            
            // Goals
            $table->json('goals_achieved')->nullable();
            $table->json('goal_scores')->nullable();
            
            // Quality
            $table->decimal('clarity_score', 3, 2)->nullable();
            $table->decimal('efficiency_score', 3, 2)->nullable();
            $table->decimal('professionalism_score', 3, 2)->nullable();
            
            // Business
            $table->decimal('conversion_probability', 3, 2)->nullable();
            $table->string('urgency_level', 20)->nullable();
            
            // Metadata
            $table->json('features')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->json('confidence_scores')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('call_id')->references('id')->on('calls');
            $table->foreign('model_id')->references('id')->on('ml_models');
            
            $table->index('call_id');
            $table->index('sentiment');
            $table->index('created_at');
        });
    }
}
```

## 3. Feature Engineering Details

### 3.1 Text Features Implementation

```python
# ml/feature_extractors/text_features.py
import pandas as pd
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from textblob_de import TextBlobDE
import spacy
import re

class TextFeatureExtractor:
    def __init__(self):
        self.nlp = spacy.load('de_core_news_sm')
        self.tfidf = TfidfVectorizer(
            max_features=1000,
            ngram_range=(1, 2),
            min_df=5,
            stop_words=self._load_german_stopwords()
        )
        
    def extract_features(self, transcript: str) -> dict:
        features = {}
        
        # Basic statistics
        features.update(self._extract_basic_stats(transcript))
        
        # Sentiment features
        features.update(self._extract_sentiment_features(transcript))
        
        # Linguistic features
        features.update(self._extract_linguistic_features(transcript))
        
        # Conversation features
        features.update(self._extract_conversation_features(transcript))
        
        # Keywords and entities
        features.update(self._extract_keywords_entities(transcript))
        
        return features
    
    def _extract_basic_stats(self, text: str) -> dict:
        sentences = text.split('.')
        words = text.split()
        
        return {
            'word_count': len(words),
            'sentence_count': len(sentences),
            'avg_sentence_length': len(words) / max(len(sentences), 1),
            'question_marks': text.count('?'),
            'exclamation_marks': text.count('!'),
            'unique_words': len(set(words)),
            'lexical_diversity': len(set(words)) / max(len(words), 1)
        }
    
    def _extract_sentiment_features(self, text: str) -> dict:
        blob = TextBlobDE(text)
        sentences = text.split('.')
        
        sentiments = []
        for sentence in sentences:
            if sentence.strip():
                try:
                    sent_blob = TextBlobDE(sentence)
                    sentiments.append(sent_blob.sentiment.polarity)
                except:
                    pass
        
        return {
            'overall_polarity': blob.sentiment.polarity,
            'overall_subjectivity': blob.sentiment.subjectivity,
            'avg_sentence_polarity': np.mean(sentiments) if sentiments else 0,
            'std_sentence_polarity': np.std(sentiments) if sentiments else 0,
            'min_sentence_polarity': min(sentiments) if sentiments else 0,
            'max_sentence_polarity': max(sentiments) if sentiments else 0
        }
    
    def _extract_linguistic_features(self, text: str) -> dict:
        doc = self.nlp(text)
        
        pos_counts = {}
        for token in doc:
            pos = token.pos_
            pos_counts[f'pos_{pos}'] = pos_counts.get(f'pos_{pos}', 0) + 1
        
        # Normalize by total tokens
        total_tokens = len(doc)
        for key in pos_counts:
            pos_counts[key] = pos_counts[key] / max(total_tokens, 1)
        
        return pos_counts
    
    def _extract_conversation_features(self, text: str) -> dict:
        # Identify speaker turns (assuming format "Agent: ... Kunde: ...")
        agent_pattern = r'Agent:|AI:|Assistent:'
        customer_pattern = r'Kunde:|Anrufer:|Customer:'
        
        agent_turns = len(re.findall(agent_pattern, text, re.IGNORECASE))
        customer_turns = len(re.findall(customer_pattern, text, re.IGNORECASE))
        
        # Extract agent and customer text
        agent_text = ' '.join(re.findall(f'{agent_pattern}(.*?)(?:{customer_pattern}|$)', text, re.IGNORECASE))
        customer_text = ' '.join(re.findall(f'{customer_pattern}(.*?)(?:{agent_pattern}|$)', text, re.IGNORECASE))
        
        return {
            'speaker_turns': agent_turns + customer_turns,
            'agent_turns': agent_turns,
            'customer_turns': customer_turns,
            'turn_ratio': agent_turns / max(customer_turns, 1),
            'agent_word_count': len(agent_text.split()),
            'customer_word_count': len(customer_text.split()),
            'agent_speak_ratio': len(agent_text) / max(len(text), 1)
        }
    
    def _extract_keywords_entities(self, text: str) -> dict:
        doc = self.nlp(text)
        
        # Named entities
        entities = {}
        for ent in doc.ents:
            entity_type = f'entity_{ent.label_}'
            entities[entity_type] = entities.get(entity_type, 0) + 1
        
        # Key phrases indicating goals
        goal_keywords = {
            'appointment_booked': ['termin', 'vereinbaren', 'buchen', 'appointment'],
            'information_received': ['information', 'frage', 'wissen', 'erklärt'],
            'issue_resolved': ['gelöst', 'problem', 'behoben', 'erledigt'],
            'callback_scheduled': ['rückruf', 'zurückrufen', 'callback']
        }
        
        keyword_features = {}
        for goal, keywords in goal_keywords.items():
            count = sum(1 for keyword in keywords if keyword.lower() in text.lower())
            keyword_features[f'keyword_{goal}'] = count
        
        features = {**entities, **keyword_features}
        return features
```

### 3.2 Context Features Implementation

```python
# ml/feature_extractors/context_features.py
from datetime import datetime
import holidays

class ContextFeatureExtractor:
    def __init__(self):
        self.de_holidays = holidays.Germany()
    
    def extract_features(self, call_data: dict) -> dict:
        features = {}
        
        # Temporal features
        features.update(self._extract_temporal_features(call_data))
        
        # Customer features
        features.update(self._extract_customer_features(call_data))
        
        # Call metadata features
        features.update(self._extract_call_features(call_data))
        
        return features
    
    def _extract_temporal_features(self, call_data: dict) -> dict:
        timestamp = datetime.fromisoformat(call_data['created_at'])
        
        return {
            'hour_of_day': timestamp.hour,
            'day_of_week': timestamp.weekday(),
            'is_weekend': timestamp.weekday() >= 5,
            'is_holiday': timestamp.date() in self.de_holidays,
            'month': timestamp.month,
            'is_business_hours': 8 <= timestamp.hour < 18,
            'time_since_midnight': timestamp.hour * 60 + timestamp.minute
        }
    
    def _extract_customer_features(self, call_data: dict) -> dict:
        # These would come from joined data
        return {
            'is_new_customer': call_data.get('is_new_customer', True),
            'previous_calls': call_data.get('previous_calls', 0),
            'days_since_last_call': call_data.get('days_since_last_call', -1),
            'previous_appointments': call_data.get('previous_appointments', 0),
            'customer_lifetime_days': call_data.get('customer_lifetime_days', 0)
        }
    
    def _extract_call_features(self, call_data: dict) -> dict:
        return {
            'duration_seconds': call_data.get('duration_sec', 0),
            'duration_minutes': call_data.get('duration_sec', 0) / 60,
            'has_appointment': call_data.get('appointment_id') is not None,
            'agent_version': float(call_data.get('agent_version', '1.0').replace('V', '')),
            'call_successful': call_data.get('call_successful', False)
        }
```

## 4. Model Implementation

### 4.1 Scikit-learn Pipeline

```python
# ml/models/feedback_classifier.py
import joblib
import numpy as np
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.svm import SVC
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import cross_val_score
from sklearn.metrics import classification_report

class CustomerFeedbackClassifier:
    def __init__(self):
        self.models = {
            'sentiment': self._build_sentiment_model(),
            'goals': self._build_goal_model(),
            'quality': self._build_quality_model()
        }
        
    def _build_sentiment_model(self):
        return Pipeline([
            ('scaler', StandardScaler()),
            ('classifier', RandomForestClassifier(
                n_estimators=100,
                max_depth=10,
                random_state=42,
                n_jobs=-1
            ))
        ])
    
    def _build_goal_model(self):
        # Multi-label classification
        from sklearn.multioutput import MultiOutputClassifier
        
        base_estimator = GradientBoostingClassifier(
            n_estimators=100,
            learning_rate=0.1,
            max_depth=5,
            random_state=42
        )
        
        return Pipeline([
            ('scaler', StandardScaler()),
            ('classifier', MultiOutputClassifier(base_estimator))
        ])
    
    def _build_quality_model(self):
        # Regression for quality scores
        from sklearn.ensemble import RandomForestRegressor
        from sklearn.multioutput import MultiOutputRegressor
        
        return Pipeline([
            ('scaler', StandardScaler()),
            ('regressor', MultiOutputRegressor(
                RandomForestRegressor(
                    n_estimators=100,
                    max_depth=10,
                    random_state=42
                )
            ))
        ])
    
    def train(self, X_train, y_train, model_type='sentiment'):
        model = self.models[model_type]
        model.fit(X_train, y_train)
        return model
    
    def predict(self, X, model_type='sentiment'):
        model = self.models[model_type]
        predictions = model.predict(X)
        
        # Get probability scores for classification
        if hasattr(model.named_steps['classifier'], 'predict_proba'):
            probabilities = model.named_steps['classifier'].predict_proba(X)
        else:
            probabilities = None
            
        return predictions, probabilities
    
    def evaluate(self, X_test, y_test, model_type='sentiment'):
        model = self.models[model_type]
        predictions = model.predict(X_test)
        
        if model_type in ['sentiment', 'goals']:
            report = classification_report(y_test, predictions, output_dict=True)
        else:
            # For regression tasks
            from sklearn.metrics import mean_squared_error, r2_score
            report = {
                'mse': mean_squared_error(y_test, predictions),
                'r2': r2_score(y_test, predictions)
            }
            
        return report
    
    def save_model(self, model_type, version, path):
        model = self.models[model_type]
        joblib.dump(model, f"{path}/{model_type}_v{version}.joblib")
    
    def load_model(self, model_type, version, path):
        self.models[model_type] = joblib.load(f"{path}/{model_type}_v{version}.joblib")
```

## 5. Laravel Integration

### 5.1 ML Service

```php
// app/Services/ML/MLService.php
namespace App\Services\ML;

use App\Models\Call;
use App\Models\CallClassification;
use App\Models\MLModel;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;

class MLService
{
    private string $pythonPath;
    private string $scriptsPath;
    
    public function __construct()
    {
        $this->pythonPath = config('ml.python_path', '/usr/bin/python3');
        $this->scriptsPath = base_path('ml');
    }
    
    public function classifyCall(Call $call): CallClassification
    {
        // Extract features
        $features = $this->extractFeatures($call);
        
        // Get active models
        $models = MLModel::where('is_active', true)->get();
        
        $classification = new CallClassification([
            'call_id' => $call->id,
            'model_id' => $models->first()->id
        ]);
        
        foreach ($models as $model) {
            $prediction = $this->runInference($model, $features);
            
            switch ($model->type) {
                case 'sentiment':
                    $classification->sentiment = $prediction['class'];
                    $classification->sentiment_score = $prediction['probability'];
                    break;
                    
                case 'goals':
                    $classification->goals_achieved = $prediction['classes'];
                    $classification->goal_scores = $prediction['probabilities'];
                    break;
                    
                case 'quality':
                    $classification->clarity_score = $prediction['clarity'];
                    $classification->efficiency_score = $prediction['efficiency'];
                    $classification->professionalism_score = $prediction['professionalism'];
                    break;
            }
        }
        
        $classification->save();
        
        return $classification;
    }
    
    private function extractFeatures(Call $call): array
    {
        $cacheKey = "ml_features:{$call->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($call) {
            $result = Process::path($this->scriptsPath)
                ->run([
                    $this->pythonPath,
                    'extract_features.py',
                    '--call-id', $call->id,
                    '--transcript', $call->transcript,
                    '--metadata', json_encode($call->toArray())
                ]);
            
            if (!$result->successful()) {
                throw new \Exception("Feature extraction failed: " . $result->errorOutput());
            }
            
            return json_decode($result->output(), true);
        });
    }
    
    private function runInference(MLModel $model, array $features): array
    {
        $result = Process::path($this->scriptsPath)
            ->run([
                $this->pythonPath,
                'inference.py',
                '--model-path', $model->model_path,
                '--features', json_encode($features)
            ]);
        
        if (!$result->successful()) {
            throw new \Exception("Model inference failed: " . $result->errorOutput());
        }
        
        return json_decode($result->output(), true);
    }
}
```

### 5.2 Queue Job

```php
// app/Jobs/ClassifyCallJob.php
namespace App\Jobs;

use App\Models\Call;
use App\Services\ML\MLService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClassifyCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        private Call $call
    ) {}
    
    public function handle(MLService $mlService): void
    {
        try {
            $classification = $mlService->classifyCall($this->call);
            
            // Update call with classification results
            $this->call->update([
                'sentiment' => $classification->sentiment,
                'sentiment_score' => $classification->sentiment_score,
                'analysis' => array_merge($this->call->analysis ?? [], [
                    'ml_classification' => $classification->toArray()
                ])
            ]);
            
            // Trigger alerts for negative sentiment
            if ($classification->sentiment === 'negative' && $classification->sentiment_score < 0.3) {
                event(new NegativeCallAlert($this->call, $classification));
            }
            
        } catch (\Exception $e) {
            \Log::error('Call classification failed', [
                'call_id' => $this->call->id,
                'error' => $e->getMessage()
            ]);
            
            $this->fail($e);
        }
    }
}
```

### 5.3 API Endpoints

```php
// app/Http/Controllers/Api/MLController.php
namespace App\Http\Controllers\Api;

use App\Models\Call;
use App\Models\CallClassification;
use App\Services\ML\MLService;
use Illuminate\Http\Request;

class MLController extends Controller
{
    public function __construct(
        private MLService $mlService
    ) {}
    
    /**
     * Classify a single call
     */
    public function classifyCall(Request $request, Call $call)
    {
        $this->authorize('view', $call);
        
        $classification = $this->mlService->classifyCall($call);
        
        return response()->json([
            'classification' => $classification,
            'call_id' => $call->id
        ]);
    }
    
    /**
     * Get classification history for a call
     */
    public function getClassifications(Call $call)
    {
        $this->authorize('view', $call);
        
        $classifications = CallClassification::where('call_id', $call->id)
            ->with('mlModel')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($classifications);
    }
    
    /**
     * Bulk classify calls
     */
    public function bulkClassify(Request $request)
    {
        $validated = $request->validate([
            'call_ids' => 'required|array',
            'call_ids.*' => 'exists:calls,id'
        ]);
        
        $calls = Call::whereIn('id', $validated['call_ids'])
            ->where('company_id', auth()->user()->company_id)
            ->get();
            
        foreach ($calls as $call) {
            ClassifyCallJob::dispatch($call);
        }
        
        return response()->json([
            'message' => 'Classification jobs queued',
            'count' => $calls->count()
        ]);
    }
    
    /**
     * Get ML metrics and statistics
     */
    public function getMetrics(Request $request)
    {
        $companyId = auth()->user()->company_id;
        
        $stats = [
            'total_classifications' => CallClassification::whereHas('call', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->count(),
            
            'sentiment_distribution' => CallClassification::whereHas('call', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->selectRaw('sentiment, COUNT(*) as count')
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment'),
            
            'average_scores' => CallClassification::whereHas('call', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->selectRaw('
                AVG(sentiment_score) as avg_sentiment,
                AVG(clarity_score) as avg_clarity,
                AVG(efficiency_score) as avg_efficiency,
                AVG(professionalism_score) as avg_professionalism
            ')
            ->first(),
            
            'goal_achievement_rate' => $this->calculateGoalAchievementRate($companyId)
        ];
        
        return response()->json($stats);
    }
    
    private function calculateGoalAchievementRate($companyId)
    {
        $classifications = CallClassification::whereHas('call', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->get();
        
        $goalStats = [];
        
        foreach ($classifications as $classification) {
            $goals = $classification->goals_achieved ?? [];
            foreach ($goals as $goal) {
                $goalStats[$goal] = ($goalStats[$goal] ?? 0) + 1;
            }
        }
        
        $total = $classifications->count();
        
        foreach ($goalStats as $goal => $count) {
            $goalStats[$goal] = $total > 0 ? round($count / $total * 100, 2) : 0;
        }
        
        return $goalStats;
    }
}
```

## 6. Configuration

### 6.1 ML Configuration File

```php
// config/ml.php
return [
    'python_path' => env('ML_PYTHON_PATH', '/usr/bin/python3'),
    
    'models' => [
        'sentiment' => [
            'enabled' => env('ML_SENTIMENT_ENABLED', true),
            'version' => env('ML_SENTIMENT_VERSION', '1.0'),
            'path' => storage_path('ml/models/sentiment'),
        ],
        'goals' => [
            'enabled' => env('ML_GOALS_ENABLED', true),
            'version' => env('ML_GOALS_VERSION', '1.0'),
            'path' => storage_path('ml/models/goals'),
        ],
        'quality' => [
            'enabled' => env('ML_QUALITY_ENABLED', true),
            'version' => env('ML_QUALITY_VERSION', '1.0'),
            'path' => storage_path('ml/models/quality'),
        ],
    ],
    
    'feature_extraction' => [
        'cache_ttl' => env('ML_FEATURE_CACHE_TTL', 3600),
        'batch_size' => env('ML_BATCH_SIZE', 100),
    ],
    
    'alerts' => [
        'negative_sentiment_threshold' => env('ML_NEGATIVE_THRESHOLD', 0.3),
        'low_quality_threshold' => env('ML_QUALITY_THRESHOLD', 0.5),
    ],
];
```

### 6.2 Python Requirements

```txt
# requirements.txt
scikit-learn==1.3.0
pandas==2.0.3
numpy==1.24.3
spacy==3.6.0
textblob-de==0.4.3
joblib==1.3.1
holidays==0.29
librosa==0.10.0
transformers==4.30.2
torch==2.0.1
```

## 7. Testing Strategy

### 7.1 Unit Tests

```php
// tests/Unit/ML/MLServiceTest.php
namespace Tests\Unit\ML;

use Tests\TestCase;
use App\Models\Call;
use App\Services\ML\MLService;

class MLServiceTest extends TestCase
{
    public function test_can_extract_features_from_call()
    {
        $call = Call::factory()->create([
            'transcript' => 'Agent: Guten Tag! Kunde: Ich möchte einen Termin vereinbaren.'
        ]);
        
        $service = new MLService();
        $features = $service->extractFeatures($call);
        
        $this->assertArrayHasKey('word_count', $features);
        $this->assertArrayHasKey('sentiment_score', $features);
    }
    
    public function test_negative_sentiment_triggers_alert()
    {
        Event::fake();
        
        $call = Call::factory()->create([
            'transcript' => 'Kunde: Ich bin sehr unzufrieden mit dem Service!'
        ]);
        
        ClassifyCallJob::dispatchSync($call);
        
        Event::assertDispatched(NegativeCallAlert::class);
    }
}
```

### 7.2 Integration Tests

```python
# tests/test_ml_pipeline.py
import pytest
from ml.models.feedback_classifier import CustomerFeedbackClassifier
from ml.feature_extractors.text_features import TextFeatureExtractor

def test_sentiment_classification():
    classifier = CustomerFeedbackClassifier()
    text_extractor = TextFeatureExtractor()
    
    # Positive example
    positive_text = "Vielen Dank! Das war sehr hilfreich. Ich bin sehr zufrieden."
    features = text_extractor.extract_features(positive_text)
    
    # Mock feature vector
    X = [[features[key] for key in sorted(features.keys())]]
    
    # This would use a pre-trained model in production
    prediction, probability = classifier.predict(X, model_type='sentiment')
    
    assert prediction[0] in ['positive', 'neutral', 'negative']
    assert 0 <= probability[0][0] <= 1

def test_goal_achievement_detection():
    text_extractor = TextFeatureExtractor()
    
    appointment_text = "Agent: Der Termin ist für morgen um 10 Uhr gebucht. Kunde: Perfekt, danke!"
    features = text_extractor.extract_features(appointment_text)
    
    assert features['keyword_appointment_booked'] > 0
```

## 8. Deployment

### 8.1 Docker Setup

```dockerfile
# Dockerfile.ml
FROM python:3.9-slim

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Download spaCy model
RUN python -m spacy download de_core_news_sm

# Copy ML scripts
COPY ml/ ./ml/

# Create model directory
RUN mkdir -p /app/models

CMD ["python", "-m", "ml.api.server"]
```

### 8.2 Supervisor Configuration

```ini
# /etc/supervisor/conf.d/ml-worker.conf
[program:ml-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/python3 /var/www/api-gateway/ml/worker.py
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/ml-worker.log
```

## 9. Monitoring & Alerts

### 9.1 Prometheus Metrics

```php
// app/Services/ML/MetricsExporter.php
namespace App\Services\ML;

class MetricsExporter
{
    public function export(): array
    {
        return [
            'ml_classifications_total' => CallClassification::count(),
            'ml_negative_sentiment_rate' => $this->getNegativeSentimentRate(),
            'ml_avg_processing_time_ms' => $this->getAvgProcessingTime(),
            'ml_model_accuracy' => $this->getModelAccuracy(),
        ];
    }
}
```

### 9.2 Alert Configuration

```php
// app/Notifications/MLAlertNotification.php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class MLAlertNotification extends Notification
{
    public function via($notifiable)
    {
        return ['slack', 'mail'];
    }
    
    public function toSlack($notifiable)
    {
        return (new SlackMessage)
            ->error()
            ->content('ML Alert: High negative sentiment detected')
            ->attachment(function ($attachment) use ($notifiable) {
                $attachment->title('Call Details')
                    ->fields([
                        'Call ID' => $this->call->id,
                        'Sentiment Score' => $this->classification->sentiment_score,
                        'Customer' => $this->call->customer->name,
                    ]);
            });
    }
}
```

---

This specification provides a complete implementation plan for the ML customer feedback classification system. The next step would be to begin implementation following the test-driven development approach.