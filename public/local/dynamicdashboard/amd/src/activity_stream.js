define(['jquery', 'local_dynamicdashboard/motion'], function($, Motion) {
    return {
        init: function($element) {
            var $list = $element.find('.events-list');

            $element.on('widget:update', function(e, data) {
                if (!data.events) {
                    return;
                }

                // Replace list with new events.
                var html = '';
                data.events.forEach(function(ev) {
                    html += '<li class="event-item">' +
                                '<span class="event-time">' + ev.time + '</span>' +
                                '<span class="event-desc">' + ev.description + '</span>' +
                            '</li>';
                });

                if (Motion.shouldAnimate()) {
                    $list.fadeOut(200, function() {
                        $list.html(html).fadeIn(200);
                    });
                } else {
                    $list.html(html);
                }
            });
        }
    };
});