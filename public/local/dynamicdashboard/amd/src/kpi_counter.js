define(['jquery', 'local_dynamicdashboard/motion'], function($, Motion) {
    /**
     * Animate a numeric value from start to end over a duration.
     * @param {jQuery} $element Element to update.
     * @param {number} start Starting value.
     * @param {number} end Target value.
     * @param {number} duration Animation duration in ms.
     */
    function animateValue($element, start, end, duration) {
        if (start === end) {
            $element.text(end);
            return;
        }

        const range = end - start;
        const startTime = performance.now();

        /**
         * Animation frame callback that updates the element value.
         * @param {number} timestamp - Current timestamp from requestAnimationFrame.
         */
        function step(timestamp) {
            const progress = Math.min((timestamp - startTime) / duration, 1);
            // Ease-out quadratic for smoother feel
            const eased = 1 - Math.pow(1 - progress, 2);
            const current = Math.floor(start + (range * eased));
            $element.text(current);

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                $element.text(end); // Ensure exact final value
            }
        }

        requestAnimationFrame(step);
    }

    return {
        /**
         * Initialize KPI counter widget.
         * @param {jQuery} $element Root element.
         */
        init: function($element) {
            var $value = $element.find('.kpi-value');
            var currentVal = Number($value.text()) || 0;

            $element.on('widget:update', function(e, data) {
                if (!data || data.value === undefined) {
                    return;
                }

                var targetVal = Number(data.value) || 0;

                if (Motion.shouldAnimate() && targetVal !== currentVal) {
                    animateValue($value, currentVal, targetVal, 600);
                } else {
                    $value.text(targetVal);
                }

                currentVal = targetVal;
            });
        }
    };
});