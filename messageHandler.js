// messageHandler.js
document.addEventListener("DOMContentLoaded", function() {
    // Event listener para enviar mensaje
    document.getElementById('send-button').addEventListener('click', function() {
        var messageInput = document.getElementById('message-input');
        var messageText = messageInput.value.trim();
        if (messageText !== '') {
            // Agregar el mensaje al chat body
            var chatBody = document.getElementById('chat-body');
            var messageDiv = document.createElement('div');
            messageDiv.classList.add('message');
            messageDiv.classList.add('sent');
            messageDiv.innerHTML = `
                <p>${messageText}</p>
                <span class="time">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
            `;
            chatBody.appendChild(messageDiv);
            // Limpiar el input después de enviar el mensaje
            messageInput.value = '';
        }
    });

});
