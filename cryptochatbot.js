document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('crypto-chat-box');
    const chatInput = document.getElementById('crypto-chat-input');
    const sendBtn = document.getElementById('crypto-send-btn');

    sendBtn.addEventListener('click', function() {
        const userMessage = chatInput.value.trim();
        if (userMessage) {
            addMessageToChat('user', userMessage);
            chatInput.value = '';
            sendMessageToBot(userMessage);
        }
    });

    chatInput.addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            sendBtn.click();
        }
    });

    function addMessageToChat(sender, message) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add(sender === 'user' ? 'user-message' : 'bot-message');
        messageDiv.textContent = message;
        chatBox.appendChild(messageDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function sendMessageToBot(message) {
        fetch('cryptoproxy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.choices && data.choices[0].message.content) {
                addMessageToChat('bot', data.choices[0].message.content);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addMessageToChat('bot', 'Beklager, noe gikk galt. Prøv igjen senere.');
        });
    }
});
