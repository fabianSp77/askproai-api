import React, { useState, useEffect } from 'react';
import { Select, Space, Spin } from 'antd';
import { GlobalOutlined } from '@ant-design/icons';
import { useTranslation } from '../hooks/useTranslation';

const LanguageSelector = ({ placement = 'bottomRight', showLabel = true }) => {
    const { language, changeLanguage, getLanguages, isLoading } = useTranslation();
    const [languages, setLanguages] = useState({});

    useEffect(() => {
        const langs = getLanguages();
        setLanguages(langs);
    }, [getLanguages]);

    const handleChange = async (value) => {
        await changeLanguage(value);
    };

    const options = Object.entries(languages).map(([code, name]) => ({
        value: code,
        label: (
            <Space>
                <span className="language-flag">{getFlagEmoji(code)}</span>
                <span>{name}</span>
            </Space>
        ),
    }));

    return (
        <Space>
            {showLabel && <GlobalOutlined />}
            <Select
                value={language}
                onChange={handleChange}
                options={options}
                loading={isLoading}
                disabled={isLoading}
                style={{ minWidth: 120 }}
                placement={placement}
                notFoundContent={isLoading ? <Spin size="small" /> : null}
            />
        </Space>
    );
};

// Helper function to get flag emoji for language code
function getFlagEmoji(languageCode) {
    const flags = {
        'de': '🇩🇪',
        'en': '🇬🇧',
        'es': '🇪🇸',
        'fr': '🇫🇷',
        'it': '🇮🇹',
        'nl': '🇳🇱',
        'pl': '🇵🇱',
        'pt': '🇵🇹',
        'ru': '🇷🇺',
        'ja': '🇯🇵',
        'zh': '🇨🇳',
        'tr': '🇹🇷',
    };
    return flags[languageCode] || '🌐';
}

export default LanguageSelector;