// local/dynamicdashboard/amd/src/dashboard.js
define(
    [
        'jquery',
        'core/ajax',
        'local_dynamicdashboard/motion',
        'local_dynamicdashboard/responsive',
        'local_dynamicdashboard/widget_loader'
    ],
    function($, Ajax, Motion, Responsive, WidgetLoader) {

        var refreshTimer;
        var refreshInterval = 30; // seconds, overridden by config

        /**
         * Initialize all widgets on the page.
         */
        function initWidgets() {
            $('.dynamic-widget').each(function() {
                var widget = $(this);
                var type = widget.data('widget-type');
                var id = widget.data('widget-id');
                WidgetLoader.load(type, widget, id);
            });
        }

        /**
         * Start polling for widget updates.
         * @param {number} interval Refresh interval in seconds.
         */
        function startPolling(interval) {
            refreshInterval = interval || refreshInterval;
            stopPolling();
            refreshTimer = setInterval(fetchUpdates, refreshInterval * 1000);
        }

        /**
         * Stop polling for updates.
         */
        function stopPolling() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
                refreshTimer = null;
            }
        }

        /**
         * Fetch updates from the server.
         */
        function fetchUpdates() {
            if (document.visibilityState !== 'visible') {
                return;
            }

            var promises = Ajax.call([{
                methodname: 'local_dynamicdashboard_get_widgets',
                args: {widgetids: [], since: 0}
            }]);

            promises[0].done(function(response) {
                if (response.widgets) {
                    response.widgets.forEach(function(widget) {
                        var selector =
                            '.dynamic-widget[data-widget-id="' + widget.id + '"]';

                        var $widget = $(selector);

                        if ($widget.length) {
                            var data = JSON.parse(widget.data);
                            $widget.trigger('widget:update', [data]);
                        }
                    });
                }
            }).fail(function(error) {
                window.console.log('Dashboard update failed:', error);
            });
        }

        /**
         * Initialize dashboard.
         * @param {Object} params Configuration parameters.
         */
        function init(params) {
            if (params.refreshInterval) {
                refreshInterval = params.refreshInterval;
            }

            Motion.init();
            Responsive.init();
            initWidgets();
            startPolling(refreshInterval);
        }

        return {
            init: init
        };
    }
);