import axios from 'axios';

class TranslationService {
    constructor() {
        this.cache = new Map();
        this.currentLanguage = 'de';
        this.autoTranslate = true;
        this.batchTimeout = null;
        this.batchQueue = [];
        this.listeners = [];
    }

    // Initialize the service
    async init() {
        try {
            const response = await axios.get('/business/api/translate/languages');
            this.currentLanguage = response.data.current_language || 'de';
            this.supportedLanguages = response.data.languages;
            this.notifyListeners();
        } catch (error) {
            console.error('Failed to initialize translation service:', error);
        }
    }

    // Add a listener for language changes
    addListener(callback) {
        this.listeners.push(callback);
        return () => {
            this.listeners = this.listeners.filter(cb => cb !== callback);
        };
    }

    // Notify all listeners of language change
    notifyListeners() {
        this.listeners.forEach(callback => callback(this.currentLanguage));
    }

    // Get current language
    getCurrentLanguage() {
        return this.currentLanguage;
    }

    // Get supported languages
    getSupportedLanguages() {
        return this.supportedLanguages || {};
    }

    // Update language preference
    async setLanguage(language) {
        try {
            const response = await axios.post('/business/api/translate/preference', {
                language,
                auto_translate: this.autoTranslate
            });
            
            if (response.data.success) {
                this.currentLanguage = language;
                this.cache.clear(); // Clear cache when language changes
                this.notifyListeners();
                return true;
            }
        } catch (error) {
            console.error('Failed to update language preference:', error);
            return false;
        }
    }

    // Translate a single text
    async translate(text, targetLang = null, sourceLang = null) {
        if (!text || typeof text !== 'string') {
            return text;
        }

        // Use current language if not specified
        targetLang = targetLang || this.currentLanguage;

        // Check cache
        const cacheKey = `${text}-${targetLang}-${sourceLang || 'auto'}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await axios.post('/business/api/translate', {
                text,
                target_lang: targetLang,
                source_lang: sourceLang
            });

            const translated = response.data.translated;
            this.cache.set(cacheKey, translated);
            return translated;
        } catch (error) {
            console.error('Translation failed:', error);
            return text; // Return original text on error
        }
    }

    // Batch translate multiple texts
    async translateBatch(texts, targetLang = null, sourceLang = null) {
        if (!Array.isArray(texts)) {
            return texts;
        }

        targetLang = targetLang || this.currentLanguage;

        try {
            const response = await axios.post('/business/api/translate/batch', {
                texts,
                target_lang: targetLang,
                source_lang: sourceLang
            });

            return response.data.translations;
        } catch (error) {
            console.error('Batch translation failed:', error);
            return texts; // Return original texts on error
        }
    }

    // Detect language of text
    async detectLanguage(text) {
        try {
            const response = await axios.post('/business/api/translate/detect', { text });
            return response.data.detected_language;
        } catch (error) {
            console.error('Language detection failed:', error);
            return null;
        }
    }

    // Translate with debouncing (useful for real-time translation)
    translateDebounced(text, targetLang = null, sourceLang = null) {
        return new Promise((resolve) => {
            // Clear existing timeout
            if (this.batchTimeout) {
                clearTimeout(this.batchTimeout);
            }

            // Add to batch queue
            this.batchQueue.push({ text, targetLang, sourceLang, resolve });

            // Set new timeout
            this.batchTimeout = setTimeout(async () => {
                const queue = [...this.batchQueue];
                this.batchQueue = [];

                if (queue.length === 1) {
                    // Single item, translate directly
                    const item = queue[0];
                    const result = await this.translate(item.text, item.targetLang, item.sourceLang);
                    item.resolve(result);
                } else {
                    // Multiple items, use batch translation
                    const texts = queue.map(item => item.text);
                    const results = await this.translateBatch(texts, targetLang, sourceLang);
                    
                    queue.forEach((item, index) => {
                        item.resolve(results[index] || item.text);
                    });
                }
            }, 300); // 300ms debounce
        });
    }

    // Helper function to translate React component props
    translateProps(props, keys = []) {
        const translated = { ...props };
        
        keys.forEach(key => {
            if (props[key] && typeof props[key] === 'string') {
                this.translate(props[key]).then(result => {
                    translated[key] = result;
                });
            }
        });
        
        return translated;
    }

    // Clear translation cache
    clearCache() {
        this.cache.clear();
    }
}

// Create singleton instance
const translationService = new TranslationService();

// Initialize on load
if (typeof window !== 'undefined') {
    translationService.init();
}

export default translationService;