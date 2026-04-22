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
}
.header-controls {
    display: flex; gap: 12px;
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
#ai-chat-body {
    flex: 1; padding: 15px; overflow-y: auto; background: #f9f9fb;
    display: flex; flex-direction: column; gap: 12px;
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
#ai-chat-footer {
    padding: 10px; border-top: 1px solid #eee; display: flex;
    gap: 8px; background: #fff;
}
#ai-chat-input {
    flex: 1; border: 1px solid #ddd; border-radius: 20px;
    padding: 10px 15px; height: 42px; resize: none; outline: none;
}
#ai-chat-send, #ai-chat-mic {
    background: #007bff; color: #fff; border: none; border-radius: 50%;
    width: 40px; height: 40px; cursor: pointer;
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
                <button id="mic-toggle-btn" title="Toggle microphone input">🎤❌</button>
                <span id="close-chat" style="cursor:pointer;font-size:20px;">&times;</span>
            </div>
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
            let voiceEnabled = true;   // AI voice output on/off
            let micEnabled = true;     // Microphone input functionality on/off

            // UI elements
            const $voiceToggleBtn = $('#voice-toggle-btn');
            const $micToggleBtn = $('#mic-toggle-btn');
            const $micButton = $('#ai-chat-mic');

            /**
             * Updates the voice toggle button UI based on current voiceEnabled state.
             */
            function updateVoiceToggleUI() {
                if (voiceEnabled) {
                    $voiceToggleBtn.html('🔊');
                    $voiceToggleBtn.removeClass('off');
                    $voiceToggleBtn.attr('title', 'Disable voice output');
                } else {
                    $voiceToggleBtn.html('🔇');
                    $voiceToggleBtn.addClass('off');
                    $voiceToggleBtn.attr('title', 'Enable voice output');
                }
            }

            /**
             * Updates the microphone toggle button UI and enables/disables the mic button.
             */
            function updateMicToggleUI() {
                if (micEnabled) {
                    $micToggleBtn.html('🎤');
                    $micToggleBtn.removeClass('off');
                    $micToggleBtn.attr('title', 'Disable microphone input');
                    $micButton.prop('disabled', false);
                } else {
                    $micToggleBtn.html('🚫🎤');
                    $micToggleBtn.addClass('off');
                    $micToggleBtn.attr('title', 'Enable microphone input');
                    $micButton.prop('disabled', true);
                    // If currently listening, stop recognition
                    if (recognition) {
                        try { recognition.stop(); } catch(e) {}
                        recognition = null;
                        $micButton.removeClass('listening');
                    }
                }
            }

            // Disable mic button if speech recognition not available
            if (!hasSpeechRecognition) {
                $micButton.prop('disabled', true).attr('title', 'Voice input not supported in this browser');
                micEnabled = false;
                updateMicToggleUI();
            }

            /**
             * Scrolls the chat body to the bottom.
             */
            const scrollToBottom = function() {
                const body = $('#ai-chat-body');
                body.animate({scrollTop: body[0].scrollHeight}, 300);
            };

            /**
             * Renders a chat bubble.
             * @param {string} content - The message text.
             * @param {string} type - The bubble type: 'user-msg' or 'ai-msg'.
             * @returns {jQuery} The created bubble element.
             */
            const renderBubble = function(content, type) {
                return $('<div>').addClass('chat-bubble ' + type).html(content);
            };

            /**
             * Loads the conversation history from the server and populates the chat body.
             */
            const loadHistory = function() {
                fetch(M.cfg.wwwroot + '/local/coptutor/history.php?courseid=' + courseid)
                .then(res => res.json())
                .then(history => {
                    $('#ai-chat-body').empty();
                    if (!history.length) {
                        $('#ai-chat-body').append(renderBubble('Hello! How can I help you today?', 'ai-msg'));
                    } else {
                        history.forEach(item => {
                            $('#ai-chat-body').append(renderBubble(item.question, 'user-msg'));
                            $('#ai-chat-body').append(renderBubble(item.answer, 'ai-msg'));
                        });
                    }
                    scrollToBottom();
                })
                .catch(() => {
                    // Silently fail - do not use console in Moodle AMD
                    $('#ai-chat-body').empty().append(renderBubble('Could not load conversation history.', 'ai-msg'));
                });
            };

            /**
             * Speaks the AI response using the Web Speech API if voice output is enabled.
             * @param {string} text - The text to be spoken.
             */
            const speakResponse = function(text) {
                if (!voiceEnabled || !hasSpeechSynthesis) {
                    return;
                }
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-US';
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
            };

            /**
             * Sends the user's message to the backend, displays the AI response, and optionally speaks it.
             */
            const sendMessage = function() {
                const message = $('#ai-chat-input').val().trim();
                if (!message) {
                    return;
                }

                $('#ai-chat-body').append(renderBubble(message, 'user-msg'));
                $('#ai-chat-input').val('');

                const loadingId = 'loading-' + Date.now();
                $('#ai-chat-body').append('<div id="' + loadingId + '" class="typing">AI is thinking...</div>');
                scrollToBottom();

                fetch(M.cfg.wwwroot + '/local/coptutor/ajax.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({message: message, courseid: courseid})
                })
                .then(res => res.json())
                .then(data => {
                    $('#' + loadingId).remove();
                    const reply = data.reply || 'Sorry, I could not process that.';
                    $('#ai-chat-body').append(renderBubble(reply, 'ai-msg'));
                    speakResponse(reply);
                    scrollToBottom();
                })
                .catch(() => {
                    $('#' + loadingId).text('Error: AI offline');
                });
            };

            /**
             * Starts voice input (speech-to-text) and fills the input field with the transcript.
             */
            const startVoiceInput = function() {
                if (!micEnabled) {
                    return;
                }
                if (!hasSpeechRecognition || recognition) {
                    return;
                }

                recognition = new SpeechRecognition();
                recognition.lang = 'en-US';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;

                $micButton.addClass('listening');

                recognition.onresult = function(event) {
                    const transcript = event.results[0][0].transcript;
                    const currentText = $('#ai-chat-input').val().trim();
                    $('#ai-chat-input').val((currentText ? currentText + ' ' : '') + transcript).focus();
                };

                recognition.onerror = function() {
                    $micButton.removeClass('listening');
                    recognition = null;
                    // Silent fail - no console output
                };

                recognition.onend = function() {
                    $micButton.removeClass('listening');
                    recognition = null;
                };

                recognition.start();
            };

            // ----- Event bindings -----

            // Toggle voice output
            $voiceToggleBtn.on('click', function(e) {
                e.stopPropagation();
                voiceEnabled = !voiceEnabled;
                updateVoiceToggleUI();
                if (!voiceEnabled && hasSpeechSynthesis) {
                    window.speechSynthesis.cancel(); // stop any ongoing speech
                }
            });

            // Toggle microphone input (enable/disable mic button)
            $micToggleBtn.on('click', function(e) {
                e.stopPropagation();
                micEnabled = !micEnabled;
                updateMicToggleUI();
            });

            // Open/close chat window
            $('#ai-chat-icon').on('click', function() {
                $('#ai-chat-window').fadeToggle(200).css('display', 'flex');
                if (!historyLoaded) {
                    loadHistory();
                    historyLoaded = true;
                }
            });

            $('#close-chat').on('click', function() {
                $('#ai-chat-window').fadeOut(200);
            });

            // Send message
            $('#ai-chat-send').on('click', sendMessage);
            $('#ai-chat-mic').on('click', startVoiceInput);

            // Enter key (without shift) sends message
            $('#ai-chat-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // ---------- Load data immediately when page opens ----------
            loadHistory();
            historyLoaded = true;

            // Initialize toggle UI states
            updateVoiceToggleUI();
            updateMicToggleUI();
        }
    };
});