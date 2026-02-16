define(['jquery'], function($) {
    return {
        init: function(courseid) {
            // 1. Inject Styles dynamically (Broken into shorter lines for ESLint)
            let styles = '<style>';
            styles += '#ai-chat-wrapper { position: fixed; bottom: 20px; right: 20px; z-index: 9999; ';
            styles += "font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }";
            styles += '#ai-chat-icon { width: 60px; height: 60px; background: #007bff; border-radius: 50%; ';
            styles += 'display: flex; align-items: center; justify-content: center; cursor: pointer; ';
            styles += 'box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.3s; }';
            styles += '#ai-chat-icon:hover { transform: scale(1.1); }';
            styles += '#ai-chat-window { display: none; width: 350px; height: 500px; background: #fff; ';
            styles += 'border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); flex-direction: column; ';
            styles += 'overflow: hidden; margin-bottom: 15px; border: 1px solid #eee; }';
            styles += '#ai-chat-header { background: #007bff; color: white; padding: 15px; ';
            styles += 'font-weight: bold; display: flex; justify-content: space-between; }';
            styles += '#ai-chat-body { flex: 1; padding: 15px; overflow-y: auto; background: #f9f9f9; ';
            styles += 'display: flex; flex-direction: column; gap: 10px; }';
            styles += '.chat-bubble { padding: 10px 14px; border-radius: 15px; max-width: 80%; ';
            styles += 'font-size: 14px; line-height: 1.4; }';
            styles += '.user-msg { background: #007bff; color: white; align-self: flex-end; ';
            styles += 'border-bottom-right-radius: 2px; }';
            styles += '.ai-msg { background: #e9e9eb; color: #333; align-self: flex-start; ';
            styles += 'border-bottom-left-radius: 2px; }';
            styles += '#ai-chat-footer { padding: 10px; border-top: 1px solid #eee; display: flex; gap: 5px; }';
            styles += '#ai-chat-input { flex: 1; border: 1px solid #ddd; border-radius: 20px; ';
            styles += 'padding: 8px 15px; outline: none; resize: none; height: 40px; }';
            styles += '#ai-chat-send { background: #007bff; color: white; border: none; ';
            styles += 'border-radius: 50%; width: 40px; height: 40px; cursor: pointer; }';
            styles += '.typing { font-style: italic; font-size: 12px; color: #888; }';
            styles += '</style>';

            // 2. Chat HTML structure (SVG and long lines broken down)
            const chatHTML = `
                <div id="ai-chat-wrapper">
                    <div id="ai-chat-window">
                        <div id="ai-chat-header">
                            <span>AI Course Assistant</span>
                            <span id="close-chat" style="cursor:pointer">&times;</span>
                        </div>
                        <div id="ai-chat-body">
                            <div class="chat-bubble ai-msg">Hello! How can I help you today?</div>
                        </div>
                        <div id="ai-chat-footer">
                            <textarea id="ai-chat-input" placeholder="Ask a question..."></textarea>
                            <button id="ai-chat-send">➤</button>
                        </div>
                    </div>
                    <div id="ai-chat-icon">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="white">
                            <path d="M12 2C6.47 2 2 6.47 2 12c0 1.91.54 3.68 1.46 5.2L2 22l4.8-1.46
                            c1.52.92 3.29 1.46 5.2 1.46 5.53 0 10-4.47 10-10S17.53 2 12 2zm0
                            18c-1.65 0-3.19-.45-4.52-1.24l-.32-.19-2.69.82.82-2.69-.19-.32
                            C4.45 15.19 4 13.65 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/>
                        </svg>
                    </div>
                </div>`;

            $('body').append(styles + chatHTML);

            const scrollToBottom = function() {
                const body = $('#ai-chat-body');
                if (body.length) {
                    body.scrollTop(body[0].scrollHeight);
                }
            };

            const sendMessage = function() {
                const message = $('#ai-chat-input').val().trim();
                if (!message) {
                    return;
                }

                $('#ai-chat-body').append(`<div class="chat-bubble user-msg">${message}</div>`);
                $('#ai-chat-input').val('');

                const loadingId = 'loading-' + Date.now();
                $('#ai-chat-body').append(`<div id="${loadingId}" class="typing">AI is thinking...</div>`);
                scrollToBottom();

                fetch(M.cfg.wwwroot + '/local/coptutor/ajax.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({message: message, courseid: courseid})
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    $(`#${loadingId}`).remove();
                    $('#ai-chat-body').append(`<div class="chat-bubble ai-msg">${data.reply}</div>`);
                    scrollToBottom();
                })
                .catch(function() {
                    $(`#${loadingId}`).text("Error: Could not connect to AI.");
                });
            };

            // Toggle Window
            $('#ai-chat-icon, #close-chat').click(function() {
                $('#ai-chat-window').fadeToggle(200).css('display', function(_, current) {
                    return current === 'none' ? 'none' : 'flex';
                });
            });

            $('#ai-chat-send').click(sendMessage);

            $('#ai-chat-input').keypress(function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }
    };
});