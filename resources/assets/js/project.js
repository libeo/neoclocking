// Initialize project
var CLOCKING = window.CLOCKING = {};

// Project settings
CLOCKING.Settings = {
    Classes: {
        active: 'is-active',
        open: 'is-open',
        hover: 'is-hover',
        clicked: 'is-clicked',
        extern: 'is-external',
        error: 'is-error',
        a11y: 'l-a11y',
        zoom: 'l-zoomed',
        font: 'l-font'
    }
};

// Project components
CLOCKING.Components = {};

// Project Vues
CLOCKING.Vues = {};

CLOCKING.editing = 0;
CLOCKING.adding = 0;

// Export project
module.exports = CLOCKING;
