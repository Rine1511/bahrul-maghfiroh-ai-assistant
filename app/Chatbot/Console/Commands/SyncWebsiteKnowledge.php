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
    protected $signature = 'app:sync-website-knowledge
                            {--limit=20 : Jumlah artikel atau halaman maksimal yang disinkronkan}';

    /**
     * Deskripsi console command.
     */
    protected $description = 'Sinkronisasi konten publik dari website utama Pondok Pesantren Bahrul Maghfiroh ke database';

    /**
     * Eksekusi console command.
     */
    public function handle(): int
    {
        $baseUrl = rtrim(
            (string) config(
                'services.website.url',
                'https://bahrulmaghfirohmalang.or.id'
            ),
            '/'
        );

        $limit = max(1, min(100, (int) $this->option('limit')));

        $this->info("Memulai sinkronisasi konten dari website: {$baseUrl} (Maks {$limit} data)...");

        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        // 1. Sinkronisasi berita dan postingan publik
        $this->info("\n--- Mengambil Berita & Artikel Terbaru ---");

        $this->syncPosts($baseUrl, $limit, $stats);

        // 2. Sinkronisasi halaman publik
        $this->info("\n--- Mengambil Halaman Informasi Publik ---");

        $this->syncPages($baseUrl, $limit, $stats);

        // 3. Menampilkan hasil sinkronisasi
        $this->newLine();

        $this->info("==========================================");
        $this->info("SINKRONISASI SELESAI!");
        $this->info("Data Baru Ditambahkan : {$stats['inserted']}");
        $this->info("Data Diperbarui       : {$stats['updated']}");
        $this->info("Data Tidak Berubah    : {$stats['skipped']}");

        if ($stats['errors'] > 0) {
            $this->warn("Data Gagal Diproses   : {$stats['errors']}");
        }

        $this->info("==========================================");

        return $stats['errors'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Mengambil dan memproses posts/berita dari WordPress REST API.
     */
    protected function syncPosts(
        string $baseUrl,
        int $limit,
        array &$stats
    ): void {
        try {
            $endpoint = "{$baseUrl}/wp-json/wp/v2/posts";

            $response = Http::timeout(30)
                ->acceptJson()
                ->get($endpoint, [
                    'per_page' => $limit,
                    'status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'desc',
                ]);

            if ($response->failed()) {
                $this->error(
                    "Gagal menghubungi endpoint posts: Status {$response->status()}"
                );

                $stats['errors']++;

                return;
            }

            $posts = $response->json();

            if (!is_array($posts)) {
                $this->warn('Format respons posts tidak valid.');

                $stats['errors']++;

                return;
            }

            foreach ($posts as $item) {
                if (!is_array($item)) {
                    $stats['skipped']++;

                    continue;
                }

                $this->processContentItem(
                    $item,
                    'website_post',
                    'Berita & Kegiatan',
                    $stats
                );
            }

        } catch (\Throwable $e) {
            $this->error(
                'Error saat sinkronisasi posts: ' . $e->getMessage()
            );

            Log::error('SyncWebsiteKnowledge syncPosts error', [
                'exception' => $e,
            ]);

            $stats['errors']++;
        }
    }

    /**
     * Mengambil dan memproses halaman (pages) dari WordPress REST API.
     */
    protected function syncPages(
        string $baseUrl,
        int $limit,
        array &$stats
    ): void {
        try {
            $endpoint = "{$baseUrl}/wp-json/wp/v2/pages";

            $response = Http::timeout(30)
                ->acceptJson()
                ->get($endpoint, [
                    'per_page' => $limit,
                    'status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'desc',
                ]);

            if ($response->failed()) {
                $this->error(
                    "Gagal menghubungi endpoint pages: Status {$response->status()}"
                );

                $stats['errors']++;

                return;
            }

            $pages = $response->json();

            if (!is_array($pages)) {
                $this->warn('Format respons pages tidak valid.');

                $stats['errors']++;

                return;
            }

            // Kata kunci halaman yang tidak perlu dimasukkan ke knowledge chatbot.
            $ignoredKeywords = [
                'checkout',
                'profile',
                'cart',
                'dashboard',
                'venue',
                'instructor',
            ];

            foreach ($pages as $item) {
                if (!is_array($item)) {
                    $stats['skipped']++;

                    continue;
                }

                $titleLower = mb_strtolower(
                    $item['title']['rendered'] ?? ''
                );

                $shouldSkip = false;

                foreach ($ignoredKeywords as $keyword) {
                    if (str_contains($titleLower, $keyword)) {
                        $shouldSkip = true;

                        break;
                    }
                }

                if ($shouldSkip) {
                    $stats['skipped']++;

                    continue;
                }

                $this->processContentItem(
                    $item,
                    'website_page',
                    'Halaman Website',
                    $stats
                );
            }

        } catch (\Throwable $e) {
            $this->error(
                'Error saat sinkronisasi pages: ' . $e->getMessage()
            );

            Log::error('SyncWebsiteKnowledge syncPages error', [
                'exception' => $e,
            ]);

            $stats['errors']++;
        }
    }

    /**
     * Memproses satu item konten WordPress,
     * memeriksa checksum, lalu menyimpan ke Sources dan Knowledge.
     */
    protected function processContentItem(
        array $item,
        string $sourceType,
        string $category,
        array &$stats
    ): void {
        $externalId = (string) ($item['id'] ?? '');

        $rawTitle = $item['title']['rendered'] ?? '';
        $rawContent = $item['content']['rendered'] ?? '';
        $url = $item['link'] ?? '';

        // Validasi data dasar.
        if (
            $externalId === '' ||
            !is_string($rawTitle) ||
            !is_string($rawContent) ||
            !is_string($url) ||
            !filter_var($url, FILTER_VALIDATE_URL)
        ) {
            $this->warn('[SKIP] Data konten tidak lengkap atau tidak valid.');

            $stats['skipped']++;

            return;
        }

        // Membersihkan judul dan isi konten.
        $title = trim(
            html_entity_decode(
                strip_tags($rawTitle),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )
        );

        $cleanContent = $this->cleanHtmlContent($rawContent);

        // Lewati konten kosong atau terlalu pendek.
        if ($title === '' || mb_strlen($cleanContent) < 50) {
            $this->line(" [SKIP] {$title} (Konten terlalu pendek)");

            $stats['skipped']++;

            return;
        }

        // Menghitung SHA-256 checksum untuk mendeteksi perubahan konten.
        $checksum = hash(
            'sha256',
            $title . "\n" . $cleanContent
        );

        // Memeriksa data berdasarkan identitas sumber.
        $existing = ChatbotSource::where('source_type', $sourceType)
            ->where('external_id', $externalId)
            ->first();

        // Jika checksum sama, konten tidak perlu diperbarui.
        if ($existing && $existing->checksum === $checksum) {
            $this->line(" [SKIP] {$title} (Tidak ada perubahan)");

            $stats['skipped']++;

            return;
        }

        $isNew = !$existing;

        // Menyimpan atau memperbarui sumber konten.
        ChatbotSource::updateOrCreate(
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

        // Membuat kata kunci dari judul dan isi konten.
        $keywords = $this->generateKeywords(
            $title,
            $cleanContent
        );

        // Menyimpan konten agar dapat digunakan KnowledgeService.
        ChatbotKnowledge::updateOrCreate(
            [
                'source_url' => $url,
            ],
            [
                'title' => $title,
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
     * Membersihkan HTML menjadi teks murni yang nyaman dibaca AI.
     */
    protected function cleanHtmlContent(string $html): string
    {
        // Menghapus blok script dan style.
        $content = preg_replace(
            '/<(script|style)\b[^>]*>.*?<\/\1>/is',
            ' ',
            $html
        ) ?? $html;

        // Mengubah elemen HTML tertentu menjadi pemisah baris.
        $content = preg_replace(
            '/<br\s*\/?>/i',
            "\n",
            $content
        ) ?? $content;

        $content = preg_replace(
            '/<\/(p|div|h[1-6]|li|section|article|blockquote|tr)>/i',
            "\n",
            $content
        ) ?? $content;

        // Menghapus seluruh tag HTML.
        $content = strip_tags($content);

        // Mengubah HTML entities menjadi karakter normal.
        $content = html_entity_decode(
            $content,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        // Menormalkan karakter baris baru.
        $content = str_replace(
            ["\r\n", "\r"],
            "\n",
            $content
        );

        // Menghapus spasi dan tab berlebih.
        $content = preg_replace(
            '/[ \t]+/',
            ' ',
            $content
        ) ?? $content;

        // Menghapus spasi di awal dan akhir setiap baris.
        $content = preg_replace(
            '/^[ \t]+|[ \t]+$/m',
            '',
            $content
        ) ?? $content;

        // Mengurangi baris kosong berlebihan.
        $content = preg_replace(
            '/\n{3,}/',
            "\n\n",
            $content
        ) ?? $content;

        return trim($content);
    }

    /**
     * Menghasilkan kata kunci otomatis dari judul dan cuplikan konten.
     */
    protected function generateKeywords(
        string $title,
        string $content
    ): array {
        $text = mb_strtolower(
            $title . ' ' . mb_substr($content, 0, 300)
        );

        // Memisahkan teks berdasarkan karakter selain huruf dan angka.
        $words = preg_split(
            '/[^\p{L}\p{N}]+/u',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if (!is_array($words)) {
            return [];
        }

        // Mengambil kata unik dengan panjang minimal 4 karakter.
        $filtered = array_values(
            array_unique(
                array_filter(
                    $words,
                    fn ($word) => mb_strlen($word) >= 4
                )
            )
        );

        // Maksimal 8 kata kunci.
        return array_slice($filtered, 0, 8);
    }
}