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

        $message = trim($request->message);

        // Mencari informasi relevan dari database (FAQ, Knowledge, Sources)
        $knowledge = $this->knowledge->search($message);

        $contextText = '';
        if ($knowledge->isNotEmpty()) {
            foreach ($knowledge as $item) {
                $typeLabel = match ($item->source_type ?? 'knowledge') {
                    'faq' => 'FAQ Resmi',
                    'website' => 'Halaman Website',
                    'sib_bm' => 'Data Fasilitas SIB-BM',
                    default => 'Informasi Pondok',
                };

                $contextText .= "--- [{$typeLabel}: {$item->title}] ---\n";
                $contextText .= "{$item->content}\n\n";
            }
        } else {
            $contextText = "(Tidak ada data spesifik yang cocok dari basis data pondok.)\n";
        }

        // Instruksi dan batasan prompt untuk Gemini
        $prompt = "
Kamu adalah Bahrul Maghfiroh AI Assistant, asisten virtual resmi Pondok Pesantren Bahrul Maghfiroh Malang.

TUGAS UTAMA:
Kamu hanya bertugas memberikan informasi dan menjawab pertanyaan yang berkaitan dengan Pondok Pesantren Bahrul Maghfiroh Malang.

GAYA KOMUNIKASI:
- Gunakan bahasa Indonesia yang sopan, ramah, dan mudah dipahami.
- Berikan jawaban secara singkat, jelas, dan informatif.
- Gunakan sapaan yang santun.
- Jangan memberikan informasi yang tidak memiliki dasar.

PEDOMAN MENJAWAB:

1. PERTANYAAN SEPUTAR PONDOK:
- Jawab hanya berdasarkan INFORMASI RESMI PONDOK yang diberikan.
- Jangan mengarang informasi, biaya, tanggal, alamat, nomor kontak, atau fakta lainnya.
- Jika informasi yang ditanyakan belum tersedia, sampaikan bahwa informasi tersebut belum tersedia dalam sistem dan sarankan pengguna menghubungi pihak pondok secara langsung.

2. SALAM DAN SAPAAN:
- Kamu diperbolehkan membalas salam, sapaan, ucapan terima kasih, dan percakapan singkat secara ramah.
- Arahkan percakapan kembali pada informasi Pondok Pesantren Bahrul Maghfiroh.

3. PERTANYAAN DI LUAR PONDOK:
- Jangan menjawab pertanyaan umum yang tidak berkaitan dengan Pondok Pesantren Bahrul Maghfiroh.
- Sampaikan dengan sopan bahwa kamu hanya dapat membantu memberikan informasi seputar Pondok Pesantren Bahrul Maghfiroh Malang.

INFORMASI RESMI PONDOK:
{$contextText}

PERTANYAAN PENGGUNA:
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
            Log::error('Chatbot sendMessage error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Maaf, saat ini layanan chatbot sedang mengalami kendala. Silakan coba kembali beberapa saat lagi.',
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