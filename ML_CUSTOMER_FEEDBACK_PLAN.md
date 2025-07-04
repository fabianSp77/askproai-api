# Machine Learning Customer Feedback Classification Plan

## 🎯 Projektziel

Entwicklung eines ML-Systems zur automatischen Klassifizierung von Kundenfeedback aus Telefongesprächen, um:
1. Kundenzufriedenheit zu bewerten (positiv/negativ)
2. Zielerreichung zu erkennen (Termin gebucht, Informationen erhalten, etc.)
3. Gespräche automatisch zu kategorisieren
4. Performance des AI-Agenten zu messen

## 📊 Verfügbare Daten

### Aus der Call-Tabelle:
- **transcript**: Vollständiges Transkript des Gesprächs
- **audio_url**: URL zur Audiodatei
- **sentiment**: Bereits erfasste Stimmung (falls vorhanden)
- **sentiment_score**: Numerischer Sentiment-Wert
- **analysis**: JSON-Feld mit zusätzlichen Analysen
- **call_successful**: Boolean für erfolgreichen Anruf
- **duration_sec**: Anrufdauer
- **appointment_id**: Verknüpfung zu gebuchtem Termin (falls vorhanden)

### Zusätzliche Kontextdaten:
- **customer_id**: Für Kundenhistorie
- **agent_id**: Für Agent-Performance
- **company_id/branch_id**: Für geschäftsspezifische Ziele

## 🔄 Workflow

### Phase 1: Plan ✓
1. Analysiere vorhandene Datenstruktur
2. Definiere Klassifizierungsziele
3. Erstelle Projektplan

### Phase 2: Specification
1. Detaillierte technische Spezifikation
2. Feature-Engineering-Pipeline
3. Modell-Architektur
4. Evaluationsmetriken

### Phase 3: Test (Pre-Implementation)
1. Datenqualitäts-Check
2. Baseline-Performance ohne ML
3. Test-Datensatz erstellen

### Phase 4: Build
1. Datenaufbereitung
2. Feature-Extraktion
3. Modell-Training
4. API-Integration

### Phase 5: Test (Post-Implementation)
1. Modell-Evaluation
2. A/B-Testing
3. Performance-Monitoring

## 🎯 Klassifizierungsziele

### 1. Sentiment-Analyse (Binary + Multi-class)
- **Positiv**: Zufriedener Kunde, freundlich, dankbar
- **Neutral**: Sachlich, ohne emotionale Färbung
- **Negativ**: Unzufrieden, frustriert, verärgert

### 2. Zielerreichung (Multi-label)
- **appointment_booked**: Termin erfolgreich gebucht
- **information_received**: Kunde hat gewünschte Info erhalten
- **callback_scheduled**: Rückruf vereinbart
- **transferred_to_human**: An Mitarbeiter weitergeleitet
- **issue_resolved**: Problem gelöst
- **no_action_needed**: Keine Aktion erforderlich

### 3. Gesprächsqualität
- **clarity_score**: Klarheit der Kommunikation (0-1)
- **efficiency_score**: Effizienz der Zielerreichung (0-1)
- **professionalism_score**: Professionalität des Agenten (0-1)

### 4. Business-Metriken
- **conversion_probability**: Wahrscheinlichkeit einer Buchung
- **customer_lifetime_value_impact**: Einfluss auf Kundenwert
- **urgency_level**: Dringlichkeit (low/medium/high)

## 🛠️ Technologie-Stack

### Core ML Libraries
- **scikit-learn**: Hauptframework für ML-Modelle
- **pandas**: Datenverarbeitung
- **numpy**: Numerische Berechnungen
- **nltk/spacy**: NLP für Deutsch

### Feature Engineering
- **librosa**: Audio-Feature-Extraktion (falls Audio verwendet)
- **transformers**: Pre-trained Language Models (BERT für Deutsch)
- **textblob-de**: Deutsche Sentiment-Analyse

### Integration
- **Laravel Jobs**: Asynchrone Verarbeitung
- **Redis**: Feature-Caching
- **PostgreSQL**: ML-Modell-Versionierung

## 📈 Features für das Modell

### Text-basierte Features (aus Transkript)
1. **Basis-Features**:
   - Wortanzahl
   - Satzanzahl
   - Durchschnittliche Satzlänge
   - Anzahl Fragezeichen
   - Anzahl Ausrufezeichen

2. **NLP-Features**:
   - TF-IDF Vektoren (Top 1000 Wörter)
   - N-Gramme (Unigrams, Bigrams)
   - Part-of-Speech Tags
   - Named Entity Recognition (Datum, Zeit, Namen)

3. **Sentiment-Features**:
   - Polarität pro Satz
   - Subjektivität
   - Emotionale Wörter (positiv/negativ)
   - Höflichkeitsmarker

4. **Konversations-Features**:
   - Anzahl Sprecherwechsel
   - Verhältnis Agent zu Kunde Sprechzeit
   - Längste Pause
   - Anzahl Unterbrechungen

### Audio-basierte Features (optional)
1. **Prosodische Features**:
   - Pitch (Tonhöhe)
   - Energy (Lautstärke)
   - Speaking Rate
   - Voice Activity Detection

2. **Emotionale Features**:
   - Stress-Level
   - Arousal
   - Valence

### Kontext-Features
1. **Zeitliche Features**:
   - Tageszeit
   - Wochentag
   - Monat
   - Feiertag (ja/nein)

2. **Kunden-Features**:
   - Neue vs. Bestandskunde
   - Anzahl vorheriger Anrufe
   - Historie der Zufriedenheit

3. **Business-Features**:
   - Service-Typ
   - Filiale
   - Agent-Version

## 🎯 Modell-Architektur

### Ensemble-Ansatz
1. **Text-Klassifikator** (Random Forest)
   - Input: TF-IDF + NLP Features
   - Output: Sentiment, Zielerreichung

2. **Audio-Klassifikator** (Optional, SVM)
   - Input: Audio-Features
   - Output: Emotionaler Zustand

3. **Meta-Klassifikator** (Gradient Boosting)
   - Input: Outputs der Sub-Modelle + Kontext
   - Output: Finale Klassifizierung

## 📊 Evaluationsmetriken

### Primäre Metriken
- **Accuracy**: Gesamtgenauigkeit
- **F1-Score**: Balance zwischen Precision und Recall
- **AUC-ROC**: Für binäre Klassifikationen

### Business-Metriken
- **False Negative Rate**: Verpasste unzufriedene Kunden
- **Conversion Accuracy**: Korrekte Vorhersage von Buchungen
- **Alert Precision**: Genauigkeit bei kritischen Fällen

## 🚀 Implementierungsschritte

### Woche 1: Datenvorbereitung
- [ ] Daten aus DB extrahieren
- [ ] Transkripte bereinigen
- [ ] Labels manuell annotieren (Sample)

### Woche 2: Feature Engineering
- [ ] Text-Feature-Pipeline
- [ ] Audio-Feature-Pipeline (optional)
- [ ] Feature-Selection

### Woche 3: Modell-Training
- [ ] Baseline-Modelle
- [ ] Hyperparameter-Tuning
- [ ] Ensemble-Building

### Woche 4: Integration & Testing
- [ ] API-Endpoints
- [ ] Batch-Processing Jobs
- [ ] Performance-Tests

## 🎨 Deliverables

1. **ML-Pipeline**: Vollständige Feature-Engineering und Training Pipeline
2. **API-Endpoints**: REST-API für Echtzeit-Klassifizierung
3. **Dashboard**: Visualisierung der Ergebnisse
4. **Dokumentation**: Technische Docs und Benutzerhandbuch
5. **Monitoring**: Alerting-System für schlechte Gespräche

## ⚠️ Risiken & Mitigationen

1. **Datenschutz**: 
   - Anonymisierung persönlicher Daten
   - On-Premise Deployment Option

2. **Bias im Modell**:
   - Regelmäßige Bias-Audits
   - Diverse Trainingsdaten

3. **Performance**:
   - Caching von Features
   - Asynchrone Verarbeitung

4. **Sprachvielfalt**:
   - Dialekte und Akzente berücksichtigen
   - Continuous Learning Pipeline

## 📅 Timeline

- **Woche 1-2**: Specification & Test Planning
- **Woche 3-4**: Implementation
- **Woche 5**: Testing & Evaluation
- **Woche 6**: Deployment & Monitoring

---

**Nächster Schritt**: Technische Spezifikation erstellen mit detaillierten Implementierungsdetails.