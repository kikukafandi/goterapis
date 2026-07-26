<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['Temulawak Iris Kering', 'bahan-herbal', 'Temulawak pilihan untuk seduhan hangat sehari-hari.', 'Irisan temulawak matang yang dikeringkan perlahan agar aroma tanah dan rasa khasnya tetap terjaga.', 28000, 42, 150, 'Wonogiri, Jawa Tengah', 'Simpan rapat di tempat kering dan jauh dari sinar matahari.', 'temulawak.webp', true],
            ['Jahe Merah Utuh Kering', 'bahan-herbal', 'Jahe merah kering beraroma tajam dan hangat.', 'Rimpang jahe merah pilihan untuk racikan minuman, rebusan herbal, atau campuran rempah rumahan.', 32000, 35, 200, 'Batu, Jawa Timur', 'Simpan dalam wadah kedap udara di tempat sejuk dan kering.', 'jahe-merah.webp', false],
            ['Bunga Telang Kering', 'bahan-herbal', 'Kelopak telang utuh untuk seduhan berwarna alami.', 'Bunga telang yang dipetik dan dikeringkan dengan cermat, cocok dinikmati sebagai seduhan ringan.', 24000, 28, 40, 'Sleman, DI Yogyakarta', 'Hindari kelembapan dan tutup kembali kemasan setelah digunakan.', 'bunga-telang.webp', false],
            ['Wedang Rempah Nusantara', 'produk-herbal', 'Racikan jahe, serai, kayu manis, dan kapulaga.', 'Campuran rempah siap seduh dengan rasa hangat seimbang untuk menemani pagi atau malam hari.', 45000, 50, 250, 'Surakarta, Jawa Tengah', 'Simpan di tempat kering; habiskan dalam 30 hari setelah dibuka.', 'wedang-rempah.webp', true],
            ['Beras Kencur Bubuk', 'produk-herbal', 'Minuman tradisional praktis dengan rasa segar lembut.', 'Bubuk beras kencur siap seduh yang memadukan kencur aromatik, beras, dan gula aren secukupnya.', 38000, 31, 200, 'Bantul, DI Yogyakarta', 'Simpan tertutup rapat dan gunakan sendok yang kering.', 'beras-kencur.webp', false],
            ['Madu Hutan Sumbawa', 'produk-herbal', 'Madu hutan berkarakter lembut dalam botol praktis.', 'Madu hasil panen musiman dengan warna dan aroma alami yang dapat berbeda pada setiap produksi.', 89000, 18, 350, 'Sumbawa, Nusa Tenggara Barat', 'Simpan pada suhu ruang, tidak perlu dimasukkan ke lemari es.', 'madu-hutan.webp', true],
            ['Minyak Pijat Jahe', 'minyak-terapi', 'Minyak pijat hangat dengan aroma jahe yang nyaman.', 'Perpaduan minyak kelapa dan ekstrak jahe untuk membantu sesi pijat terasa hangat dan rileks.', 62000, 24, 120, 'Ubud, Bali', 'Tutup rapat, simpan di tempat teduh, dan hindari panas langsung.', 'minyak-jahe.webp', false],
            ['Minyak Sereh Wangi', 'minyak-terapi', 'Minyak terapi ringan dengan aroma sereh yang segar.', 'Formula minyak pijat beraroma sereh wangi yang mudah diratakan dan nyaman untuk perawatan tubuh.', 55000, 27, 100, 'Garut, Jawa Barat', 'Simpan tegak di tempat sejuk dan jauhkan dari jangkauan anak.', 'minyak-sereh.webp', false],
            ['Set Batu Pijat Basalt', 'perlengkapan-terapis', 'Delapan batu basalt halus untuk terapi panas.', 'Batu pijat dengan ukuran bertahap dan permukaan halus, dilengkapi kantong kain untuk penyimpanan.', 185000, 9, 1800, 'Magelang, Jawa Tengah', 'Bersihkan dan keringkan sempurna sebelum disimpan dalam kantong.', 'batu-pijat.webp', false],
            ['Cangkir Bekam Silikon', 'perlengkapan-terapis', 'Set enam cangkir silikon lentur dan mudah dibersihkan.', 'Cangkir silikon dalam beberapa ukuran untuk kebutuhan terapi kering oleh praktisi terlatih.', 125000, 14, 420, 'Bandung, Jawa Barat', 'Cuci setelah digunakan, keringkan, lalu simpan tanpa tekanan.', 'cangkir-bekam.webp', false],
        ];

        foreach ($products as [$name, $category, $shortDescription, $description, $price, $stock, $weight, $origin, $storage, $image, $promoted]) {
            Product::updateOrCreate(
                ['slug' => str($name)->slug()],
                [
                    'name' => $name,
                    'category' => $category,
                    'short_description' => $shortDescription,
                    'description' => $description,
                    'price' => $price,
                    'stock' => $stock,
                    'weight_grams' => $weight,
                    'origin' => $origin,
                    'storage_instructions' => $storage,
                    'image_path' => "images/produk/{$image}",
                    'is_promoted' => $promoted,
                    'status' => 'published',
                    'published_at' => now(),
                ],
            );
        }
    }
}
