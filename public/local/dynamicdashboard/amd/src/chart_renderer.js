define(['jquery', 'local_dynamicdashboard/chartjs', 'local_dynamicdashboard/motion'], function($, Chart, Motion) {
    var instances = {};

    return {
        init: function($element, id) {
            // Read initial config from data attributes
            var canvas = $element.find('canvas')[0];
            var type = canvas.getAttribute('data-type');
            var labels = canvas.getAttribute('data-labels').split(',').filter(Boolean);
            var datasets = JSON.parse(canvas.getAttribute('data-datasets'));

            var ctx = canvas.getContext('2d');
            var chart = new Chart(ctx, {
                type: type,
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: Motion.shouldAnimate() ? 500 : 0 }
                }
            });
            instances[id] = chart;

            // Update handler
            $element.on('widget:update', function(e, data) {
                if (!data || !data.datasets) {return;}
                var ch = instances[id];
                ch.data.labels = data.labels;
                ch.data.datasets = data.datasets;
                ch.update(Motion.shouldAnimate() ? 'active' : 'none');
            });
        }
    };
});