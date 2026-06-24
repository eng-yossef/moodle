define(['jquery'], function($) {
    return {
        /**
         * Initialize the interview module.
         *
         * @param {number} cmid Course module ID.
         */
        init: function(cmid) {
            var container = $('#interview-container');
            var stateStart = $('#state-start');
            var stateInterview = $('#state-interview');
            var stateCompleted = $('#state-completed');
            var errorAlert = $('#error-alert');
            var messageList = $('#message-list');
            var typingIndicator = $('#typing-indicator');
            var questionCounter = $('#question-counter');
            var progressBar = $('#progress-bar');
            var loadingOverlay = $('#loading-overlay');

            var currentStatus = container.data('initial-status') || 'not_started';
            var questionCount = 0;
            var totalQuestions = 5;
            var ajaxUrl = M.cfg.wwwroot + '/mod/interview/ajax.php';

            var interviewEnded = (currentStatus === 'completed');
            var requestInFlight = false;
            var cleanupSent = false;

            /**
             * Show the loading overlay.
             */
            function showOverlay() {
                loadingOverlay.show();
            }

            /**
             * Hide the loading overlay.
             */
            function hideOverlay() {
                loadingOverlay.hide();
            }

            /**
             * Display an error message that auto-hides after 5 seconds.
             *
             * @param {string} msg The error message text.
             */
            function showError(msg) {
                errorAlert.text(msg).removeClass('d-none');
                setTimeout(function() {
                    errorAlert.addClass('d-none');
                }, 5000);
            }

            /**
             * Hide the error alert.
             */
            function hideError() {
                errorAlert.addClass('d-none');
            }

            /**
             * Switch the visible UI state.
             *
             * @param {string} state One of 'not_started', 'in_progress', or 'completed'.
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
             * Update the progress bar and question counter.
             *
             * @param {number} count The number of answered questions.
             */
            function updateProgress(count) {
                var pct = Math.min(100, Math.floor((count / totalQuestions) * 100));
                progressBar.css('width', pct + '%').text(pct + '%');
                questionCounter.text(count + ' / ' + totalQuestions);
            }

            /**
             * Add a chat message to the conversation display.
             *
             * @param {string} text The message text.
             * @param {string} type 'question' or 'answer'.
             */
            function addMessage(text, type) {
                var isQuestion = (type === 'question');
                var avatar = isQuestion ? '🤖' : '👤';
                var alignClass = isQuestion ? 'text-start' : 'text-end';
                var bubbleClass = isQuestion ? 'bg-light' : 'bg-primary text-white';
                var safeText = $('<div>').text(text).html();

                var msgHtml =
                    '<div class="mb-3 ' + alignClass + '">' +
                        '<div class="d-inline-block p-3 rounded-3 ' + bubbleClass + ' shadow-sm" style="max-width: 80%;">' +
                            '<div class="d-flex align-items-start">' +
                                '<span class="me-2" style="font-size: 1.5rem;">' + avatar + '</span>' +
                                '<div>' + safeText + '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="text-muted small mt-1">' + (isQuestion ? 'Interviewer' : 'You') + '</div>' +
                    '</div>';

                messageList.append(msgHtml);

                var chatContainer = document.getElementById('chat-messages');
                if (chatContainer) {
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            }

            /**
             * Show or hide the typing indicator.
             *
             * @param {boolean} show True to show the indicator, false to hide.
             */
            function showTyping(show) {
                if (show) {
                    typingIndicator.removeClass('d-none');
                } else {
                    typingIndicator.addClass('d-none');
                }
            }

            /**
             * Finalize the UI after the interview is completed.
             *
             * @param {Object|null} evaluation The evaluation object containing level and feedback.
             */
            function finalizeInterviewUI(evaluation) {
                interviewEnded = true;
                currentStatus = 'completed';
                setState('completed');

                var html = '';
                if (evaluation) {
                    html += '<strong>Level:</strong> ' + (evaluation.level || '') + '<br>';
                    html += '<strong>Feedback:</strong> ' + (evaluation.feedback || '');
                } else {
                    html = '<div class="text-muted">Interview completed.</div>';
                }

                $('#evaluation-text').html(html);
            }

            /**
             * Reset all UI elements and internal state for a fresh interview attempt.
             */
            function resetInterviewUI() {
                currentStatus = 'not_started';
                interviewEnded = false;
                cleanupSent = false;
                requestInFlight = false;
                questionCount = 0;

                messageList.empty();
                $('#evaluation-text').html('');
                $('#answer-text').val('');
                updateProgress(0);
                setState('not_started');
                hideError();

                var dropzone = document.getElementById('dropzone');
                var fileInput = document.getElementById('cvfile');
                var fileDisplay = $('#file-name-display');

                if (fileInput) {
                    fileInput.value = '';
                }

                if (fileDisplay.length) {
                    fileDisplay.text('No file selected');
                }

                if (dropzone && dropzone.querySelector('p')) {
                    dropzone.querySelector('p').innerHTML =
                        '<strong>Click or drag &amp; drop</strong> your CV here';
                }
            }

            // ---- File upload: drag & drop + explicit button ----
            var dropzone = document.getElementById('dropzone');
            var fileInput = document.getElementById('cvfile');
            var fileDisplay = $('#file-name-display');

            if (dropzone && fileInput) {
                dropzone.addEventListener('click', function() {
                    fileInput.click();
                });

                dropzone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    dropzone.classList.add('border-primary', 'bg-primary-subtle');
                });

                dropzone.addEventListener('dragleave', function() {
                    dropzone.classList.remove('border-primary', 'bg-primary-subtle');
                });

                dropzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    dropzone.classList.remove('border-primary', 'bg-primary-subtle');
                    if (e.dataTransfer.files.length) {
                        fileInput.files = e.dataTransfer.files;
                        updateFileUI(fileInput.files[0]);
                    }
                });

                fileInput.addEventListener('change', function() {
                    if (this.files.length) {
                        updateFileUI(this.files[0]);
                    } else {
                        updateFileUI(null);
                    }
                });
            }

            $('#choose-file-btn').on('click', function() {
                if (fileInput) {
                    fileInput.click();
                }
            });

            /**
             * Update the file display UI based on the selected file.
             *
             * @param {File|null} file The selected file or null if none.
             */
            function updateFileUI(file) {
                if (file) {
                    var fileName = file.name;
                    fileDisplay.text(fileName);
                    if (dropzone && dropzone.querySelector('p')) {
                        dropzone.querySelector('p').innerHTML =
                            '<strong>' + fileName + '</strong> selected';
                    }
                } else {
                    fileDisplay.text('No file selected');
                    if (dropzone && dropzone.querySelector('p')) {
                        dropzone.querySelector('p').innerHTML =
                            '<strong>Click or drag &amp; drop</strong> your CV here';
                    }
                }
            }

            // ---- Start interview ----
            $('#btn-start').on('click', async function() {
                if (requestInFlight || interviewEnded) {
                    return;
                }

                var file = fileInput && fileInput.files ? fileInput.files[0] : null;
                if (!file) {
                    showError('Please select a CV file.');
                    return;
                }

                hideError();
                requestInFlight = true;
                showTyping(true);
                showOverlay();

                var formData = new FormData();
                formData.append('action', 'start');
                formData.append('cmid', cmid);
                formData.append('sesskey', M.cfg.sesskey);
                formData.append('cvfile', file);

                try {
                    var response = await fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });

                    var data = await response.json();
                    hideOverlay();
                    showTyping(false);
                    requestInFlight = false;

                    if (!response.ok || data.error) {
                        showError(data.error || 'Server error.');
                        return;
                    }

                    if (data.status === 'in_progress') {
                        currentStatus = 'in_progress';
                        setState('in_progress');
                        messageList.empty();
                        updateProgress(0);
                        addMessage(data.question || 'Welcome! Let’s begin.', 'question');
                        $('#answer-text').val('').focus();
                        return;
                    }

                    if (data.status === 'completed') {
                        finalizeInterviewUI(data.evaluation || null);
                        return;
                    }

                    showError('Unexpected response.');
                } catch (error) {
                    hideOverlay();
                    showTyping(false);
                    requestInFlight = false;
                    showError('Network error. Please try again.');
                }
            });

            // ---- Send the user's answer ----
            /**
             * Send the user's answer to the server and process the response.
             */
            function sendAnswer() {
                if (requestInFlight || interviewEnded) {
                    return;
                }

                var answer = $('#answer-text').val().trim();
                if (!answer) {
                    showError('Please type your answer.');
                    return;
                }

                hideError();
                addMessage(answer, 'answer');
                $('#answer-text').val('');
                showTyping(true);
                requestInFlight = true;
                // showOverlay();

                var params = new URLSearchParams();
                params.append('action', 'answer');
                params.append('cmid', cmid);
                params.append('sesskey', M.cfg.sesskey);
                params.append('answer', answer);

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: params.toString()
                })
                .then(function(response) {
                    return response.json().then(function(data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function(result) {
                    // hideOverlay();
                    showTyping(false);
                    requestInFlight = false;

                    var data = result.data;

                    if (!result.ok || data.error) {
                        showError(data.error || 'Server error.');
                        return;
                    }

                    if (data.status === 'completed') {
                        questionCount++;
                        updateProgress(questionCount);
                        finalizeInterviewUI(data.evaluation || null);
                        return;
                    }

                    if (data.status === 'in_progress' && data.question) {
                        questionCount++;
                        updateProgress(questionCount);
                        addMessage(data.question, 'question');
                        $('#answer-text').focus();
                        return;
                    }

                    showError('Unexpected server response.');
                })
                .catch(function() {
                    hideOverlay();
                    showTyping(false);
                    requestInFlight = false;
                    showError('Network error. Please try again.');
                });
            }

            $('#btn-answer').on('click', sendAnswer);

            $('#answer-text').on('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    sendAnswer();
                }
            });

            // ---- Restart interview ----
            $('#btn-restart').on('click', async function() {
                if (requestInFlight) {
                    return;
                }

                requestInFlight = true;
                showOverlay();

                try {
                    var res = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: new URLSearchParams({
                            action: 'restart',
                            cmid: cmid,
                            sesskey: M.cfg.sesskey
                        }).toString()
                    });

                    var data = await res.json();
                    hideOverlay();
                    requestInFlight = false;

                    if (!res.ok || data.error) {
                        showError(data.error || 'Failed to restart interview.');
                        return;
                    }

                    resetInterviewUI();
                } catch (e) {
                    hideOverlay();
                    requestInFlight = false;
                    showError('Failed to restart interview.');
                }
            });

            // ---- Cleanup only if still active ----
            window.addEventListener('beforeunload', function() {
                if (interviewEnded || currentStatus !== 'in_progress' || cleanupSent) {
                    return;
                }

                cleanupSent = true;

                var payload = new URLSearchParams();
                payload.append('action', 'end');
                payload.append('cmid', cmid);
                payload.append('sesskey', M.cfg.sesskey);

                navigator.sendBeacon(ajaxUrl, payload);
            });

            // ---- Initial state ----
            setState(currentStatus);

            if (currentStatus === 'completed') {
                interviewEnded = true;
            }
        }
    };
});