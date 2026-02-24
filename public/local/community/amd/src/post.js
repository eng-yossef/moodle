/**
 * Post details page logic for Moodle 5.1.
 *
 * @module     local_community/post
 * @copyright  2026 Youssef Khaled
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification'], function($, Notification) {

    let currentPostId = null;

    /**
     * Render the post details and answers into the container.
     *
     * @param {Object} data The post and answers data.
     */
    function render(data) {
        const {post, answers} = data;
        let html = `
            <div class="post-content mb-5">
                <h1 class="display-6 fw-bold text-dark">${post.title}</h1>
                <div class="d-flex align-items-center text-muted small mt-2 border-bottom pb-3">
                    <div class="me-3"><i class="fa fa-user me-1"></i> ${post.firstname} ${post.lastname}</div>
                    <div class="badge bg-light text-dark border">${post.votes} total votes</div>
                </div>
                <div class="mt-4 fs-5 text-dark">${post.content}</div>
            </div>
            <h4 class="fw-bold mb-4">${answers.length} Answers</h4>
        `;

        answers.forEach(a => {
            html += `
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body d-flex">
                        <div class="d-flex flex-column align-items-center me-4 bg-light rounded p-2"
                             style="height: fit-content; min-width: 50px;">
                            <button class="btn btn-link link-success p-0 vote-answer" data-id="${a.id}" data-value="1">
                                <i class="fa fa-chevron-up"></i>
                            </button>
                            <span class="fw-bold my-1">${a.votes}</span>
                            <button class="btn btn-link link-danger p-0 vote-answer" data-id="${a.id}" data-value="-1">
                                <i class="fa fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-2">${a.content}</div>
                            <div class="text-muted small">Answered by <strong>${a.firstname}</strong></div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#post-data-container').html(html);
    }

    /**
     * Refresh post data from the server.
     */
    function loadPost() {
        fetch(`${M.cfg.wwwroot}/local/community/ajax/get_post.php?id=${currentPostId}`)
            .then(res => res.json())
            .then(render)
            .catch(Notification.exception);
    }

    return {
        /**
         * Initialize the post page logic.
         *
         * @param {number} postid
         */
        init: function(postid) {
            currentPostId = postid;
            loadPost();

            $(document).on('click', '#addanswer', function(e) {
                e.preventDefault();

                // Force Moodle editors (Atto/TinyMCE) to sync content back to the textarea.
                if (window.tinyMCE) {
                    window.tinyMCE.triggerSave();
                }

                const content = $('#answercontent').val();

                if (!content || content.trim() === '') {
                    Notification.alert('Error', 'Please enter an answer.', 'OK');
                    return;
                }

                fetch(`${M.cfg.wwwroot}/local/community/ajax/create_answer.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({postid: currentPostId, content: content})
                })
                .then(() => {
                    // Reset the editor content.
                    $('#answercontent').val('');
                    if (window.tinyMCE && window.tinyMCE.activeEditor) {
                        window.tinyMCE.activeEditor.setContent('');
                    }
                    loadPost();
                    Notification.addNotification({message: 'Answer added!', type: 'success'});
                })
                .catch(Notification.exception);
            });

            $(document).on('click', '.vote-answer', function() {
                fetch(`${M.cfg.wwwroot}/local/community/ajax/vote.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        answerid: $(this).data('id'),
                        postid: currentPostId,
                        value: $(this).data('value')
                    })
                }).then(loadPost);
            });
        }
    };
});