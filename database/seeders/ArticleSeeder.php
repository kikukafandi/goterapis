<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $penulis = User::where('email', 'admin@goterapis.test')->value('id')
            ?? User::query()->value('id');

        $articles = [
            [
                'title' => 'Minyak Sereh dan Pegal yang Tak Kunjung Reda',
                'excerpt' => 'Aroma yang menenangkan bukan satu-satunya alasan sereh dipakai turun-temurun. Ada logika sederhana di baliknya.',
                'cover' => 'https://images.unsplash.com/photo-1600334129128-685c5582fd35?auto=format&fit=crop&q=70&w=1200&h=800',
                'days_ago' => 2,
                'body' => <<<'HTML'
<p>Di banyak rumah, sebotol minyak sereh selalu ada di lemari. Ia dipakai saat pundak terasa berat setelah seharian bekerja, atau ketika betis menegang selepas perjalanan jauh. Kebiasaan ini bukan sekadar warisan tanpa alasan.</p>
<p>Sereh mengandung senyawa yang memberi sensasi hangat saat dioleskan. Rasa hangat itulah yang membuat otot lebih mudah rileks, dan pijatan terasa lebih dalam tanpa harus menekan lebih keras.</p>
<h2>Kapan sebaiknya dipakai</h2>
<p>Waktu terbaik memakai minyak sereh adalah setelah mandi air hangat, ketika pori-pori terbuka dan tubuh sudah lebih tenang. Oleskan tipis, lalu urut perlahan mengikuti arah otot, bukan melawannya.</p>
<blockquote><p>Tubuh yang lelah tidak minta ditekan keras. Ia minta didengarkan.</p></blockquote>
<p>Hindari memakainya pada kulit yang lecet atau iritasi. Bila muncul rasa panas berlebihan, segera basuh dengan air biasa.</p>
<h2>Bukan pengganti istirahat</h2>
<p>Sehangat apa pun minyaknya, pegal menahun sering kali adalah cara tubuh meminta jeda. Pijat membantu meredakan, tetapi tidur yang cukup dan posisi kerja yang benar tetap jadi kunci.</p>
<p>Kalau pegal tak juga reda setelah beberapa minggu, ada baiknya berkonsultasi dengan terapis atau tenaga kesehatan. Tubuh selalu punya alasan, dan sebagian di antaranya perlu diperiksa lebih saksama.</p>
HTML,
            ],
            [
                'title' => 'Mengenal Bekam: Antara Tradisi dan Kehati-hatian',
                'excerpt' => 'Bekas bundar merah di punggung sering memancing tanya. Ini penjelasan tenang tentang apa yang sebenarnya terjadi.',
                'cover' => 'https://images.unsplash.com/photo-1591343395082-e120087004b4?auto=format&fit=crop&q=70&w=1200&h=800',
                'days_ago' => 6,
                'body' => <<<'HTML'
<p>Bekam adalah praktik tua yang dikenal di banyak kebudayaan. Prinsipnya sederhana: gelas atau cawan ditempelkan ke kulit, lalu udara di dalamnya ditarik sehingga kulit terangkat lembut ke atas.</p>
<p>Tarikan itulah yang dipercaya melancarkan aliran di area yang dituju, dan meninggalkan jejak bundar yang khas selama beberapa hari.</p>
<h2>Kering dan basah</h2>
<p>Ada dua cara yang umum. Bekam kering hanya menempelkan cawan tanpa melukai kulit. Bekam basah menyertakan sayatan halus untuk mengeluarkan sedikit darah.</p>
<p>Keduanya menuntut kebersihan yang ketat. Alat yang dipakai harus steril, dan terapisnya paham betul area mana yang aman disentuh.</p>
<blockquote><p>Yang membedakan tradisi yang menyembuhkan dari yang membahayakan sering kali cuma satu hal: kehati-hatian.</p></blockquote>
<h2>Sebelum memutuskan</h2>
<p>Bekam tidak cocok untuk semua orang. Mereka yang punya gangguan pembekuan darah, sedang hamil, atau memiliki kondisi kulit tertentu sebaiknya berkonsultasi lebih dulu.</p>
<p>Pilih terapis yang terbuka soal prosedur dan kebersihannya. Terapis yang baik tidak akan keberatan menjelaskan, dan tidak menjanjikan kesembuhan yang berlebihan.</p>
HTML,
            ],
            [
                'title' => 'Tiga Peregangan Sederhana Sebelum Tidur',
                'excerpt' => 'Tak perlu matras khusus atau waktu lama. Cukup beberapa menit untuk membantu tubuh melepas hari.',
                'cover' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&q=70&w=1200&h=800',
                'days_ago' => 11,
                'body' => <<<'HTML'
<p>Malam hari adalah waktu tubuh menurunkan tegangan yang menumpuk sepanjang siang. Peregangan ringan bisa membantu proses itu, asal dilakukan perlahan dan tanpa memaksa.</p>
<h2>Leher dan bahu</h2>
<p>Duduk tegak, lalu miringkan kepala ke satu sisi seolah menempelkan telinga ke pundak. Tahan beberapa tarikan napas, rasakan tarikan lembut di sisi leher, lalu ganti arah.</p>
<h2>Punggung bawah</h2>
<p>Berbaring telentang, tarik kedua lutut perlahan ke arah dada, peluk sebentar. Gerakan ini membantu melonggarkan punggung bawah yang lelah setelah duduk seharian.</p>
<blockquote><p>Peregangan yang benar tidak pernah menyakitkan. Ia terasa seperti tubuh menghela napas.</p></blockquote>
<p>Lakukan tanpa terburu-buru, sambil mengatur napas. Bila satu gerakan terasa nyeri, hentikan. Tujuannya melepas tegangan, bukan menambah beban baru menjelang tidur.</p>
HTML,
            ],
        ];

        foreach ($articles as $data) {
            Article::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'user_id' => $penulis,
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'body' => $data['body'],
                    'cover_path' => $data['cover'],
                    'published_at' => now()->subDays($data['days_ago']),
                ],
            );
        }
    }
}
