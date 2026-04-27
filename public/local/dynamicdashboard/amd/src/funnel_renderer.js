define(['jquery'], function($) {
    return {
        init: function($element) {

            $element.on('widget:update', function(_e, data) {
                var steps = $element.find('.funnel-step');

                if (data && data.steps) {
                    data.steps.forEach(function(step, i) {
                        $(steps[i]).css('width', step.percent + '%');
                    });
                }
            });

        }
    };
});