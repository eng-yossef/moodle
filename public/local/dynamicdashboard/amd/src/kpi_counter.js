define(['jquery', 'local_dynamicdashboard/motion'], function($, Motion) {
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