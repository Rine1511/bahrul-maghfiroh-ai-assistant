<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bahrul Maghfiroh AI Assistant</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f6f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .chat-container {
            width: 100%;
            max-width: 850px;
            height: 85vh;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background: #176b50;
            color: white;
            padding: 22px;
        }

        .chat-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .chat-header p {
            font-size: 13px;
            opacity: .9;
        }

        .chat-messages {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
            max-width: 80%;
            padding: 13px 16px;
            border-radius: 12px;
            line-height: 1.6;
            white-space: pre-wrap;
            font-size: 14px;
        }

        .bot {
            background: #edf3f0;
            color: #263b32;
            align-self: flex-start;
        }

        .user {
            background: #176b50;
            color: white;
            align-self: flex-end;
        }

        .chat-input {
            display: flex;
            gap: 10px;
            padding: 18px;
            border-top: 1px solid #eee;
        }

        .chat-input input {
            flex: 1;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
        }

        .chat-input button {
            padding: 0 22px;
            border: none;
            background: #176b50;
            color: white;
            border-radius: 8px;
            cursor: pointer;
        }

        .chat-input button:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .reset-button {
            margin-top: 12px;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, .6);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
        }

        @media (max-width: 600px) {
            .chat-container {
                height: 90vh;
            }

            .message {
                max-width: 90%;
            }
        }
    </style>
</head>

<body>
    <div class="chat-container">
        <div class="chat-header">
            <h2>Bahrul Maghfiroh AI Assistant</h2>
            <p>Asisten Virtual Pondok Pesantren Bahrul Maghfiroh</p>
            <button class="reset-button" id="resetButton">
                Reset Percakapan
            </button>
        </div>

        <div class="chat-messages" id="chatMessages">

            @forelse ($history as $item)
                <div class="message {{ $item['role'] === 'user' ? 'user' : 'bot' }}">
                    {{ $item['content'] }}
                </div>
            @empty
                <div class="message bot">
                    Assalamu'alaikum! Selamat datang di Bahrul Maghfiroh AI Assistant.
                    Ada yang bisa saya bantu?
                </div>
            @endforelse

        </div>

        <form class="chat-input" id="chatForm">
            <input type="text" id="messageInput" placeholder="Tulis pertanyaan Anda..." autocomplete="off" required>
            <button type="submit" id="sendButton">Kirim</button>
        </form>
    </div>

    <script>
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const chatMessages = document.getElementById('chatMessages');
        const sendButton = document.getElementById('sendButton');
        const resetButton = document.getElementById('resetButton');

        function addMessage(text, sender) {
            const message = document.createElement('div');
            message.className = `message ${sender}`;
            message.textContent = text;

            chatMessages.appendChild(message);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        chatForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const message = messageInput.value.trim();

            if (!message) return;

            addMessage(message, 'user');
            messageInput.value = '';
            sendButton.disabled = true;

            const loading = document.createElement('div');
            loading.className = 'message bot';
            loading.textContent = 'Sedang mengetik...';
            chatMessages.appendChild(loading);

            try {
                const response = await fetch(
                    "{{ route('chatbot.message') }}",
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ message })
                    }
                );

                const data = await response.json();

                loading.remove();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal memproses pertanyaan.');
                }

                addMessage(data.answer, 'bot');

            } catch (error) {
                loading.remove();
                addMessage(error.message, 'bot');
            } finally {
                sendButton.disabled = false;
                messageInput.focus();
            }
        });

        resetButton.addEventListener('click', async function () {
            if (!confirm('Hapus seluruh riwayat percakapan?')) return;

            try {
                const response = await fetch(
                    "{{ route('chatbot.reset') }}",
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]'
                            ).content,
                            'Accept': 'application/json'
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Gagal mereset percakapan.');
                }

                chatMessages.innerHTML = '';

                addMessage(
                    'Assalamu’alaikum! Selamat datang kembali. Ada yang bisa saya bantu?',
                    'bot'
                );

            } catch (error) {
                alert(error.message);
            }
        });
    </script>
</body>

</html>