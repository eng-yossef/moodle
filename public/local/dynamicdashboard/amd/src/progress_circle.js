define(['jquery', 'local_dynamicdashboard/motion'], function($, Motion) {
    return {
        /**
         * Initialize progress circle widget.
         *
         * @param {jQuery} $element Widget container
         */
        init: function($element) {

            var $circle = $element.find('.progress-fill');

            var radius = parseFloat($circle.attr('r')) || 0;
            var circumference = 2 * Math.PI * radius;

            $circle.css({
                'stroke-dasharray': circumference,
                'stroke-dashoffset': circumference
            });

            var percent = parseFloat($circle.data('percent')) || 0;
            animate(percent);

            $element.on('widget:update', function(_e, data) {
                if (data && data.percent !== undefined) {
                    animate(parseFloat(data.percent));
                }
            });

            /**
             * Animate progress circle to target percentage.
             *
             * @param {number} targetPercent Target percentage (0-100)
             */
            function animate(targetPercent) {

                var targetOffset =
                    circumference - (targetPercent / 100) * circumference;

                if (Motion &&
                    Motion.shouldAnimate &&
                    Motion.shouldAnimate()
                ) {
                    $circle.animate(
                        { 'stroke-dashoffset': targetOffset },
                        800
                    );
                } else {
                    $circle.css('stroke-dashoffset', targetOffset);
                }

                $element.find('.progress-text')
                    .text(targetPercent + '%');
            }
        }
    };
});