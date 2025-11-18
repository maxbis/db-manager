/**
 * State Management Module
 * Manages global application state
 */

const State = {
    currentDatabase: '',
    databases: [],
    dbSearchQuery: '',
    dbSortMode: 'name_asc',
    selectedTable: ''
};

// Export for use in other modules
if (typeof window !== 'undefined') {
    window.State = State;
}

