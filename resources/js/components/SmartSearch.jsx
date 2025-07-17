import React, { useState, useEffect, useRef } from 'react';
import { 
    Input, 
    Tag, 
    Space, 
    Dropdown, 
    Card,
    Typography,
    Divider,
    Badge,
    Spin
} from 'antd';
import { 
    SearchOutlined,
    UserOutlined,
    PhoneOutlined,
    BranchesOutlined,
    CalendarOutlined,
    TagOutlined,
    FilterOutlined,
    ClockCircleOutlined
} from '@ant-design/icons';
import Fuse from 'fuse.js';

const { Text } = Typography;

const SmartSearch = ({ 
    onSearch, 
    placeholder = 'Intelligente Suche...',
    loading = false,
    branches = [],
    recentSearches = []
}) => {
    const [searchValue, setSearchValue] = useState('');
    const [suggestions, setSuggestions] = useState([]);
    const [filters, setFilters] = useState([]);
    const [dropdownVisible, setDropdownVisible] = useState(false);
    const inputRef = useRef(null);

    // Search operators and keywords
    const searchOperators = {
        'von:': { icon: <UserOutlined />, label: 'Von', type: 'from' },
        'an:': { icon: <PhoneOutlined />, label: 'An', type: 'to' },
        'filiale:': { icon: <BranchesOutlined />, label: 'Filiale', type: 'branch' },
        'datum:': { icon: <CalendarOutlined />, label: 'Datum', type: 'date' },
        'status:': { icon: <TagOutlined />, label: 'Status', type: 'status' },
        'dauer:': { icon: <ClockCircleOutlined />, label: 'Dauer', type: 'duration' },
    };

    const statusOptions = [
        'neu', 'in_bearbeitung', 'abgeschlossen', 'geplant', 'abgebrochen'
    ];

    const dateOptions = [
        { value: 'heute', label: 'Heute' },
        { value: 'gestern', label: 'Gestern' },
        { value: 'diese_woche', label: 'Diese Woche' },
        { value: 'letzte_woche', label: 'Letzte Woche' },
        { value: 'dieser_monat', label: 'Dieser Monat' },
        { value: 'letzter_monat', label: 'Letzter Monat' },
    ];

    // Parse search input to extract filters
    const parseSearchInput = (input) => {
        const extractedFilters = [];
        let remainingText = input;

        // Extract operator-based filters
        Object.entries(searchOperators).forEach(([operator, config]) => {
            const regex = new RegExp(`${operator}([^\\s]+)`, 'gi');
            const matches = [...input.matchAll(regex)];
            
            matches.forEach(match => {
                extractedFilters.push({
                    type: config.type,
                    value: match[1],
                    operator: operator,
                    label: `${config.label}: ${match[1]}`,
                    icon: config.icon
                });
                remainingText = remainingText.replace(match[0], '');
            });
        });

        return {
            filters: extractedFilters,
            searchText: remainingText.trim()
        };
    };

    // Generate suggestions based on input
    const generateSuggestions = (input) => {
        const suggestions = [];
        const lowerInput = input.toLowerCase();
        const lastWord = input.split(' ').pop().toLowerCase();

        // Suggest operators
        Object.entries(searchOperators).forEach(([operator, config]) => {
            if (operator.startsWith(lastWord) && lastWord.length > 0) {
                suggestions.push({
                    type: 'operator',
                    value: operator,
                    label: config.label,
                    icon: config.icon,
                    description: `Nach ${config.label} filtern`
                });
            }
        });

        // If typing after an operator, suggest values
        const currentOperator = Object.keys(searchOperators).find(op => 
            input.endsWith(op)
        );

        if (currentOperator) {
            const operatorConfig = searchOperators[currentOperator];
            
            switch (operatorConfig.type) {
                case 'branch':
                    branches.forEach(branch => {
                        suggestions.push({
                            type: 'value',
                            value: branch.name,
                            label: branch.name,
                            icon: <BranchesOutlined />,
                            operator: currentOperator
                        });
                    });
                    break;
                
                case 'status':
                    statusOptions.forEach(status => {
                        suggestions.push({
                            type: 'value',
                            value: status,
                            label: status,
                            icon: <TagOutlined />,
                            operator: currentOperator
                        });
                    });
                    break;
                
                case 'date':
                    dateOptions.forEach(option => {
                        suggestions.push({
                            type: 'value',
                            value: option.value,
                            label: option.label,
                            icon: <CalendarOutlined />,
                            operator: currentOperator
                        });
                    });
                    break;
            }
        }

        // Add recent searches if no specific suggestions
        if (suggestions.length === 0 && input.length === 0) {
            recentSearches.slice(0, 5).forEach(search => {
                suggestions.push({
                    type: 'recent',
                    value: search,
                    label: search,
                    icon: <ClockCircleOutlined />,
                    description: 'Letzte Suche'
                });
            });
        }

        return suggestions;
    };

    useEffect(() => {
        const suggestions = generateSuggestions(searchValue);
        setSuggestions(suggestions);
        setDropdownVisible(suggestions.length > 0);
    }, [searchValue, branches]);

    const handleSearch = () => {
        const { filters, searchText } = parseSearchInput(searchValue);
        setFilters(filters);
        onSearch({ text: searchText, filters });
        setDropdownVisible(false);
    };

    const handleSuggestionClick = (suggestion) => {
        let newValue = searchValue;
        
        if (suggestion.type === 'operator') {
            // Add operator to search
            const words = searchValue.split(' ');
            words[words.length - 1] = suggestion.value;
            newValue = words.join(' ');
        } else if (suggestion.type === 'value') {
            // Add value after operator
            newValue = searchValue + suggestion.value;
        } else if (suggestion.type === 'recent') {
            // Replace entire search with recent search
            newValue = suggestion.value;
        }
        
        setSearchValue(newValue);
        inputRef.current?.focus();
        
        // Auto-search for recent searches
        if (suggestion.type === 'recent') {
            handleSearch();
        }
    };

    const removeFilter = (filter) => {
        const newSearchValue = searchValue.replace(
            `${filter.operator}${filter.value}`,
            ''
        ).trim();
        setSearchValue(newSearchValue);
        handleSearch();
    };

    const renderSuggestion = (suggestion) => (
        <div
            className="suggestion-item"
            onClick={() => handleSuggestionClick(suggestion)}
            style={{ 
                padding: '8px 12px', 
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '8px'
            }}
            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f5f5f5'}
            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
        >
            {suggestion.icon}
            <div style={{ flex: 1 }}>
                <Text strong>{suggestion.label}</Text>
                {suggestion.description && (
                    <Text type="secondary" style={{ fontSize: 12, marginLeft: 8 }}>
                        {suggestion.description}
                    </Text>
                )}
            </div>
        </div>
    );

    const menu = {
        items: suggestions.map((suggestion, index) => ({
            key: index,
            label: renderSuggestion(suggestion),
        }))
    };

    return (
        <div style={{ position: 'relative' }}>
            <Space direction="vertical" style={{ width: '100%' }}>
                <Dropdown
                    menu={menu}
                    open={dropdownVisible}
                    onOpenChange={setDropdownVisible}
                    placement="bottomLeft"
                    overlayStyle={{ minWidth: 400 }}
                >
                    <Input
                        ref={inputRef}
                        size="large"
                        placeholder={placeholder}
                        prefix={loading ? <Spin size="small" /> : <SearchOutlined />}
                        value={searchValue}
                        onChange={(e) => setSearchValue(e.target.value)}
                        onPressEnter={handleSearch}
                        allowClear
                        style={{ width: '100%' }}
                    />
                </Dropdown>
                
                {filters.length > 0 && (
                    <Space wrap>
                        {filters.map((filter, index) => (
                            <Tag
                                key={index}
                                closable
                                onClose={() => removeFilter(filter)}
                                icon={filter.icon}
                            >
                                {filter.label}
                            </Tag>
                        ))}
                    </Space>
                )}
            </Space>
            
            <div 
                style={{ 
                    position: 'absolute',
                    top: '100%',
                    left: 0,
                    marginTop: 8,
                    opacity: 0.7,
                    fontSize: 12
                }}
            >
                <Text type="secondary">
                    Tipp: Verwenden Sie "von:", "filiale:", "status:" für erweiterte Filter
                </Text>
            </div>
        </div>
    );
};

export default SmartSearch;