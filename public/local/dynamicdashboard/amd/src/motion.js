define(function() {
    var prefersReducedMotion = false;

    return {
        init: function() {
            var mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            prefersReducedMotion = mediaQuery.matches;
            // Listen for changes (unlikely, but possible)
            mediaQuery.addEventListener('change', function(e) {
                prefersReducedMotion = e.matches;
            });
        },
        shouldAnimate: function() {
            return !prefersReducedMotion;
        }
    };
});