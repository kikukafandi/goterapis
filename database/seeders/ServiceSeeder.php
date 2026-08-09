<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'pijat' => [
                'Pijat Tradisional' => null,
                'Pijat Kebugaran' => null,
                'Pijat Olahraga' => null,
                'Spot Massage' => null,
                'Spa Massage' => 'wanita',
            ],
            'bekam' => ['Bekam' => null],
            'kretek' => ['Kretek' => null],
            'lainnya' => ['Kerik' => null, 'Totok' => null, 'Perawatan Tubuh Tradisional' => null],
        ];

        $keep = [];
        foreach ($catalog as $category => $services) {
            foreach ($services as $name => $allowedGender) {
                $keep[] = Str::slug($name);
                Service::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'category' => $category, 'allowed_gender' => $allowedGender, 'is_active' => true],
                );
            }
        }

        $obsoleteIds = Service::where('category', 'refleksi')
            ->orWhereIn('slug', ['pijat-relaksasi', 'relaksasi', 'bekam-kering', 'totok-wajah'])
            ->pluck('id');
        Service::whereIn('id', $obsoleteIds)->update(['is_active' => false]);
        DB::table('therapist_service')->whereIn('service_id', $obsoleteIds)->delete();
        Service::whereNotIn('slug', $keep)->update(['is_active' => false]);
    }
}
