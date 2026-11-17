<?php

namespace Database\Seeders;

use App\Models\ChatbotFaq;
use App\Models\ChatbotKnowledge;
use Illuminate\Database\Seeder;

class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Data FAQ Resmi
        $faqs = [
            [
                'question' => 'Siapa pengasuh Pondok Pesantren Bahrul Maghfiroh Malang?',
                'answer' => 'Pengasuh utama Pondok Pesantren Bahrul Maghfiroh Malang saat ini adalah Prof. Dr. Ir. Mohammad Bisri, M.S. (Mantan Rektor Universitas Brawijaya). Beliau merupakan kakak kandung dari pendiri awal pesantren, almarhum KH. Luqman Al Karim (Gus Lukman).',
                'category' => 'Pengasuh',
                'keywords' => ['pengasuh', 'kyai', 'gus bisri', 'mohammad bisri', 'pimpinan', 'siapa pengasuh'],
                'is_active' => true,
            ],
            [
                'question' => 'Siapa pendiri Pondok Pesantren Bahrul Maghfiroh Malang?',
                'answer' => 'Pondok Pesantren Bahrul Maghfiroh didirikan oleh almarhum KH. Luqman Al Karim (dikenal sebagai Gus Lukman) bersama keluarga besar KH. Abdullah Fattah, yang mulai dirintis pada tahun 1995 dan diresmikan pada tahun 1997 di bawah naungan Yayasan Bahrul Maghfiroh Cinta Indonesia (BMCI).',
                'category' => 'Sejarah',
                'keywords' => ['pendiri', 'sejarah', 'gus lukman', 'luqman al karim', 'asal usul', 'berdiri'],
                'is_active' => true,
            ],
            [
                'question' => 'Di mana alamat lengkap Pondok Pesantren Bahrul Maghfiroh Malang?',
                'answer' => 'Pondok Pesantren Bahrul Maghfiroh berlokasi di Jl. Joyo Agung No. 2, Kelurahan Tlogomas, Kecamatan Lowokwaru, Kota Malang, Jawa Timur 65144.',
                'category' => 'Lokasi',
                'keywords' => ['alamat', 'lokasi', 'tempat', 'dimana', 'kota malang', 'tlogomas', 'joyo agung'],
                'is_active' => true,
            ],
            [
                'question' => 'Apa saja jenjang pendidikan yang tersedia di Bahrul Maghfiroh?',
                'answer' => 'Pondok Pesantren Bahrul Maghfiroh menyediakan jenjang pendidikan terpadu, meliputi: Pendidikan Formal (PAUD/TK, SD, SMP, SMP Putri, MTs, SMA, dan MA Bahrul Maghfiroh) serta Pendidikan Non-Formal (Madrasah Diniyah Salafiyah, Tahfidzul Qur\'an, dan pelatihan kewirausahaan santri).',
                'category' => 'Pendidikan',
                'keywords' => ['jenjang', 'pendidikan', 'sekolah', 'smp', 'sma', 'smk', 'mts', 'ma', 'sd', 'tk', 'tahfidz'],
                'is_active' => true,
            ],
            [
                'question' => 'Bagaimana cara mendaftar santri baru di Pondok Pesantren Bahrul Maghfiroh?',
                'answer' => 'Pendaftaran Santri Baru (PSBM) dapat dilakukan secara online melalui portal resmi psbm.bahrulmaghfirohmalang.or.id atau datang langsung ke Kantor Sekretariat Pendaftaran di Jl. Joyo Agung No. 2, Tlogomas, Lowokwaru, Malang. Pendaftaran dibuka untuk sistem Mukim (Asrama) maupun Fullday.',
                'category' => 'Pendaftaran',
                'keywords' => ['daftar', 'pendaftaran', 'psbm', 'santri baru', 'cara daftar', 'alur pendaftaran', 'online'],
                'is_active' => true,
            ],
            [
                'question' => 'Apa saja syarat pendaftaran santri baru (PSBM)?',
                'answer' => 'Persyaratan umum pendaftaran santri baru antara lain: 1) Scan/Foto Kartu Keluarga (KK), 2) KTP Orang Tua/Wali, 3) Akta Kelahiran Calon Santri, 4) NISN dan Surat Keterangan Aktif dari sekolah asal, serta 5) Mengisi formulir pendaftaran di portal resmi PSBM.',
                'category' => 'Pendaftaran',
                'keywords' => ['syarat', 'persyaratan', 'berkas', 'dokumen', 'kk', 'ktp', 'akta', 'nisn'],
                'is_active' => true,
            ],
            [
                'question' => 'Apakah santri wajib tinggal di asrama (mukim)?',
                'answer' => 'Pondok Pesantren Bahrul Maghfiroh menyediakan 2 sistem belajar: 1) Sistem Mukim (santri tinggal dan menetap di asrama pesantren dengan pembinaan 24 jam), dan 2) Sistem Fullday (santri mengikuti pembelajaran formal dan kepesantrenan dari pagi hingga sore tanpa menginap di asrama).',
                'category' => 'Sistem Belajar',
                'keywords' => ['mukim', 'fullday', 'asrama', 'menginap', 'pulang', 'kamar', 'tinggal'],
                'is_active' => true,
            ],
            [
                'question' => 'Berapa nomor kontak dan layanan resmi Pondok Pesantren Bahrul Maghfiroh?',
                'answer' => 'Layanan informasi dan konfirmasi resmi dapat dihubungi melalui WhatsApp Admin: +62 811-3648-800, Email: yayasanbmci@gmail.com, atau situs web resmi https://bahrulmaghfirohmalang.or.id.',
                'category' => 'Kontak',
                'keywords' => ['kontak', 'whatsapp', 'wa', 'nomor', 'telepon', 'email', 'hubungi', 'sekretariat'],
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            ChatbotFaq::updateOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }

        // 2. Data Pengetahuan Mendalam (Knowledge)
        $knowledges = [
            [
                'title' => 'Profil dan Visi Misi Pondok Pesantren Bahrul Maghfiroh Malang',
                'category' => 'Profil',
                'content' => "Pondok Pesantren Bahrul Maghfiroh Malang berada di bawah naungan Yayasan Bahrul Maghfiroh Cinta Indonesia (BMCI). Pesantren ini memiliki visi mencetak generasi santri yang beriman, berakhlak mulia, berwawasan luas, mandiri, dan berjiwa enterpreneur (kewirausahaan). Keunggulan Bahrul Maghfiroh adalah memadukan kedalaman ilmu agama (salafiyah dan kitab kuning) dengan kurikulum pendidikan umum nasional serta penguasaan teknologi digital dan kemandirian usaha santri.",
                'keywords' => ['profil', 'visi', 'misi', 'bahrul maghfiroh', 'pesantren', 'salaf', 'modern', 'enterpreneur'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/tentang-kami/',
                'is_active' => true,
            ],
            [
                'title' => 'Struktur Kepengasuhan dan Sejarah Pondok Pesantren Bahrul Maghfiroh',
                'category' => 'Sejarah',
                'content' => "Pondok Pesantren Bahrul Maghfiroh didirikan oleh KH. Luqman Al Karim (Gus Lukman) bersama keluarga besar KH. Abdullah Fattah. Perintisan dimulai pada tahun 1995 dan diresmikan pada tahun 1997. Saat ini, kepemimpinan dan pengasuhan utama dipimpin oleh Prof. Dr. Ir. Mohammad Bisri, M.S., Guru Besar sekaligus mantan Rektor Universitas Brawijaya Malang, didampingi oleh jajaran dewan asatidz dan pengurus yayasan BMCI.",
                'keywords' => ['sejarah', 'pendiri', 'gus lukman', 'luqman al karim', 'gus bisri', 'mohammad bisri', 'pengasuh'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/sejarah/',
                'is_active' => true,
            ],
            [
                'title' => 'Unit Pendidikan Formal dan Kepesantrenan',
                'category' => 'Pendidikan',
                'content' => "Pondok Pesantren Bahrul Maghfiroh Malang menyelenggarakan jenjang pendidikan lengkap dari usia dini hingga menengah atas:\n1. PAUD/TK Bahrul Maghfiroh\n2. SD Bahrul Maghfiroh\n3. SMP Bahrul Maghfiroh (Reguler)\n4. SMP Putri Bahrul Maghfiroh (Khusus Santriwati)\n5. MTs Bahrul Maghfiroh\n6. SMA Bahrul Maghfiroh\n7. MA Bahrul Maghfiroh\n8. Madrasah Diniyah Salafiyah (Kajian Kitab Kuning Fiqih, Aqidah, Akhlak, Nahwu, Shorof)\n9. Program Tahfidzul Qur'an (Penghafal Al-Qur'an terpadu dengan bimbingan ustadz/ustadzah bersanad).",
                'keywords' => ['unit pendidikan', 'jenjang', 'smp', 'sma', 'smk', 'mts', 'ma', 'sd', 'tk', 'diniyah', 'tahfidz'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/pendidikan/',
                'is_active' => true,
            ],
            [
                'title' => 'Sistem Belajar Mukim (Asrama) dan Fullday',
                'category' => 'Sistem Belajar',
                'content' => "Untuk mengakomodasi kebutuhan wali santri, Bahrul Maghfiroh menghadirkan dua pilihan sistem belajar:\n1. Program Mukim: Santri menetap di asrama pondok (asrama putra dan asrama putri terpisah dengan penjagaan dan pendampingan 24 jam). Kegiatan mencakup sholat berjamaah 5 waktu, tahfidz, kajian kitab diniyah ba'da subuh dan maghrib, serta sekolah formal di pagi hingga siang hari.\n2. Program Fullday: Diperuntukkan bagi santri yang tidak tinggal di asrama, mengikuti pembelajaran sekolah dan materi kepesantrenan dari pagi hingga sore hari sebelum kembali ke rumah masing-masing.",
                'keywords' => ['mukim', 'fullday', 'asrama', 'sistem belajar', 'kegiatan harian', 'jadwal santri'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/program/',
                'is_active' => true,
            ],
            [
                'title' => 'Prosedur Penerimaan Santri Baru (PSBM) dan Persyaratan Berkas',
                'category' => 'Pendaftaran',
                'content' => "Penerimaan Santri Baru (PSBM) Bahrul Maghfiroh dibuka setiap tahun ajaran baru dengan tahapan:\n1. Pengisian formulir online melalui psbm.bahrulmaghfirohmalang.or.id atau langsung di Sekretariat PSB.\n2. Pengunggahan berkas: KK, KTP orang tua, Akta Lahir, dan NISN/Surat Aktif Sekolah Asal.\n3. Uji observasi/pemetaan minat santri (membaca Al-Qur'an dan wawancara dasar).\n4. Pengumuman hasil dan registrasi ulang santri.\nInformasi pendaftaran dapat dikonfirmasi ke WhatsApp resmi panitia di +62 811-3648-800.",
                'keywords' => ['psbm', 'syarat pendaftaran', 'alur daftar', 'jadwal pendaftaran', 'biaya daftar', 'observasi'],
                'source_url' => 'https://psbm.bahrulmaghfirohmalang.or.id/',
                'is_active' => true,
            ],
            [
                'title' => 'Fasilitas Pondok Pesantren Bahrul Maghfiroh',
                'category' => 'Fasilitas',
                'content' => "Fasilitas penunjang di Pesantren Bahrul Maghfiroh Malang meliputi: Masjid jami' pusat kegiatan ibadah, gedung asrama putra dan putri terpisah, ruang kelas ber-AC dan multimedia, laboratorium komputer, perpustakaan, sarana olahraga (lapangan futsal/basket/voli), unit kewirausahaan santri (pengolahan keju, peternakan, mini market santri), klinik kesehatan pesantren, dan Balai Latihan Kerja (BLK).",
                'keywords' => ['fasilitas', 'sarana', 'asrama', 'masjid', 'lapangan', 'laboratorium', 'blk', 'koperasi'],
                'source_url' => 'https://bahrulmaghfirohmalang.or.id/fasilitas/',
                'is_active' => true,
            ],
        ];

        foreach ($knowledges as $knowledge) {
            ChatbotKnowledge::updateOrCreate(
                ['title' => $knowledge['title']],
                $knowledge
            );
        }
    }
}
