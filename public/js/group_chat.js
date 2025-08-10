document.addEventListener('DOMContentLoaded', function() {
    // Initialisation
    const groupId = document.body.dataset.groupId;
    const userId = document.body.dataset.userId;
    const chatContainer = document.getElementById('messagesContainer');
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendMessageBtn');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');


    // Configuration Emoji Picker

    const emojiPicker = new EmojiPicker({
        rootElement: document.getElementById('emojiPickerContainer'),
        onEmojiSelect: emoji => {
            const input = document.getElementById('messageInput');
            input.value += emoji;
            input.focus();
        }
    });

document.getElementById('emojiBtn').addEventListener('click', () => {
    document.getElementById('emojiPickerContainer').style.display = 
        document.getElementById('emojiPickerContainer').style.display === 'block' ? 'none' : 'block';
});

    // Gestion des Fichiers
    document.getElementById('attachFileBtn').addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        filePreview.innerHTML = '';
        Array.from(this.files).forEach((file, index) => {
            const fileElement = document.createElement('div');
            fileElement.className = 'file-preview-item';
            fileElement.innerHTML = `
                <i class="fas fa-file-alt"></i>
                ${file.name}
                <button class="btn btn-sm btn-outline-danger remove-file" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            filePreview.appendChild(fileElement);
        });

        // Gestion suppression fichiers
        document.querySelectorAll('.remove-file').forEach(btn => {
            btn.addEventListener('click', function() {
                const newFiles = new DataTransfer();
                Array.from(fileInput.files).forEach((file, idx) => {
                    if (idx !== parseInt(this.dataset.index)) {
                        newFiles.items.add(file);
                    }
                });
                fileInput.files = newFiles.files;
                this.parentElement.remove();
            });
        });
    });

    // Envoi de Message
    function sendMessage() {
        const formData = new FormData();
        formData.append('group_id', groupId);
        formData.append('message', messageInput.value.trim());
        
        Array.from(fileInput.files).forEach(file => {
            formData.append('files[]', file);
        });

        fetch('../src/controllers/send_message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                fileInput.value = '';
                filePreview.innerHTML = '';
                loadMessages();
            } else {
                console.error('Erreur:', data.error);
            }
        })
        .catch(error => console.error('Erreur:', error));
    }

    // Chargement des messages
    function loadMessages() {
        fetch(`../src/controllers/get_messages.php?group_id=${groupId}`)
            .then(response => response.text())
            .then(html => {
                chatContainer.innerHTML = html;
                chatContainer.scrollTop = chatContainer.scrollHeight;
            });
    }

    // Événements
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Chargement initial et rafraîchissement périodique
    loadMessages();
    setInterval(loadMessages, 5000); // Rafraîchit toutes les 5 secondes
});