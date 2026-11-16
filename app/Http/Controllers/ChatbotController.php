<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\GeminiService;
use App\Services\KnowledgeService;

class ChatbotController extends Controller
{
    protected $gemini;
    protected $knowledge;

    public function __construct(
        GeminiService $gemini,
        KnowledgeService $knowledge
    ) {
        $this->gemini = $gemini;
        $this->knowledge = $knowledge;
    }

    public function index()
    {
        $history = session('chat_history', []);

        return view('chatbot.index', compact('history'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $message = $request->message;

        // Mencari informasi dari database
        $knowledge = $this->knowledge->search($message);

        $context = '';

        foreach ($knowledge as $item) {
            $context .= "\nJudul: {$item->title}\n";
            $context .= "Informasi: {$item->content}\n";
        }

        // Instruksi untuk Gemini
        $prompt = "
Kamu adalah Bahrul Maghfiroh AI Assistant,
asisten virtual resmi Pondok Pesantren Bahrul Maghfiroh.

Gunakan bahasa Indonesia dengan gaya formal dan ramah.

Aturan:
1. Jawab berdasarkan informasi yang diberikan.
2. Jangan mengarang fakta atau informasi.
3. Jika informasi tidak tersedia, sampaikan dengan jujur.
4. Berikan jawaban yang jelas, singkat, dan mudah dipahami.

Informasi yang tersedia:
{$context}

Pertanyaan pengguna:
{$message}
";

        try {
            $answer = $this->gemini->generate($prompt);

            // Menyimpan riwayat percakapan di session
            $history = session('chat_history', []);

            $history[] = [
                'role' => 'user',
                'content' => $message,
            ];

            $history[] = [
                'role' => 'assistant',
                'content' => $answer,
            ];

            session(['chat_history' => $history]);

            return response()->json([
                'success' => true,
                'answer' => $answer,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function reset()
    {
        session()->forget('chat_history');

        return response()->json([
            'success' => true,
            'message' => 'Percakapan berhasil direset.',
        ]);
    }
}