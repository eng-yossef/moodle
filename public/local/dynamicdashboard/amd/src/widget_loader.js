define([], function() {

    var loadedModules = {};

    /**
     * Registry of widget types → module paths.
     * @type {Object<string, string>}
     */
    var registry = {
    kpi_counter: 'local_dynamicdashboard/kpi_counter',
    kpicounter: 'local_dynamicdashboard/kpi_counter',

    activity_stream: 'local_dynamicdashboard/activity_stream',
    activitystream: 'local_dynamicdashboard/activity_stream',

    chart: 'local_dynamicdashboard/chart_renderer'
};

    /**
     * Load widget module dynamically.
     * @param {string} type Widget type.
     * @returns {Promise|null} Loaded module promise.
     */
    function loadModule(type) {
        var moduleName = registry[type];

        if (!moduleName) {
            return null;
        }

        if (!loadedModules[moduleName]) {
            loadedModules[moduleName] = new Promise(function(resolve, reject) {
                require(
                    [moduleName],
                    function(mod) {
                        resolve(mod);
                    },
                    function(err) {
                        reject(err);
                    }
                );
            });
        }

        return loadedModules[moduleName];
    }

    return {
        /**
         * Load and initialize widget.
         * @param {string} type Widget type.
         * @param {jQuery} $container Widget container.
         * @param {number|string} widgetId Widget ID.
         */
        load: function(type, $container, widgetId) {
            if (!type) {
                return;
            }

            var module = loadModule(type);

            // 🔥 FIX: prevent calling .then() on null
            if (!module) {
                window.console.warn(
                    'DynamicDashboard: Unknown widget type:',
                    type
                );
                return;
            }

            module.then(function(Widget) {
                if (Widget && Widget.init) {
                    Widget.init($container, widgetId);
                } else {
                    window.console.warn(
                        'DynamicDashboard: Invalid module for type:',
                        type
                    );
                }
            }).catch(function(err) {
                window.console.error(
                    'DynamicDashboard: Failed to load module:',
                    type,
                    err
                );
            });
        }
    };
});