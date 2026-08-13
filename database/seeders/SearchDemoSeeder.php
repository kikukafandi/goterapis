<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SearchDemoSeeder extends Seeder
{
    public function run(): void
    {
        $img = fn ($id) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&crop=faces&q=70&w=240&h=240";

        // [nama, gender, kota, distrik, foto, status, rating, ulasan, exp, model, transport, [slug=>[price,dur], ...]]
        $data = [
            ['Pak Rahmat', 'pria', 'Yogyakarta', 'Umbulharjo', '1472099645785-5658abf4ff4e', 'pilihan', 4.9, 214, 10, 'call', 15000,
                ['pijat-kebugaran' => [85000, 60], 'pijat-capek' => [70000, 45]]],
            ['Bu Melati', 'wanita', 'Yogyakarta', 'Gondokusuman', '1544005313-94ddf0286df2', 'terdaftar', 4.8, 132, 7, 'place', 0,
                ['spa-massage' => [95000, 60]]],
            ['Pak Hendra', 'pria', 'Sleman', 'Depok', '1500648767791-00dcc994a43e', 'berpengalaman', 4.7, 88, 6, 'call', 12000,
                ['bekam' => [90000, 60]]],
            ['Bu Rina', 'wanita', 'Bantul', 'Kasihan', '1438761681033-6461ffad8d80', 'terdaftar', 4.9, 176, 8, 'place', 0,
                ['totok' => [110000, 60], 'kerik' => [60000, 30]]],
            ['Pak Yusuf', 'pria', 'Yogyakarta', 'Mergangsan', '1506794778202-cad84cf45f1d', 'pilihan', 4.8, 143, 5, 'call', 10000,
                ['spot-massage' => [80000, 45]]],
            ['Bu Sari', 'wanita', 'Sleman', 'Mlati', '1607746882042-944635dfe10e', 'berpengalaman', 4.7, 97, 4, 'call', 15000,
                ['pijat-kebugaran' => [75000, 60]]],
            ['Pak Budi', 'pria', 'Jakarta', 'Kebayoran', '1519085360753-af0119f7cbe7', 'terdaftar', 4.6, 64, 9, 'place', 0,
                ['kretek-tubuh' => [100000, 45], 'peregangan-tubuh' => [90000, 45]]],
            ['Bu Wulan', 'wanita', 'Bandung', 'Coblong', '1489424731084-a5d8b219a5bb', 'pilihan', 4.9, 201, 11, 'place', 0,
                ['spa-massage' => [90000, 60], 'pijat-seluruh-tubuh' => [120000, 90]]],
            ['Pak Anton', 'pria', 'Yogyakarta', 'Kotagede', '1521119989659-a83eee488004', 'identitas', 4.5, 41, 3, 'call', 15000,
                ['bekam-kebugaran' => [85000, 60]]],
            ['Bu Dewi', 'wanita', 'Bantul', 'Sewon', '1531123897727-8f129e1688ce', 'terdaftar', 4.8, 118, 6, 'place', 0,
                ['spa-massage' => [90000, 60]]],
            ['Bu Ayu', 'wanita', 'Jakarta', 'Menteng', '1534528741775-53994a69daeb', 'berpengalaman', 4.7, 89, 5, 'place', 0,
                ['totok' => [130000, 60]]],
            ['Bu Indah', 'wanita', 'Bandung', 'Cidadap', '1508214751196-bcfd4ca60f91', 'pilihan', 4.9, 156, 12, 'call', 20000,
                ['pijat-capek' => [80000, 45], 'pijat-tradisional' => [85000, 60]]],
        ];

        foreach ($data as $i => $row) {
            [$nama, $gender, $kota, $distrik, $foto, $status, $rating, $ulasan, $exp, $model, $transport, $services] = $row;

            $user = User::updateOrCreate(
                ['email' => 'terapis'.($i + 1).'@goterapis.test'],
                [
                    'name' => $nama,
                    'password' => Hash::make('password'),
                    'role' => 'therapist',
                    'phone' => '08120000'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'phone_verified_at' => now(),
                    'email_verified_at' => now(),
                    'avatar_path' => $img($foto),
                ],
            );

            $profile = TherapistProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $gender,
                    'bio' => "{$nama} melayani {$kota} dan sekitarnya dengan sepenuh hati. Pengalaman {$exp} tahun.",
                    'experience_years' => $exp,
                    'verification_status' => $status,
                    'is_featured' => in_array($status, ['pilihan'], true),
                    'serves_call' => $model === 'call',
                    'serves_place' => $model === 'place',
                    'province' => in_array($kota, ['Jakarta']) ? 'DKI Jakarta' : (in_array($kota, ['Bandung']) ? 'Jawa Barat' : 'DI Yogyakarta'),
                    'city' => $kota,
                    'district' => $distrik,
                    'transport_fee' => $transport,
                    'rating_avg' => $rating,
                    'reviews_count' => $ulasan,
                    'completed_count' => $ulasan + random_int(5, 40),
                    'is_available' => true,
                ],
            );

            $sync = [];
            foreach ($services as $slug => [$price, $dur]) {
                $svc = Service::where('slug', $slug)->first();
                if ($svc) {
                    $sync[$svc->id] = ['price' => $price, 'duration_min' => $dur];
                }
            }
            $profile->services()->sync($sync);
        }
    }
}
