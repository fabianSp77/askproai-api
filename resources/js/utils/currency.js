/**
 * Safely format a currency value
 * @param {*} value - The value to format
 * @param {number} decimals - Number of decimal places (default: 2)
 * @returns {string} - Formatted currency string
 */
export const formatCurrency = (value, decimals = 2) => {
    // Convert to number, handling various edge cases
    const numValue = parseFloat(value) || 0;
    
    // Ensure we have a valid number
    if (isNaN(numValue)) {
        return '0.00';
    }
    
    return numValue.toFixed(decimals);
};

/**
 * Safely get a numeric value from a potentially nested object
 * @param {*} obj - The object to get the value from
 * @param {string} path - Dot-separated path to the value
 * @param {number} defaultValue - Default value if not found
 * @returns {number} - The numeric value
 */
export const getNumericValue = (obj, path, defaultValue = 0) => {
    if (!obj || !path) return defaultValue;
    
    const keys = path.split('.');
    let value = obj;
    
    for (const key of keys) {
        if (value && typeof value === 'object' && key in value) {
            value = value[key];
        } else {
            return defaultValue;
        }
    }
    
    return parseFloat(value) || defaultValue;
};