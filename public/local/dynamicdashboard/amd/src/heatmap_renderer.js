define([], function() {
    return {
        init: function($element) {

            $element.on('widget:update', function() {
                // Heatmap update placeholder
                // Re-render grid here when needed
            });

        }
    };
});