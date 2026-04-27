define(['jquery'], function($) {
    return {
        init: function() {
            var grid = $('#widget-grid');
            var updateLayout = function() {
                var width = $(window).width();
                if (width < 768) {
                    grid.addClass('mobile-layout').removeClass('tablet-layout desktop-layout');
                } else if (width < 1200) {
                    grid.addClass('tablet-layout').removeClass('mobile-layout desktop-layout');
                } else {
                    grid.addClass('desktop-layout').removeClass('mobile-layout tablet-layout');
                }
            };
            $(window).on('resize', updateLayout);
            updateLayout();
        }
    };
});