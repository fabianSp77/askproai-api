#!/usr/bin/env python3
"""
Standalone script to analyze a single call
Can be called directly from Laravel
"""

import sys
import json
import time
from pathlib import Path

# Add the ml directory to Python path
sys.path.append(str(Path(__file__).parent))

from sentiment_analyzer import SentimentAnalyzer

def main():
    if len(sys.argv) < 2:
        print(json.dumps({
            'error': 'No input file provided',
            'usage': 'python analyze_call.py <input_json_file>'
        }))
        sys.exit(1)
    
    input_file = sys.argv[1]
    
    try:
        # Read input data
        with open(input_file, 'r', encoding='utf-8') as f:
            call_data = json.load(f)
        
        # Initialize analyzer
        start_time = time.time()
        
        # Try to load existing model, fallback to rule-based
        model_path = Path(__file__).parent / 'models' / 'sentiment_model.pkl'
        if model_path.exists():
            analyzer = SentimentAnalyzer(str(model_path))
        else:
            analyzer = SentimentAnalyzer()
        
        # Analyze sentiment
        result = analyzer.analyze_sentiment(call_data)
        
        # Add processing time
        processing_time_ms = int((time.time() - start_time) * 1000)
        result['processing_time_ms'] = processing_time_ms
        result['model_version'] = '1.0.0'
        
        # Add satisfaction and goal achievement scores (placeholder for now)
        # These would be calculated based on more sophisticated analysis
        if result['sentiment'] == 'positive' and call_data.get('appointment_id'):
            result['satisfaction_score'] = 0.8
            result['goal_achievement_score'] = 1.0
        elif result['sentiment'] == 'positive':
            result['satisfaction_score'] = 0.7
            result['goal_achievement_score'] = 0.5
        elif result['sentiment'] == 'negative':
            result['satisfaction_score'] = 0.3
            result['goal_achievement_score'] = 0.0
        else:
            result['satisfaction_score'] = 0.5
            result['goal_achievement_score'] = 0.3
        
        # Output result as JSON
        print(json.dumps(result, ensure_ascii=False))
        
    except Exception as e:
        error_result = {
            'error': str(e),
            'sentiment': 'neutral',
            'sentiment_score': 0.0,
            'confidence': 0.0,
            'method': 'error_fallback'
        }
        print(json.dumps(error_result))
        sys.exit(1)

if __name__ == '__main__':
    main()