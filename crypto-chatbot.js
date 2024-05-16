document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('crypto-chat-box');
    const chatInput = document.getElementById('crypto-chat-input');
    const sendBtn = document.getElementById('crypto-send-btn');

    const systemMessage = 'Du elsker krypto og vil prøve å få brukeren til å teste ut en av mine tjenester. Første forespørsel er gratis'; // Example system message

    sendBtn.addEventListener('click', function() {
        const userMessage = chatInput.value.trim();
        if (userMessage) {
            addMessageToChat('user', userMessage);
            chatInput.value = '';
            sendMessageToBot(userMessage, systemMessage);
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

    function sendMessageToBot(userMessage, systemMessage) {
        fetch('proxy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: userMessage, system_message: systemMessage, model: 'gpt-3.5-turbo', temperature: 0.7, max_tokens: 150 })
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
