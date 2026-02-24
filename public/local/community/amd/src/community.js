/**
 * Global Community AMD module.
 *
 * @module     local_community/community
 * @copyright  2026 Youssef Khaled
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    /**
     * Initialize the community page.
     *
     * @returns {void}
     */
    function init() {

        loadPosts();

        /**
         * Load all posts from backend.
         *
         * @returns {void}
         */
        function loadPosts() {

            fetch(M.cfg.wwwroot + '/local/community/ajax/get_posts.php')
                .then(function(res) {
                    return res.json();
                })
                .then(function(posts) {

                    let html = '<button id="ask">Ask Question</button>';

                    posts.forEach(function(p) {
                        html += `
                            <div class="post">
                                <h3>
                                <a href="${M.cfg.wwwroot}/local/community/pages/post.php?id=${p.id}">
                                ${p.title}
                                </a>
                                </h3>
                                <p>${p.firstname} ${p.lastname}</p>
                            </div>
                        `;
                    });

                    $('#community-app').html(html);
                });
        }

        /**
         * Handle creating a new post.
         *
         * @returns {void}
         */
        $(document).on('click', '#ask', function() {

            const title = prompt('Title');
            const content = prompt('Content');

            fetch(M.cfg.wwwroot + '/local/community/ajax/create_post.php', {
                method: 'POST',
                body: JSON.stringify({
                    title: title,
                    content: content,
                    posttype: 'question'
                })
            }).then(function() {
                loadPosts();
            });

        });

    }

    return {
        init: init
    };

});