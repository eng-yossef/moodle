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
        askBtn: '#ask-question-btn',
        searchInput: '#community-search',
        sortSelect: '#community-sort',
        leaderboardFilter: '#leaderboard-filter'
    };

    /**
     * Escape user-controlled values before injecting HTML.
     *
     * @param {string} value
     * @returns {string}
     */
    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    /**
     * Render the page shell.
     */
    function renderLayout() {
        const html = `
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="h4 fw-bold text-dark mb-1">Community Discussions</h2>
                    <small class="text-muted">Ask questions, upvote strong answers, and grow your reputation.</small>
                </div>
                <button id="ask-question-btn" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="fa fa-plus me-2"></i>Ask Question
                </button>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body d-flex flex-column flex-md-row gap-3">
                    <input id="community-search" class="form-control" type="search" placeholder="Search by title...">
                    <select id="community-sort" class="form-select" style="max-width: 260px;">
                        <option value="recent">Newest first</option>
                        <option value="votes">Top voted</option>
                        <option value="answers">Most answered</option>
                    </select>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div id="posts-grid"></div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h5 mb-0">Leaderboard</h3>
                                <select id="leaderboard-filter" class="form-select form-select-sm" style="max-width: 160px;">
                                    <option value="all">All time</option>
                                    <option value="30d">Last 30 days</option>
                                    <option value="7d">Last 7 days</option>
                                </select>
                            </div>
                            <div id="leaderboard-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $(Selectors.container).html(html);
    }

    /**
     * Render community posts.
     *
     * @param {Array} posts
     */
    function renderPosts(posts) {
        let html = '';

        if (!posts.length) {
            html = `
                <div class="text-center py-5 card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="alert alert-light mb-0">
                            No discussions found. Try another filter or start a new conversation.
                        </div>
                    </div>
                </div>`;
            $('#posts-grid').html(html);
            return;
        }

        posts.forEach(post => {
            const deleteBtn = post.can_delete ? `
                <button class="btn btn-sm btn-danger delete-post ms-3" data-id="${post.id}">
                    <i class="fa fa-trash"></i>
                </button>
            ` : '';

            const badges = (post.badges || []).map(badge => `
                <span class="badge bg-light text-dark border me-1">
                    <i class="fa ${escapeHtml(badge.icon)} text-warning"></i> ${escapeHtml(badge.name)}
                </span>
            `).join('');

            html += `
                <div class="mb-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="vote-count text-center me-4 border-end pe-4" style="min-width: 80px;">
                                <span class="h4 fw-bold text-primary">${post.votes}</span>
                                <small class="text-muted d-block">Votes</small>
                            </div>

                            <div class="vote-count text-center me-4 border-end pe-4" style="min-width: 80px;">
                                <span class="h4 fw-bold text-primary">${post.answers}</span>
                                <small class="text-muted d-block">Answers</small>
                            </div>

                            <div class="post-content flex-grow-1">
                                <h5>
                                    <a href="${M.cfg.wwwroot}/local/community/pages/post.php?id=${post.id}"
                                       class="text-dark text-decoration-none">
                                         ${escapeHtml(post.title)}
                                    </a>
                                </h5>

                                <small class="text-muted d-block">
                                    <i class="fa fa-user-circle"></i>
                                    ${escapeHtml(post.firstname)} ${escapeHtml(post.lastname)}
                                </small>

                                <small class="text-warning fw-bold d-block">⭐ ${post.reputation}</small>
                                <div class="mt-1">${badges}</div>
                            </div>

                            ${deleteBtn}
                        </div>
                    </div>
                </div>
            `;
        });

        $('#posts-grid').html(html);
    }

    /**
     * Render leaderboard widget.
     *
     * @param {Array} leaders
     */
    function renderLeaderboard(leaders) {
        if (!leaders.length) {
            $('#leaderboard-list').html('<div class="alert alert-light mb-0">No reputation activity yet.</div>');
            return;
        }

        const html = leaders.map((user, index) => `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <div class="fw-semibold">#${index + 1} ${escapeHtml(user.firstname)} ${escapeHtml(user.lastname)}</div>
                    <small class="text-muted">${user.posts} posts • ${user.answers} answers</small>
                </div>
                <span class="badge bg-warning text-dark">${user.points} pts</span>
            </div>
        `).join('');

        $('#leaderboard-list').html(html);
    }

    /**
     * Fetch and render posts.
     */
    function loadPosts() {
        const search = $(Selectors.searchInput).val() || '';
        const sort = $(Selectors.sortSelect).val() || 'recent';
        const params = new URLSearchParams({q: search, sort: sort});

        fetch(`${M.cfg.wwwroot}/local/community/ajax/get_posts.php?${params.toString()}`)
            .then(res => res.json())
            .then(renderPosts)
            .catch(Notification.exception);
    }

    /**
     * Fetch and render leaderboard.
     */
    function loadLeaderboard() {
        const period = $(Selectors.leaderboardFilter).val() || 'all';
        fetch(`${M.cfg.wwwroot}/local/community/ajax/get_leaderboard.php?period=${encodeURIComponent(period)}`)
            .then(res => res.json())
            .then(renderLeaderboard)
            .catch(Notification.exception);
    }

    /**
     * Create and initialize the modal for asking questions.
     */
    function initModal() {
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: 'Ask a New Question',
            body: `
                <div class="p-2">
                    <div class="mb-3">
                        <label for="post-title" class="form-label fw-bold">Title</label>
                        <input type="text" class="form-control shadow-none" id="post-title" maxlength="255"
                               placeholder="e.g. How do I use the new Moodle 5.1 Gradebook?">
                        <div id="similar-questions" class="mt-2"></div>
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

            modal.getRoot().on('input', '#post-title', function() {
                const title = ($(this).val() || '').toString();
                if (title.length < 3) {
                    $('#similar-questions').empty();
                    return;
                }

                fetch(`${M.cfg.wwwroot}/local/community/ajax/similar.php?q=${encodeURIComponent(title)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.length) {
                            $('#similar-questions').html('<small>No similar questions found.</small>');
                            return;
                        }

                        let html = '<h6>🤖 Similar Questions</h6><ul class="list-unstyled">';
                        data.forEach(item => {
                            html += `<li>
                                <a href="${M.cfg.wwwroot}/local/community/pages/post.php?id=${item.id}">
                                    ${escapeHtml(item.title)}
                                </a>
                            </li>`;
                        });
                        html += '</ul>';
                        $('#similar-questions').html(html);
                    })
                    .catch(Notification.exception);
            });

            modal.getRoot().on(ModalEvents.save, e => {
                const title = ($('#post-title').val() || '').toString().trim();
                const content = ($('#post-content').val() || '').toString().trim();

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
     * Submit post data.
     *
     * @param {string} title
     * @param {string} content
     * @param {Object} modal
     */
    function submitPost(title, content, modal) {
        fetch(`${M.cfg.wwwroot}/local/community/ajax/create_post.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({title, content, posttype: 'question', sesskey: M.cfg.sesskey})
        }).then(response => response.json())
            .then(data => {
                if (data.status !== 'ok') {
                    throw new Error(data.message || 'Failed to create post.');
                }

                modal.hide();
                loadPosts();
                loadLeaderboard();
                Notification.addNotification({
                    message: 'Question posted!',
                    type: 'success'
                });
            })
            .catch(Notification.exception);
    }

    /**
     * Bind page interactions.
     */
    function bindEvents() {
        $(document).on('input', Selectors.searchInput, loadPosts);
        $(document).on('change', Selectors.sortSelect, loadPosts);
        $(document).on('change', Selectors.leaderboardFilter, loadLeaderboard);

        $(document).on('click', '.delete-post', function(e) {
            e.preventDefault();
            const id = $(this).data('id');

            Notification.confirm(
                'Delete post',
                'Do you really want to delete this post?',
                'Yes',
                'Cancel',
                () => {
                    fetch(`${M.cfg.wwwroot}/local/community/ajax/delete_post.php`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({postid: id, sesskey: M.cfg.sesskey})
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success') {
                                loadPosts();
                                loadLeaderboard();
                            } else {
                                Notification.alert('Error', data.message, 'OK');
                            }
                        })
                        .catch(Notification.exception);
                }
            );
        });
    }

    return {
        init: () => {
            renderLayout();
            initModal();
            bindEvents();
            loadPosts();
            loadLeaderboard();
        }
    };
});