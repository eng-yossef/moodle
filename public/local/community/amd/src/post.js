/**
 * Post details page logic.
 *
 * @module     local_community/post
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
         */
        function loadPost() {
            fetch(M.cfg.wwwroot + '/local/community/ajax/get_post.php?id=' + postid)
                .then(res => res.json())
                .then(function(data) {
                    let html = `
                        <h2>${data.post.title}</h2>
                        <p>${data.post.content}</p>
                        <hr>
                        <h3>Answers</h3>
                    `;

                    data.answers.forEach(function(a) {
                        html += `
                            <div class="answer">
                                <p>${a.content}</p>
                                <small>${a.firstname} ${a.lastname}</small>
                                <button class="vote-answer" data-id="${a.id}" data-value="1">▲</button>
                                <button class="vote-answer" data-id="${a.id}" data-value="-1">▼</button>
                            </div>
                        `;
                    });

                    html += `
                        <textarea id="answercontent"></textarea>
                        <button id="addanswer">Add Answer</button>
                    `;

                    $('#post-app').html(html);
                });
        }

        // Add answer event
        $(document).on('click', '#addanswer', function() {
            const content = $('#answercontent').val();

            fetch(M.cfg.wwwroot + '/local/community/ajax/create_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    postid: postid,
                    content: content
                })
            }).then(loadPost);
        });

        // Vote answer event
        $(document).on('click', '.vote-answer', function() {
            fetch(M.cfg.wwwroot + '/local/community/ajax/vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    answerid: $(this).data('id'),
                    value: $(this).data('value')
                })
            });
        });

    }

    return {
        init: init
    };

});
