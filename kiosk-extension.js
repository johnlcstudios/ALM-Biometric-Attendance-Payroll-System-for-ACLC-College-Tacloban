/**
 * Kiosk Extension Utilities
 * Handles local caching and UI synchronization for the biometric terminal.
 */

const KioskExtension = {
    version: "1.2.0",
    
    /**
     * Caches verified employee data to reduce API calls during high-traffic hours.
     */
    cacheEmployee: (id, data) => {
        const cache = JSON.parse(localStorage.getItem('kiosk_cache') || '{}');
        cache[id] = {
            ...data,
            timestamp: Date.now()
        };
        localStorage.setItem('kiosk_cache', JSON.stringify(cache));
    },

    /**
     * Validates if the cached data is still fresh (less than 1 hour old).
     */
    isCacheValid: (id) => {
        const cache = JSON.parse(localStorage.getItem('kiosk_cache') || '{}');
        if (!cache[id]) return false;
        const oneHour = 60 * 60 * 1000;
        return (Date.now() - cache[id].timestamp) < oneHour;
    }
};

window.KioskExtension = KioskExtension;
