/**
 * Page Builder - Core JavaScript
 * Zapmatic v7.11.20
 */
(function() {
    'use strict';
    
    // Load SortableJS dynamically if needed
    function loadSortable(callback) {
        if (typeof Sortable !== 'undefined') {
            callback();
            return;
        }
        var s = document.createElement('script');
        s.src = document.querySelector('meta[name="base-url"]') 
            ? document.querySelector('meta[name="base-url"]').content + 'inc/core/Page_builder/Assets/js/sortable.min.js'
            : '/inc/core/Page_builder/Assets/js/sortable.min.js';
        s.onload = callback;
        document.head.appendChild(s);
    }
    
    window.PageBuilder = {
        loadSortable: loadSortable
    };
})();
