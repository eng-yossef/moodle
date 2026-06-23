define(['jquery'], function($) {
    return {

        /**
         * Initialize the interview module.
         *
         * @param {Number} cmid Course module ID.
         */
        init: function(cmid) {
            // Send an "end" request when the user navigates away.
            window.addEventListener("beforeunload", function () {
                navigator.sendBeacon(
                    M.cfg.wwwroot + "/mod/interview/ajax.php",
                    new URLSearchParams({
                        action: "end",
                        cmid: cmid,
                        sesskey: M.cfg.sesskey
                    })
                );
            });

            const container = $('#interview-container');
            const stateStart = $('#state-start');
            const stateInterview = $('#state-interview');
            const stateCompleted = $('#state-completed');
            const errorAlert = $('#error-alert');

            let currentStatus = container.data('initial-status') || 'not_started';
            let questionCount = 0;

            const ajaxUrl = M.cfg.wwwroot + '/mod/interview/ajax.php';

            /**
             * Show an error message.
             *
             * @param {String} msg Error message.
             */
            function showError(msg) {
                errorAlert.text(msg).removeClass('d-none');
            }

            /**
             * Hide the error message.
             */
            function hideError() {
                errorAlert.addClass('d-none');
            }

            /**
             * Set the current UI state.
             *
             * @param {String} state Interview state.
             */
            function setState(state) {
                stateStart.addClass('d-none');
                stateInterview.addClass('d-none');
                stateCompleted.addClass('d-none');

                if (state === 'not_started') {
                    stateStart.removeClass('d-none');
                } else if (state === 'in_progress') {
                    stateInterview.removeClass('d-none');
                } else if (state === 'completed') {
                    stateCompleted.removeClass('d-none');
                }
            }

            /**
             * Update progress bar.
             *
             * @param {Number} count Questions answered.
             */
            function updateProgress(count) {
                const pct = Math.min(100, Math.floor((count / 5) * 100));
                $('#progress-bar').css('width', pct + '%').text(pct + '%');
            }

            /**
             * Start interview.
             */
            $('#btn-start').on('click', async function() {
                const fileInput = document.getElementById('cvfile');
                const file = fileInput && fileInput.files ? fileInput.files[0] : null;

                if (!file) {
                    showError('Please select a CV file.');
                    return;
                }

                hideError();

                const formData = new FormData();
                formData.append('action', 'start');
                formData.append('cmid', cmid);
                formData.append('sesskey', M.cfg.sesskey);
                formData.append('cvfile', file);

                try {
                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (!response.ok || data.error) {
                        showError(data.error || 'Server error.');
                        return;
                    }

                    if (data.status === 'in_progress') {
                        currentStatus = 'in_progress';
                        setState('in_progress');
                        $('#question-text').text(data.question || '');
                        $('#answer-text').val('');
                        updateProgress(0);
                    } else {
                        showError('Unexpected response from server.');
                    }
                } catch (error) {
                    showError('Server error. Please try again.');
                }
            });

            /**
             * Submit answer.
             */
            $('#btn-answer').on('click', async function() {
                const answer = $('#answer-text').val().trim();

                if (!answer) {
                    showError('Please type your answer.');
                    return;
                }

                hideError();

                const params = new URLSearchParams();
                params.append('action', 'answer');
                params.append('cmid', cmid);
                params.append('sesskey', M.cfg.sesskey);
                params.append('answer', answer);

                try {
                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: params.toString()
                    });

                    const data = await response.json();

                    if (!response.ok || data.error) {
                        showError(data.error || 'Server error.');
                        return;
                    }

                    questionCount++;
                    updateProgress(questionCount);

                    if (data.status === 'completed') {
                        currentStatus = 'completed';
                        setState('completed');

                        let html = '';
                        if (data.evaluation) {
                            html += '<strong>Level:</strong> ' + (data.evaluation.level || '') + '<br>';
                            html += '<strong>Feedback:</strong> ' + (data.evaluation.feedback || '');
                        }
                        $('#evaluation-text').html(html);
                    } else if (data.question) {
                        $('#question-text').text(data.question);
                        $('#answer-text').val('');
                    } else {
                        showError('Unexpected response from server.');
                    }
                } catch (error) {
                    showError('Server error. Please try again.');
                }
            });

            setState(currentStatus);
        }
    };
});