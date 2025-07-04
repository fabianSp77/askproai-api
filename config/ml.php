<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Machine Learning Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the ML sentiment analysis system
    |
    */

    'python_path' => env('ML_PYTHON_PATH', '/usr/bin/python3'),
    
    'sentiment_service_url' => env('ML_SERVICE_URL', 'http://localhost:5000'),
    
    'model_path' => env('ML_MODEL_PATH', base_path('ml/models/sentiment_model.pkl')),
    
    // Training configuration
    'training' => [
        'min_samples' => 10,
        'test_split' => 0.2,
        'cross_validation_folds' => 5,
    ],
    
    // Feature extraction settings
    'features' => [
        'use_word_embeddings' => false, // Set to true if you have German BERT installed
        'max_transcript_length' => 5000,
        'sentiment_keywords' => [
            'positive' => ['danke', 'super', 'toll', 'perfekt', 'gut', 'gerne', 'freue', 'klasse', 'wunderbar', 'ja', 'prima', 'schön'],
            'negative' => ['problem', 'schlecht', 'nein', 'nicht', 'leider', 'schwierig', 'ärger', 'beschwerde', 'unzufrieden', 'falsch'],
        ],
    ],
];