/**
 * Global Community AMD module.
 *
 * @module     local_community/community
 * @copyright  2026 Youssef Khaled
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/modal_factory',
    'core/modal_events',
    'core/notification',
    'core/ajax'
], function($, ModalFactory, ModalEvents, Notification, Ajax) {

    /**
     * Internal state and selectors.
     */
    const Selectors = {
        container: '#community-app',
        askBtn: '#ask-question-btn'
    };

    /**
     * Render the post list using Bootstrap Card styling.
     * * @param {Array} posts 
     */
    function renderPosts(posts) {
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4">Community Discussions</h2>
                <button id="ask-question-btn" class="btn btn-primary">
                    <i class="fa fa-plus-circle"></i> Ask Question
                </button>
            </div>
            <div class="community-posts-list">
        `;

        if (posts.length === 0) {
            html += `<div class="alert alert-info">No questions asked yet. Be the first!</div>`;
        }

        posts.forEach(function(p) {
            html += `
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <h5 class="card-title mb-0">
                                <a href="${M.cfg.wwwroot}/local/community/pages/post.php?id=${p.id}" class="text-decoration-none">
                                    ${p.title}
                                </a>
                            </h5>
                            <span class="badge badge-pill badge-light border">${p.votes} votes</span>
                        </div>
                        <p class="card-text text-muted small">
                            <i class="fa fa-user-circle"></i> ${p.firstname} ${p.lastname}
                        </p>
                    </div>
                </div>
            `;
        });

        html += `</div>`;
        $(Selectors.container).html(html);
    }

    /**
     * Load posts via AJAX.
     */
    function loadPosts() {
        fetch(M.cfg.wwwroot + '/local/community/ajax/get_posts.php')
            .then(res => res.json())
            .then(posts => renderPosts(posts))
            .catch(Notification.exception);
    }

    /**
     * Create the Modal Form.
     */
    function initModal() {
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: 'Ask a New Question',
            body: `
                <form id="add-post-form">
                    <div class="form-group">
                        <label for="post-title">Question Title</label>
                        <input type="text" class="form-control" id="post-title" placeholder="What's on your mind?" required>
                    </div>
                    <div class="form-group mt-3">
                        <label for="post-content">Description</label>
                        <textarea class="form-control" id="post-content" rows="4" required></textarea>
                    </div>
                </form>
            `,
            buttons: {
                save: 'Post Question'
            }
        }).then(function(modal) {
            const root = modal.getRoot();

            // Handle the click on our custom "Ask" button to show modal
            $(document).on('click', Selectors.askBtn, function(e) {
                e.preventDefault();
                modal.show();
            });

            // Handle Save button click
            root.on(ModalEvents.save, function(e) {
                e.preventDefault();
                
                const title = root.find('#post-title').val();
                const content = root.find('#post-content').val();

                if (!title || !content) {
                    Notification.alert('Error', 'Please fill in all fields', 'OK');
                    return;
                }

                submitPost(title, content, modal);
            });
        });
    }

    /**
     * Submit the post to the server.
     */
    function submitPost(title, content, modal) {
        fetch(M.cfg.wwwroot + '/local/community/ajax/create_post.php', {
            method: 'POST',
            body: JSON.stringify({
                title: title,
                content: content,
                posttype: 'question'
            })
        })
        .then(() => {
            modal.hide();
            // Clear inputs for next time
            modal.getRoot().find('#add-post-form')[0].reset();
            loadPosts();
            Notification.addNotification({
                message: 'Your question was posted successfully!',
                type: 'success'
            });
        })
        .catch(Notification.exception);
    }

    return {
        init: function() {
            loadPosts();
            initModal();
        }
    };
});