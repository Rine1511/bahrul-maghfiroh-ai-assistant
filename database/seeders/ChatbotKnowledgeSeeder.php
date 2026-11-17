<?php

namespace Database\Seeders;

use App\Models\ChatbotFaq;
use App\Models\ChatbotKnowledge;
use Illuminate\Database\Seeder;

class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Data FAQ
        |--------------------------------------------------------------------------
        */

        $faqs = [
            [
                'question' => 'Siapa pengasuh Pondok Pesantren Bahrul Maghfiroh Malang?',
                'answer' => 'Pengasuh utama Pondok Pesantren Bahrul Maghfiroh Malang saat ini adalah Prof. Dr. Ir. Mohammad Bisri, M.S. (Mantan Rektor Universitas Brawijaya).',
                'category' => 'Pengasuh',
                'keywords' => ['pengasuh', 'kyai', 'gus bisri', 'mohammad bisri', 'pimpinan'],
            ],
            [
                'question' => 'Siapa pendiri Pondok Pesantren Bahrul Maghfiroh Malang?',
                'answer' => 'Pondok Pesantren Bahrul Maghfiroh didirikan oleh almarhum KH. Luqman Al Karim (Gus Lukman) bersama keluarga besar KH. Abdullah Fattah. Perintisan dimulai pada tahun 1995 dan diresmikan pada tahun 1997.',
                'category' => 'Sejarah',
                'keywords' => ['pendiri', 'sejarah', 'gus lukman', 'luqman al karim'],
            ],
            [
                'question' => 'Di mana alamat lengkap Pondok Pesantren Bahrul Maghfiroh Malang?',
                'answer' => 'Pondok Pesantren Bahrul Maghfiroh berlokasi di Jl. Joyo Agung No. 2, Kelurahan Tlogomas, Kecamatan Lowokwaru, Kota Malang, Jawa Timur 65144.',
                'category' => 'Lokasi',
                'keywords' => ['alamat', 'lokasi', 'dimana', 'tlogomas', 'joyo agung'],
            ],
            [
                'question' => 'Apa saja jenjang pendidikan yang tersedia di Bahrul Maghfiroh?',
                'answer' => 'Pondok Pesantren Bahrul Maghfiroh menyediakan jenjang pendidikan formal dan nonformal, antara lain PAUD/TK, SD, SMP, MTs, SMA, MA, Madrasah Diniyah Salafiyah, Tahfidzul Quran, dan pelatihan kewirausahaan santri.',
                'category' => 'Pendidikan',
                'keywords' => ['jenjang', 'pendidikan', 'sekolah', 'smp', 'sma', 'mts', 'ma', 'sd', 'tk', 'tahfidz'],
            ],
            [
                'question' => 'Bagaimana cara mendaftar santri baru?',
                'answer' => 'Pendaftaran Santri Baru dapat dilakukan secara online melalui portal psbm.bahrulmaghfirohmalang.or.id atau datang langsung ke Kantor Sekretariat Pendaftaran di Jl. Joyo Agung No. 2, Tlogomas, Lowokwaru, Malang.',
                'category' => 'Pendaftaran',
                'keywords' => ['daftar', 'pendaftaran', 'psbm', 'santri baru', 'cara daftar'],
            ],
            [
                'question' => 'Apa saja syarat pendaftaran santri baru?',
                'answer' => 'Persyaratan umum pendaftaran antara lain Kartu Keluarga, KTP orang tua/wali, Akta Kelahiran calon santri, NISN dan surat keterangan aktif dari sekolah asal, serta pengisian formulir pendaftaran.',
                'category' => 'Pendaftaran',
                'keywords' => ['syarat', 'persyaratan', 'berkas', 'dokumen', 'kk', 'ktp', 'akta', 'nisn'],
            ],
            [
                'question' => 'Apakah santri wajib tinggal di asrama?',
                'answer' => 'Pondok menyediakan dua sistem belajar, yaitu Mukim (santri tinggal di asrama) dan Fullday (santri mengikuti pembelajaran dari pagi hingga sore tanpa menginap).',
                'category' => 'Sistem Belajar',
                'keywords' => ['mukim', 'fullday', 'asrama', 'menginap', 'tinggal'],
            ],
            [
                'question' => 'Bagaimana cara menghubungi Pondok Pesantren Bahrul Maghfiroh?',
                'answer' => 'Informasi dan konfirmasi dapat dilakukan melalui WhatsApp Admin: +62 811-3648-800, Email: yayasanbmci@gmail.com, atau website resmi https://bahrulmaghfirohmalang.or.id.',
                'category' => 'Kontak',
                'keywords' => ['kontak', 'whatsapp', 'wa', 'nomor', 'telepon', 'email', 'hubungi'],
            ],
        ];

        foreach ($faqs as $faq) {
            ChatbotFaq::updateOrCreate(
                ['question' => $faq['question']],
                $faq + ['is_active' => true]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Data Knowledge
        |--------------------------------------------------------------------------
        */

        $knowledges = [
            [
                'title' => 'Profil dan Visi Misi Pondok Pesantren Bahrul Maghfiroh Malang',
                'category' => 'Profil',
                'content' => 'Pondok Pesantren Bahrul Maghfiroh Malang berada di bawah naungan Yayasan Bahrul Maghfiroh Cinta Indonesia (BMCI). Pesantren ini memiliki visi mencetak generasi santri yang beriman, berakhlak mulia, berwawasan luas, mandiri, dan berjiwa entrepreneur. Bahrul Maghfiroh memadukan pendidikan agama dengan pendidikan umum serta pengembangan kewirausahaan.',
                'keywords' => ['profil', 'visi', 'misi', 'bahrul maghfiroh', 'pesantren', 'salaf', 'entrepreneur'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/tentang-kami/',
            ],
            [
                'title' => 'Sejarah dan Kepengasuhan Pondok Pesantren Bahrul Maghfiroh',
                'category' => 'Sejarah',
                'content' => 'Pondok Pesantren Bahrul Maghfiroh dirintis oleh KH. Luqman Al Karim (Gus Lukman) bersama keluarga besar KH. Abdullah Fattah. Perintisan dimulai pada tahun 1995 dan diresmikan pada tahun 1997. Informasi mengenai kepengasuhan dan struktur pengurus dapat dikonfirmasi melalui pihak pondok.',
                'keywords' => ['sejarah', 'pendiri', 'gus lukman', 'luqman al karim', 'pengasuh'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/sejarah/',
            ],
            [
                'title' => 'Unit Pendidikan Formal dan Kepesantrenan',
                'category' => 'Pendidikan',
                'content' => "Pondok Pesantren Bahrul Maghfiroh menyelenggarakan pendidikan formal dan kepesantrenan, meliputi PAUD/TK, SD, SMP, MTs, SMA, MA, Madrasah Diniyah Salafiyah, dan program Tahfidzul Quran.",
                'keywords' => ['pendidikan', 'jenjang', 'smp', 'sma', 'mts', 'ma', 'sd', 'tk', 'diniyah', 'tahfidz'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/pendidikan/',
            ],
            [
                'title' => 'Sistem Belajar Mukim dan Fullday',
                'category' => 'Sistem Belajar',
                'content' => 'Bahrul Maghfiroh menyediakan dua pilihan sistem belajar. Program Mukim memungkinkan santri tinggal di asrama dan mengikuti pembinaan pesantren. Program Fullday memungkinkan santri mengikuti kegiatan pendidikan dari pagi hingga sore tanpa menetap di asrama.',
                'keywords' => ['mukim', 'fullday', 'asrama', 'sistem belajar', 'kegiatan santri'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/program/',
            ],
            [
                'title' => 'Prosedur Penerimaan Santri Baru',
                'category' => 'Pendaftaran',
                'content' => 'Penerimaan Santri Baru dilakukan melalui pengisian formulir pendaftaran, pengumpulan dokumen persyaratan, proses seleksi atau observasi jika diperlukan, pengumuman hasil, dan registrasi ulang. Informasi terbaru dapat dikonfirmasi melalui sekretariat pendaftaran.',
                'keywords' => ['psbm', 'syarat pendaftaran', 'alur daftar', 'santri baru', 'observasi'],
                'source_url' => 'https://psbm.bahrulmaghfirohmalang.or.id/',
            ],
            [
                'title' => 'Fasilitas Pondok Pesantren Bahrul Maghfiroh',
                'category' => 'Fasilitas',
                'content' => 'Fasilitas pondok meliputi masjid, gedung asrama putra dan putri, ruang kelas, laboratorium, perpustakaan, sarana olahraga, serta berbagai fasilitas pendukung kegiatan pendidikan dan kewirausahaan santri.',
                'keywords' => ['fasilitas', 'sarana', 'asrama', 'masjid', 'lapangan', 'laboratorium', 'perpustakaan'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/fasilitas/',
            ],
        ];

        foreach ($knowledges as $knowledge) {
            ChatbotKnowledge::updateOrCreate(
                [
                    'source_type' => 'manual',
                    'title' => $knowledge['title'],
                ],
                $knowledge + [
                    'source_type' => 'manual',
                    'is_active' => true,
                ]
            );
        }
    }
}
