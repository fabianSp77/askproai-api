import { useState, useEffect, useCallback } from 'react';
import translationService from '../services/TranslationService';

// Translation hook for React components
export function useTranslation() {
    const [language, setLanguage] = useState(translationService.getCurrentLanguage());
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        // Subscribe to language changes
        const unsubscribe = translationService.addListener((newLang) => {
            setLanguage(newLang);
        });

        return unsubscribe;
    }, []);

    // Translate function with caching
    const t = useCallback(async (text, options = {}) => {
        if (!text) return text;
        
        // For immediate display, return original text and translate in background
        if (options.immediate !== false) {
            translationService.translate(text, options.targetLang, options.sourceLang)
                .then(() => {
                    // Force re-render if component is still mounted
                    setLanguage(prev => prev);
                });
            return text;
        }
        
        // Wait for translation
        return await translationService.translate(text, options.targetLang, options.sourceLang);
    }, []);

    // Batch translate
    const tBatch = useCallback(async (texts, options = {}) => {
        return await translationService.translateBatch(texts, options.targetLang, options.sourceLang);
    }, []);

    // Translate with debouncing
    const tDebounced = useCallback((text, options = {}) => {
        return translationService.translateDebounced(text, options.targetLang, options.sourceLang);
    }, []);

    // Change language
    const changeLanguage = useCallback(async (newLang) => {
        setIsLoading(true);
        try {
            const success = await translationService.setLanguage(newLang);
            if (success) {
                setLanguage(newLang);
            }
            return success;
        } finally {
            setIsLoading(false);
        }
    }, []);

    // Get supported languages
    const getLanguages = useCallback(() => {
        return translationService.getSupportedLanguages();
    }, []);

    return {
        t,
        tBatch,
        tDebounced,
        language,
        changeLanguage,
        getLanguages,
        isLoading
    };
}

// HOC for translating component props
export function withTranslation(Component, propsToTranslate = []) {
    return function TranslatedComponent(props) {
        const { t } = useTranslation();
        const [translatedProps, setTranslatedProps] = useState({});

        useEffect(() => {
            const translateProps = async () => {
                const newProps = {};
                for (const key of propsToTranslate) {
                    if (props[key] && typeof props[key] === 'string') {
                        newProps[key] = await t(props[key]);
                    }
                }
                setTranslatedProps(newProps);
            };

            translateProps();
        }, [props, t]);

        return <Component {...props} {...translatedProps} />;
    };
}