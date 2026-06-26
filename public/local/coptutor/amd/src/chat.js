define(['jquery'], function($) {

    return {
        init: function(courseid) {

            const styles = `
<style>
#ai-chat-wrapper {
    position: fixed; bottom: 20px; right: 20px; z-index: 9999;
    font-family: 'Segoe UI', Arial, sans-serif;
}
#ai-chat-icon {
    width: 60px; height: 60px; background: #007bff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,.2);
    font-size: 24px;
}
#ai-chat-window {
    display: none; width: 400px; height: 500px; background: #fff;
    border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.15);
    flex-direction: column; overflow: hidden; margin-bottom: 15px;
    border: 1px solid #ddd;
}
#ai-chat-header {
    background: #007bff; color: #fff; padding: 15px; font-weight: bold;
    display: flex; justify-content: space-between; align-items: center;
    flex-shrink: 0;
}
.header-controls {
    display: flex; gap: 12px; align-items: center;
}
.header-controls button {
    background: transparent; border: none; color: white;
    font-size: 18px; cursor: pointer; padding: 0;
    width: 28px; height: 28px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.header-controls button:hover {
    background: rgba(255,255,255,0.2);
}
.header-controls button.off {
    opacity: 0.6;
    background: rgba(0,0,0,0.2);
}
#ai-material-bar {
    background: #f0f4ff; border-bottom: 1px solid #dde4f5;
    padding: 8px 12px; display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: #444;
    flex-shrink: 0;
}
#ai-material-bar label {
    white-space: nowrap; font-weight: 600; color: #007bff;
}
#ai-material-select {
    flex: 1; border: 1px solid #c5d0e8; border-radius: 6px;
    padding: 5px 8px; font-size: 13px; background: #fff;
    color: #333; outline: none; cursor: pointer;
}
#ai-material-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.15);
}
#ai-chat-body {
    flex: 1; padding: 15px; overflow-y: auto; background: #f9f9fb;
    display: flex; flex-direction: column; gap: 12px;
    min-height: 0;
}
.chat-bubble {
    padding: 12px 16px; border-radius: 15px; max-width: 85%;
    font-size: 14px; line-height: 1.6; word-wrap: break-word;
}
.user-msg {
    background: #007bff; color: #fff; align-self: flex-end;
    border-bottom-right-radius: 2px;
}
.ai-msg {
    background: #fff; color: #333; align-self: flex-start;
    border-bottom-left-radius: 2px; border: 1px solid #e0e0e0;
}
.typing {
    color: #999; font-size: 13px; font-style: italic; align-self: flex-start;
}
#ai-chat-footer {
    padding: 10px; border-top: 1px solid #eee; display: flex;
    gap: 8px; background: #fff; align-items: center;
    flex-shrink: 0;
}
#ai-chat-input {
    flex: 1; border: 1px solid #ddd; border-radius: 20px;
    padding: 10px 15px; height: 42px; resize: none; outline: none;
    font-family: inherit; font-size: 14px;
}
#ai-chat-input:focus {
    border-color: #007bff;
}
#ai-chat-send, #ai-chat-mic {
    background: #007bff; color: #fff; border: none; border-radius: 50%;
    width: 40px; height: 40px; cursor: pointer; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
#ai-chat-send:hover, #ai-chat-mic:hover {
    background: #0056b3;
}
#ai-chat-mic.listening {
    background: #dc3545;
}
#ai-chat-mic:disabled {
    background: #cccccc;
    cursor: not-allowed;
    opacity: 0.6;
}
</style>`;

            const chatHTML = `
<div id="ai-chat-wrapper">
    <div id="ai-chat-window">
        <div id="ai-chat-header">
            <span>AI Course Assistant</span>
            <div class="header-controls">
                <button id="voice-toggle-btn" title="Toggle voice output">🔊</button>
                <button id="mic-toggle-btn" title="Toggle microphone input">🎤</button>
                <span id="close-chat" style="cursor:pointer;font-size:20px;">&times;</span>
            </div>
        </div>
        <div id="ai-material-bar">
            <label>📄 Context:</label>
            <select id="ai-material-select">
                <option value="">— No file selected —</option>
            </select>
        </div>
        <div id="ai-chat-body"></div>
        <div id="ai-chat-footer">
            <textarea id="ai-chat-input" placeholder="Ask a question..."></textarea>
            <button id="ai-chat-mic" title="Voice to text">🎤</button>
            <button id="ai-chat-send" title="Send">➤</button>
        </div>
    </div>
    <div id="ai-chat-icon">🤖</div>
</div>`;

            $('body').append(styles + chatHTML);

            // Feature detection
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const hasSpeechRecognition = !!SpeechRecognition;
            const hasSpeechSynthesis = 'speechSynthesis' in window;
            let recognition = null;
            let historyLoaded = false;

            // Toggle states
            let voiceEnabled = true;
            let micEnabled = true;

            // UI elements
            const $voiceToggleBtn = $('#voice-toggle-btn');
            const $micToggleBtn   = $('#mic-toggle-btn');
            const $micButton      = $('#ai-chat-mic');
            const $materialSelect = $('#ai-material-select');

            // ----------------------------------------------------------------
            // Material files loader
            // ----------------------------------------------------------------

            const loadMaterials = function() {
                fetch(M.cfg.wwwroot + '/local/coptutor/materials.php?courseid=' + courseid)
                    .then(function(res) { return res.json(); })
                    .then(function(materials) {
                        $materialSelect.find('option:not(:first)').remove();
                        if (!materials.length) {
                            $materialSelect.append('<option value="" disabled>No files available</option>');
                            return;
                        }
                        materials.forEach(function(file) {
                            $materialSelect.append(
                                $('<option>').val(file.id).text(file.name)
                            );
                        });
                    })
                    .catch(function() {
                        $materialSelect.append('<option value="" disabled>Could not load files</option>');
                    });
            };

            // ----------------------------------------------------------------
            // Voice / mic toggle UI helpers
            // ----------------------------------------------------------------
            /**
             * Updates the voice toggle button UI based on the current state of voiceEnabled.
             */
            function updateVoiceToggleUI() {
                if (voiceEnabled) {
                    $voiceToggleBtn.html('🔊').removeClass('off').attr('title', 'Disable voice output');
                } else {
                    $voiceToggleBtn.html('🔇').addClass('off').attr('title', 'Enable voice output');
                }
            }

            /**
             * Updates the microphone toggle button UI based on the current state of micEnabled.
             */
            function updateMicToggleUI() {
                if (micEnabled) {
                    $micToggleBtn.html('🎤').removeClass('off').attr('title', 'Disable microphone input');
                    $micButton.prop('disabled', false);
                } else {
                    $micToggleBtn.html('🚫🎤').addClass('off').attr('title', 'Enable microphone input');
                    $micButton.prop('disabled', true);
                    if (recognition) {
                        try { recognition.stop(); } catch(e) {}
                        recognition = null;
                        $micButton.removeClass('listening');
                    }
                }
            }

            if (!hasSpeechRecognition) {
                $micButton.prop('disabled', true).attr('title', 'Voice input not supported in this browser');
                micEnabled = false;
                updateMicToggleUI();
            }

            // ----------------------------------------------------------------
            // Chat helpers
            // ----------------------------------------------------------------

            const scrollToBottom = function() {
                const body = $('#ai-chat-body')[0];
                if (body) {
                    body.scrollTop = body.scrollHeight;
                }
            };

            const renderBubble = function(content, type) {
                return $('<div>').addClass('chat-bubble ' + type).html(content);
            };

            const loadHistory = function() {
                fetch(M.cfg.wwwroot + '/local/coptutor/history.php?courseid=' + courseid)
                    .then(function(res) { return res.json(); })
                    .then(function(history) {
                        $('#ai-chat-body').empty();
                        if (!history.length) {
                            $('#ai-chat-body').append(renderBubble('Hello! How can I help you today?', 'ai-msg'));
                        } else {
                            history.forEach(function(item) {
                                $('#ai-chat-body').append(renderBubble(item.question, 'user-msg'));
                                $('#ai-chat-body').append(renderBubble(item.answer, 'ai-msg'));
                            });
                        }
                        scrollToBottom();
                    })
                    .catch(function() {
                        $('#ai-chat-body').empty().append(
                            renderBubble('Could not load conversation history.', 'ai-msg')
                        );
                    });
            };

            const speakResponse = function(text) {
                if (!voiceEnabled || !hasSpeechSynthesis) { return; }
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-US';
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
            };

            // ----------------------------------------------------------------
            // Send message
            // ----------------------------------------------------------------

            const sendMessage = function() {
                const message = $('#ai-chat-input').val().trim();
                if (!message) { return; }

                const fileid = $materialSelect.val() || null;

                $('#ai-chat-body').append(renderBubble(message, 'user-msg'));
                $('#ai-chat-input').val('');

                const loadingId = 'loading-' + Date.now();
                $('#ai-chat-body').append(
                    $('<div>').attr('id', loadingId).addClass('typing').text('AI is thinking…')
                );
                scrollToBottom();

                const payload = {
                    message:  message,
                    courseid: courseid
                };
                if (fileid) {
                    payload.fileid = parseInt(fileid, 10);
                }

                fetch(M.cfg.wwwroot + '/local/coptutor/ajax.php', {
                    method:  'POST',
                    headers: {'Content-Type': 'application/json'},
                    body:    JSON.stringify(payload)
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    $('#' + loadingId).remove();
                    const reply = data.reply || 'Sorry, I could not process that.';
                    $('#ai-chat-body').append(renderBubble(reply, 'ai-msg'));
                    speakResponse(reply);
                    scrollToBottom();
                })
                .catch(function() {
                    $('#' + loadingId).removeClass('typing').text('Error: AI offline');
                });
            };

            // ----------------------------------------------------------------
            // Voice input
            // ----------------------------------------------------------------

            const startVoiceInput = function() {
                if (!micEnabled || !hasSpeechRecognition || recognition) { return; }

                recognition = new SpeechRecognition();
                recognition.lang = 'en-US';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;

                $micButton.addClass('listening');

                recognition.onresult = function(event) {
                    const transcript = event.results[0][0].transcript;
                    const current    = $('#ai-chat-input').val().trim();
                    $('#ai-chat-input').val((current ? current + ' ' : '') + transcript).focus();
                };

                recognition.onerror = function() {
                    $micButton.removeClass('listening');
                    recognition = null;
                };

                recognition.onend = function() {
                    $micButton.removeClass('listening');
                    recognition = null;
                };

                recognition.start();
            };

            // ----------------------------------------------------------------
            // Event bindings
            // ----------------------------------------------------------------

            $voiceToggleBtn.on('click', function(e) {
                e.stopPropagation();
                voiceEnabled = !voiceEnabled;
                updateVoiceToggleUI();
                if (!voiceEnabled && hasSpeechSynthesis) {
                    window.speechSynthesis.cancel();
                }
            });

            $micToggleBtn.on('click', function(e) {
                e.stopPropagation();
                micEnabled = !micEnabled;
                updateMicToggleUI();
            });

            $('#ai-chat-icon').on('click', function() {
                const $win = $('#ai-chat-window');
                if ($win.is(':visible')) {
                    $win.fadeOut(200);
                } else {
                    $win.css('display', 'flex').hide().fadeIn(200);
                    if (!historyLoaded) {
                        loadHistory();
                        loadMaterials();
                        historyLoaded = true;
                    }
                }
            });

            $('#close-chat').on('click', function() {
                $('#ai-chat-window').fadeOut(200);
            });

            $('#ai-chat-send').on('click', sendMessage);
            $('#ai-chat-mic').on('click', startVoiceInput);

            $('#ai-chat-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Load history & materials immediately
            loadHistory();
            loadMaterials();
            historyLoaded = true;

            // Init toggle UI
            updateVoiceToggleUI();
            updateMicToggleUI();
        }
    };
});