<?php

namespace App\Console\Commands;

use App\Models\ChatbotKnowledge;
use App\Models\ChatbotSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncWebsiteKnowledge extends Command
{
    /**
     * Nama dan signature console command.
     */
    protected $signature = 'app:sync-website-knowledge {--limit=20 : Jumlah artikel atau halaman maksimal yang disinkronkan}';

    /**
     * Deskripsi console command.
     */
    protected $description = 'Sinkronisasi konten publik dari website utama Pondok Pesantren Bahrul Maghfiroh ke database';

    /**
     * Eksekusi console command.
     */
    public function handle()
    {
        $baseUrl = config('services.website.url', 'https://bahrulmaghfirohmalang.or.id');
        $limit = (int) $this->option('limit');

        $this->info("Memulai sinkronisasi konten dari website: {$baseUrl} (Maks {$limit} data)...");

        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // 1. Sinkronisasi Berita / Postingan Publik
        $this->info("\n--- Mengambil Berita & Artikel Terbaru ---");
        $this->syncPosts($baseUrl, $limit, $stats);

        // 2. Sinkronisasi Halaman Publik Penting
        $this->info("\n--- Mengambil Halaman Informasi Publik ---");
        $this->syncPages($baseUrl, $limit, $stats);

        $this->newLine();
        $this->info("==========================================");
        $this->info("SINKRONISASI SELESAI!");
        $this->info("Data Baru Ditambahkan : {$stats['inserted']}");
        $this->info("Data Diperbarui        : {$stats['updated']}");
        $this->info("Data Tidak Berubah    : {$stats['skipped']}");
        if ($stats['errors'] > 0) {
            $this->warn("Data Gagal Diproses   : {$stats['errors']}");
        }
        $this->info("==========================================");

        return self::SUCCESS;
    }

    /**
     * Mengambil dan memproses posts/berita dari WordPress REST API.
     */
    protected function syncPosts(string $baseUrl, int $limit, array &$stats): void
    {
        try {
            $endpoint = "{$baseUrl}/wp-json/wp/v2/posts";
            $response = Http::timeout(20)->get($endpoint, [
                'per_page' => $limit,
                'status' => 'publish',
            ]);

            if ($response->failed()) {
                $this->error("Gagal menghubungi endpoint posts: Status {$response->status()}");
                $stats['errors']++;
                return;
            }

            $posts = $response->json();
            if (!is_array($posts)) {
                $this->warn("Format respon posts tidak valid.");
                return;
            }

            foreach ($posts as $item) {
                $this->processContentItem($item, 'website_post', 'Berita & Kegiatan', $stats);
            }

        } catch (\Throwable $e) {
            $this->error("Error saat sinkronisasi posts: " . $e->getMessage());
            Log::error("SyncWebsiteKnowledge syncPosts error: " . $e->getMessage(), ['exception' => $e]);
            $stats['errors']++;
        }
    }

    /**
     * Mengambil dan memproses halaman (pages) dari WordPress REST API.
     */
    protected function syncPages(string $baseUrl, int $limit, array &$stats): void
    {
        try {
            $endpoint = "{$baseUrl}/wp-json/wp/v2/pages";
            $response = Http::timeout(20)->get($endpoint, [
                'per_page' => $limit,
                'status' => 'publish',
            ]);

            if ($response->failed()) {
                $this->error("Gagal menghubungi endpoint pages: Status {$response->status()}");
                $stats['errors']++;
                return;
            }

            $pages = $response->json();
            if (!is_array($pages)) {
                $this->warn("Format respon pages tidak valid.");
                return;
            }

            // Daftar kata kunci judul yang diabaikan (halaman sistem atau checkout plugin)
            $ignoredKeywords = ['checkout', 'profile', 'cart', 'dashboard', 'venue', 'instructor'];

            foreach ($pages as $item) {
                $titleLower = mb_strtolower($item['title']['rendered'] ?? '');
                $shouldSkip = false;
                foreach ($ignoredKeywords as $ign) {
                    if (str_contains($titleLower, $ign)) {
                        $shouldSkip = true;
                        break;
                    }
                }

                if ($shouldSkip) {
                    continue;
                }

                $this->processContentItem($item, 'website_page', 'Halaman Website', $stats);
            }

        } catch (\Throwable $e) {
            $this->error("Error saat sinkronisasi pages: " . $e->getMessage());
            Log::error("SyncWebsiteKnowledge syncPages error: " . $e->getMessage(), ['exception' => $e]);
            $stats['errors']++;
        }
    }

    /**
     * Memproses satu item konten WordPress, validasi checksum, lalu simpan ke Sources & Knowledge.
     */
    protected function processContentItem(array $item, string $sourceType, string $category, array &$stats): void
    {
        $externalId = (string) ($item['id'] ?? '');
        $rawTitle = $item['title']['rendered'] ?? '';
        $rawContent = $item['content']['rendered'] ?? '';
        $url = $item['link'] ?? '';

        $title = trim(html_entity_decode(strip_tags($rawTitle)));
        $cleanContent = $this->cleanHtmlContent($rawContent);

        // Jika konten terlalu pendek (misalnya halaman kosong atau hanya shortcode), lewati
        if (mb_strlen($cleanContent) < 50) {
            return;
        }

        // Hitung SHA-256 checksum untuk mendeteksi perubahan
        $checksum = hash('sha256', $title . "\n" . $cleanContent);

        // Cek data di tabel chatbot_sources
        $existing = ChatbotSource::where('source_type', $sourceType)
            ->where('external_id', $externalId)
            ->first();

        if ($existing && $existing->checksum === $checksum) {
            // Konten belum berubah, lewati untuk menghemat resource
            $this->line(" [SKIP] {$title} (Tidak ada perubahan)");
            $stats['skipped']++;
            return;
        }

        $isNew = !$existing;

        // Simpan / Perbarui di ChatbotSource
        $source = ChatbotSource::updateOrCreate(
            [
                'source_type' => $sourceType,
                'external_id' => $externalId,
            ],
            [
                'title' => $title,
                'url' => $url,
                'content' => $cleanContent,
                'checksum' => $checksum,
                'is_active' => true,
                'last_synced_at' => now(),
            ]
        );

        // Sinkronkan juga ke ChatbotKnowledge agar langsung dicari oleh KnowledgeService
        $keywords = $this->generateKeywords($title, $cleanContent);

        ChatbotKnowledge::updateOrCreate(
            [
                'title' => $title,
                'source_url' => $url,
            ],
            [
                'category' => $category,
                'content' => $cleanContent,
                'keywords' => $keywords,
                'is_active' => true,
            ]
        );

        if ($isNew) {
            $this->info(" [BARU] {$title}");
            $stats['inserted']++;
        } else {
            $this->comment(" [UPDATE] {$title}");
            $stats['updated']++;
        }
    }

    /**
     * Membersihkan teks HTML menjadi teks murni yang nyaman dibaca AI.
     */
    protected function cleanHtmlContent(string $html): string
    {
        // Ganti breakline HTML menjadi newline
        $content = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $content = preg_replace('/<\/p>/i', "\n\n", $content);
        $content = preg_replace('/<\/li>/i', "\n", $content);

        // Hapus seluruh tag HTML
        $content = strip_tags($content);

        // Decode karakter HTML entities (&nbsp;, &amp;, dll)
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Rapikan spasi ganda dan baris kosong berlebih
        $content = preg_replace('/[ \t]+/', ' ', $content);
        $content = preg_replace('/\n\s*\n+/', "\n\n", $content);

        return trim($content);
    }

    /**
     * Menghasilkan kata kunci otomatis dari judul dan cuplikan konten.
     */
    protected function generateKeywords(string $title, string $content): array
    {
        $text = mb_strtolower($title . ' ' . mb_substr($content, 0, 300));
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Ambil kata unik dengan panjang >= 4
        $filtered = array_values(array_unique(array_filter($words, fn($w) => mb_strlen($w) >= 4)));

        return array_slice($filtered, 0, 8);
    }
}
