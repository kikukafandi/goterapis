<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'pijat' => [
                'Pijat Tradisional', 'Pijat Kebugaran', 'Pijat Olahraga',
            ],
            'bekam' => ['Bekam'],
            'kretek' => ['Kretek'],
            'refleksi' => ['Pijat Refleksi Kaki', 'Pijat Refleksi Tangan'],
            'lainnya' => ['Kerik', 'Totok', 'Perawatan Tubuh Tradisional'],
        ];

        $keep = [];
        foreach ($catalog as $category => $names) {
            foreach ($names as $name) {
                $keep[] = Str::slug($name);
                Service::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'category' => $category, 'is_active' => true],
                );
            }
        }

        // Nonaktifkan layanan lama yang tak lagi ada di katalog (tetap disimpan agar riwayat terapis utuh).
        Service::whereNotIn('slug', $keep)->update(['is_active' => false]);
    }
}
