/**
 * Utility Functions Module
 * Contains helper functions used throughout the application
 */

const Utils = {
    /**
     * Debounce helper function
     */
    debounce: function(fn, delay = 250) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    },

    /**
     * Format bytes to human readable format
     */
    formatBytes: function(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    /**
     * Show toast notification
     */
    showToast: function(message, type = 'success') {
        const toast = $('#toast');
        toast.text(message);
        toast.removeClass('success error warning');
        toast.addClass(type);
        toast.addClass('active');

        setTimeout(function () {
            toast.removeClass('active');
        }, 4000);
    }
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.Utils = Utils;
    // Also expose showToast globally for inline handlers
    window.showToast = Utils.showToast;
}

