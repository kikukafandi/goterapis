<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\TherapistDocument;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoTherapistSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'darsono@goterapis.test'],
            [
                'name' => 'Pak Darsono',
                'password' => Hash::make('password'),
                'role' => 'therapist',
                'phone' => '081234567890',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ],
        );

        $profile = TherapistProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'gender' => 'pria',
                'bio' => 'Terapis pijat kebugaran dengan pengalaman 8 tahun. Melayani panggilan ke rumah.',
                'experience_years' => 8,
                'verification_status' => 'anggota',
                'serves_call' => true,
                'serves_place' => false,
                'province' => 'DI Yogyakarta',
                'city' => 'Yogyakarta',
                'district' => 'Umbulharjo',
                'transport_fee' => 15000,
            ],
        );

        // Layanan + harga
        $services = Service::whereIn('slug', ['pijat-kebugaran', 'spot-massage'])->get();
        $sync = [];
        foreach ($services as $s) {
            $sync[$s->id] = ['price' => 85000, 'duration_min' => 60];
        }
        $profile->services()->sync($sync);

        // Dokumen placeholder (WebP)
        $docs = [
            'ktp' => 'KTP',
            'sertifikat_pelatihan' => 'Sertifikat Pelatihan',
            'sertifikat_pengalaman' => 'Surat Pengalaman',
        ];
        foreach ($docs as $type => $label) {
            $path = "demo/{$profile->id}-{$type}.webp";
            Storage::disk('public')->put($path, $this->placeholder($label));
            TherapistDocument::updateOrCreate(
                ['therapist_profile_id' => $profile->id, 'type' => $type],
                ['path' => $path, 'status' => 'pending'],
            );
        }
    }

    /** Placeholder WebP berlabel, tanpa jaringan. */
    private function placeholder(string $label): string
    {
        $img = imagecreatetruecolor(600, 400);
        imagefill($img, 0, 0, imagecolorallocate($img, 231, 239, 230)); // daun-muda
        $ink = imagecolorallocate($img, 35, 74, 46);                     // daun-tua
        imagestring($img, 5, 30, 30, $label, $ink);
        imagestring($img, 3, 30, 60, '(dokumen demo)', $ink);

        ob_start();
        imagewebp($img, null, 70);
        imagedestroy($img);

        return ob_get_clean();
    }
}
