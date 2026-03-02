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
    border: 1px solid #ddd; display: flex; flex-direction: column;
}
#ai-chat-header {
    background: #007bff; color: #fff; padding: 15px; font-weight: bold;
    display: flex; justify-content: space-between; align-items: center;
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
#ai-chat-mic.listening { background: #dc3545; }
</style>`;

            const chatHTML = `
<div id="ai-chat-wrapper">
    <div id="ai-chat-window">
        <div id="ai-chat-header">
            <span>AI Course Assistant</span>
            <span id="close-chat" style="cursor:pointer;font-size:20px;">&times;</span>
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

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const hasSpeechRecognition = !!SpeechRecognition;
            const hasSpeechSynthesis = 'speechSynthesis' in window;
            let recognition = null;
            let historyLoaded = false;

            if (!hasSpeechRecognition) {
                $('#ai-chat-mic')
                    .prop('disabled', true)
                    .attr('title', 'Voice input not supported in this browser');
            }

            const scrollToBottom = function() {
                const body = $('#ai-chat-body');
                body.animate({scrollTop: body[0].scrollHeight}, 300);
            };

            const renderBubble = function(content, type) {
                return $('<div>').addClass('chat-bubble ' + type).html(content);
            };

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
                });
            };

            const speakResponse = function(text) {
                if (!hasSpeechSynthesis) {
                    return;
                }
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-US';
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(utterance);
            };

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
                    $('#ai-chat-body').append(renderBubble(data.reply, 'ai-msg'));
                    speakResponse(data.reply);
                    scrollToBottom();
                })
                .catch(() => {
                    $('#' + loadingId).text('Error: AI offline');
                });
            };

            const startVoiceInput = function() {
                if (!hasSpeechRecognition || recognition) {
                    return;
                }

                recognition = new SpeechRecognition();
                recognition.lang = 'en-US';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;

                $('#ai-chat-mic').addClass('listening');

                recognition.onresult = function(event) {
                    const transcript = event.results[0][0].transcript;
                    const currentText = $('#ai-chat-input').val().trim();
                    $('#ai-chat-input').val((currentText ? currentText + ' ' : '') + transcript).focus();
                };

                recognition.onerror = function(e) {
                    $('#ai-chat-mic').removeClass('listening');
                    recognition = null;
                    // eslint-disable-next-line no-console
                    console.error('Mic error:', e);
                };

                recognition.onend = function() {
                    $('#ai-chat-mic').removeClass('listening');
                    recognition = null;
                };

                recognition.start();
            };

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
            $('#ai-chat-send').on('click', sendMessage);
            $('#ai-chat-mic').on('click', startVoiceInput);

            $('#ai-chat-input').on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

        }
    };
});