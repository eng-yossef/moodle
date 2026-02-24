/**
 * Global Community AMD module for Moodle 5.1.
 *
 * @module     local_community/community
 * @copyright  2026 Youssef Khaled
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/modal_factory',
    'core/modal_events',
    'core/notification'
], function($, ModalFactory, ModalEvents, Notification) {

    const Selectors = {
        container: '#community-app',
        askBtn: '#ask-question-btn'
    };

    /**
     * Render the post list using Bootstrap 5 Card styling.
     *
     * @param {Array} posts Array of post objects from the backend.
     */
    function renderPosts(posts) {
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 fw-bold text-dark">Community Discussions</h2>
                <button id="ask-question-btn" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="fa fa-plus me-2"></i>Ask Question
                </button>
            </div>
            <div class="row" id="posts-grid">
        `;

        if (posts.length === 0) {
            html += `
                <div class="col-12 text-center py-5">
                    <div class="alert alert-light border-dashed">
                        No discussions yet. Start the conversation!
                    </div>
                </div>`;
        }

        posts.forEach(p => {
            html += `
                <div class="col-12 mb-3">
                    <div class="card h-100 border-0 shadow-sm transition">
                        <div class="card-body d-flex align-items-center">
                            <div class="vote-count text-center me-4 border-end pe-4" style="min-width: 80px;">
                                <span class="d-block h4 mb-0 fw-bold text-primary">${p.votes}</span>
                                <small class="text-muted text-uppercase small fw-semibold">Votes</small>
                            </div>
                            <div class="post-content">
                                <h5 class="card-title mb-1">
                                    <a href="${M.cfg.wwwroot}/local/community/pages/post.php?id=${p.id}"
                                       class="text-decoration-none text-dark stretched-link">
                                        ${p.title}
                                    </a>
                                </h5>
                                <p class="card-text small text-muted mb-0">
                                    <span class="me-2">
                                        <i class="fa fa-user-circle me-1"></i>${p.firstname} ${p.lastname}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += `</div>`;
        $(Selectors.container).html(html);
    }

    /**
     * Fetch posts from the server.
     */
    function loadPosts() {
        fetch(`${M.cfg.wwwroot}/local/community/ajax/get_posts.php`)
            .then(res => res.json())
            .then(res => {
                renderPosts(res);
            }).catch(Notification.exception);
    }

    /**
     * Create and initialize the Modal for asking questions.
     */
    function initModal() {
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: 'Ask a New Question',
            body: `
                <div class="p-2">
                    <div class="mb-3">
                        <label for="post-title" class="form-label fw-bold">Title</label>
                        <input type="text" class="form-control shadow-none" id="post-title"
                               placeholder="e.g. How do I use the new Moodle 5.1 Gradebook?">
                    </div>
                    <div class="mb-3">
                        <label for="post-content" class="form-label fw-bold">Details</label>
                        <textarea class="form-control shadow-none" id="post-content" rows="5"
                                  placeholder="Explain your question in detail..."></textarea>
                    </div>
                </div>
            `,
            buttons: {save: 'Post Question'}
        }).then(modal => {
            $(document).on('click', Selectors.askBtn, e => {
                e.preventDefault();
                modal.show();
            });

            modal.getRoot().on(ModalEvents.save, e => {
                const title = $('#post-title').val();
                const content = $('#post-content').val();

                if (!title || !content) {
                    e.preventDefault();
                    Notification.addNotification({
                        message: 'Both fields are required',
                        type: 'error'
                    });
                    return;
                }

                submitPost(title, content, modal);
            });
        });
    }

    /**
     * Submit the post data to the server.
     *
     * @param {string} title The title of the post.
     * @param {string} content The body of the post.
     * @param {Object} modal The modal instance.
     */
    function submitPost(title, content, modal) {
        fetch(`${M.cfg.wwwroot}/local/community/ajax/create_post.php`, {
            method: 'POST',
            body: JSON.stringify({title, content, posttype: 'question'})
        }).then(() => {
            modal.hide();
            loadPosts();
            Notification.addNotification({
                message: 'Question posted!',
                type: 'success'
            });
        });
    }

    return {
        init: () => {
            loadPosts();
            initModal();
        }
    };
});