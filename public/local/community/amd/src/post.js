/**
 * Post details page logic.
 *
 * @module local_community/post
 */
define(['jquery'], function($) {

    /**
     * Initialize the post page.
     *
     * Loads the post details, answers, and sets up event handlers
     * for adding answers and voting.
     *
     * @param {number} postid The ID of the post to load.
     */
    function init(postid) {
        loadPost();

        /**
         * Fetch and render the post details and answers.
         *
         * Calls backend endpoint to get post data and renders HTML.
         */
        function loadPost() {
            fetch(M.cfg.wwwroot + '/local/community/ajax/get_post.php?id=' + postid)
                .then(res => res.json())
                .then(function(data) {
                    let html = `
                        <h2>${data.post.title}</h2>
                        <p>${data.post.content}</p>
                        <div class="votes">${data.post.votes} votes</div>
                        <hr>
                        <h3>Answers</h3>
                    `;

                    data.answers.forEach(function(a) {
                        html += `
                            <div class="answer">
                                <p>${a.content}</p>
                                <small>${a.firstname} ${a.lastname}</small>
                                <div class="votes">${a.votes}</div>
                                <button class="vote-answer" data-id="${a.id}" data-value="1">▲</button>
                                <button class="vote-answer" data-id="${a.id}" data-value="-1">▼</button>
                            </div>
                        `;
                    });

                    $('#post-app').html(html);
                });
        }

        /**
         * Handle add answer button click.
         *
         * Reads content from the editor textarea and posts it to backend.
         */
        $(document).on('click', '#addanswer', function() {
            const content = $('#answercontent').val(); // Atto/TinyMCE keeps textarea synced

            fetch(M.cfg.wwwroot + '/local/community/ajax/create_answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    postid: postid,
                    content: content
                })
            }).then(loadPost);
        });

        /**
         * Handle vote button click.
         *
         * Sends vote value to backend for the selected answer.
         */
        $(document).on('click', '.vote-answer', function() {
            fetch(M.cfg.wwwroot + '/local/community/ajax/vote.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    answerid: $(this).data('id'),
                    postid: postid,
                    value: $(this).data('value')
                })
            });
        });
    }

    return { init: init };
});
