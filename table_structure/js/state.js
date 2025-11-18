/**
 * State Management Module
 * Manages global application state
 */

const State = {
    currentTable: '',
    tableInfo: null,
    currentEditColumn: null
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.State = State;
}

