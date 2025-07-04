#!/usr/bin/env python3
"""
Train ML sentiment model from call data
"""

import argparse
import json
import os
import sys
from pathlib import Path
import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import classification_report, confusion_matrix
import joblib
from datetime import datetime

# Add the ml directory to Python path
sys.path.append(str(Path(__file__).parent))

from sentiment_analyzer import SentimentAnalyzer

def load_training_data(csv_path):
    """Load and prepare training data"""
    print(f"Loading training data from {csv_path}")
    
    df = pd.read_csv(csv_path)
    print(f"Loaded {len(df)} samples")
    
    # Filter out samples without sentiment labels
    df = df[df['sentiment_label'].notna()]
    df = df[df['transcript'].notna()]
    
    print(f"Filtered to {len(df)} samples with valid labels")
    
    return df

def prepare_features(df, analyzer):
    """Extract features from the dataframe"""
    print("Extracting features...")
    
    features_list = []
    
    for _, row in df.iterrows():
        # Prepare call data
        call_data = {
            'transcript': row['transcript'],
            'duration_sec': row['duration_sec'],
            'cost': row['cost'],
            'appointment_id': row['has_appointment'],
            'customer_id': row['has_customer'],
            'call_successful': row['call_successful'],
            'disconnection_reason': 'normal' if row['normal_hangup'] else 'abnormal',
        }
        
        # Extract features
        features = analyzer.extract_features(call_data)
        features_list.append(features)
    
    # Convert to DataFrame
    features_df = pd.DataFrame(features_list)
    
    print(f"Extracted {len(features_df.columns)} features")
    
    return features_df

def train_and_evaluate(features_df, labels, analyzer):
    """Train the model and evaluate performance"""
    print("\nTraining model...")
    
    # Split data
    X_train, X_test, y_train, y_test = train_test_split(
        features_df, labels, test_size=0.2, random_state=42, stratify=labels
    )
    
    print(f"Training set: {len(X_train)} samples")
    print(f"Test set: {len(X_test)} samples")
    
    # Train model
    analyzer.train_model(
        pd.concat([X_train, pd.Series(y_train, name='sentiment', index=X_train.index)], axis=1),
        target_column='sentiment'
    )
    
    # Evaluate on test set
    print("\nEvaluating model...")
    
    # Get predictions
    predictions = []
    for _, row in X_test.iterrows():
        # Convert row to dict
        features = row.to_dict()
        # Create dummy call data
        call_data = {'features': features}
        result = analyzer.analyze_sentiment(call_data)
        predictions.append(result['sentiment'])
    
    # Calculate metrics
    from sklearn.metrics import accuracy_score, precision_recall_fscore_support
    
    accuracy = accuracy_score(y_test, predictions)
    precision, recall, f1, support = precision_recall_fscore_support(
        y_test, predictions, average='weighted'
    )
    
    print(f"\naccuracy: {accuracy:.3f}")
    print(f"Precision: {precision:.3f}")
    print(f"Recall: {recall:.3f}")
    print(f"F1-Score: {f1:.3f}")
    
    # Print classification report
    print("\nClassification Report:")
    print(classification_report(y_test, predictions))
    
    # Cross-validation
    print("\nPerforming cross-validation...")
    cv_scores = cross_val_score(
        analyzer.model, 
        analyzer.feature_pipeline.transform(features_df),
        labels.map({'negative': 0, 'neutral': 1, 'positive': 2}),
        cv=5
    )
    print(f"Cross-validation scores: {cv_scores}")
    print(f"Mean CV score: {cv_scores.mean():.3f} (+/- {cv_scores.std() * 2:.3f})")
    
    # Feature importance
    if hasattr(analyzer.model, 'feature_importances_'):
        feature_names = features_df.columns.tolist()
        importances = analyzer.model.feature_importances_
        
        # Sort features by importance
        indices = np.argsort(importances)[::-1]
        top_features = {}
        
        print("\nTop 10 most important features:")
        for i in range(min(10, len(indices))):
            idx = indices[i]
            feature_name = feature_names[idx]
            importance = importances[idx]
            top_features[feature_name] = float(importance)
            print(f"{i+1}. {feature_name}: {importance:.4f}")
        
        print(f"\nfeature_importance: {json.dumps(top_features[:5])}")
    
    return accuracy, len(features_df)

def main():
    parser = argparse.ArgumentParser(description='Train ML sentiment model')
    parser.add_argument('--data', required=True, help='Path to training data CSV')
    parser.add_argument('--output', required=True, help='Output directory for model')
    parser.add_argument('--model-name', default='sentiment_model', help='Model filename')
    
    args = parser.parse_args()
    
    try:
        # Load data
        df = load_training_data(args.data)
        
        if len(df) < 10:
            print("Error: Not enough training samples (minimum 10 required)")
            sys.exit(1)
        
        # Initialize analyzer
        analyzer = SentimentAnalyzer()
        
        # Prepare features
        features_df = prepare_features(df, analyzer)
        labels = df['sentiment_label']
        
        # Train and evaluate
        accuracy, num_samples = train_and_evaluate(features_df, labels, analyzer)
        
        # Save model
        model_path = os.path.join(args.output, f"{args.model_name}.pkl")
        analyzer.save_model(model_path)
        print(f"\nModel saved to: {model_path}")
        
        # Print final summary
        print(f"\nsamples: {num_samples}")
        print(f"accuracy: {accuracy:.3f}")
        print(f"saved to: {model_path}")
        
    except Exception as e:
        print(f"Error during training: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

if __name__ == '__main__':
    main()