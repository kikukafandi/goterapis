<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Support\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class TherapistRegisterController extends Controller
{
    /** Jenis dokumen yang bisa diunggah saat pendaftaran → kolom `type` di therapist_documents. */
    private const DOCUMENTS = ['ktp', 'rekening', 'sertifikat_pelatihan', 'sertifikat_pengalaman', 'stpt', 'foto_tempat'];

    public function show()
    {
        $services = Service::where('is_active', true)->orderBy('category')->orderBy('name')->get()->groupBy('category');

        return view('auth.daftar-terapis', [
            'services' => $services,
            'categoryLabels' => Category::daftar(),
        ]);
    }

    public function register(Request $request, Otp $otp)
    {
        $data = $request->validate([
            // Akun
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'legal_accepted' => ['accepted'],
            // Profil
            'gender' => ['required', 'in:pria,wanita'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:70'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            // Model layanan
            'serves_call' => ['nullable', 'boolean'],
            'serves_place' => ['nullable', 'boolean'],
            'transport_fee' => ['nullable', 'integer', 'min:0'],
            'place_address' => ['nullable', 'required_if:serves_place,1', 'string', 'max:255'],
            // Layanan terpilih + harga
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['integer', 'exists:services,id'],
            'price' => ['array'],
            'price.*' => ['nullable', 'integer', 'min:0'],
            'duration' => ['array'],
            'duration.*' => ['nullable', 'integer', 'min:15', 'max:480'],
            // Dokumen
            'ktp' => ['required', 'image', 'max:4096'],
            'avatar' => ['required', 'image', 'max:4096'],
            'rekening' => ['nullable', 'image', 'max:4096'],
            'sertifikat_pelatihan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'sertifikat_pengalaman' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'stpt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'foto_tempat' => ['nullable', 'image', 'max:4096'],
        ], [
            'services.required' => 'Pilih minimal satu jenis layanan.',
            'place_address.required_if' => 'Alamat tempat praktik wajib diisi bila melayani di tempat.',
        ]);

        if (! $request->boolean('serves_call') && ! $request->boolean('serves_place')) {
            return back()->withInput()->withErrors(['serves_call' => 'Pilih minimal satu model layanan: panggilan atau di tempat praktik.']);
        }

        $eligibleServiceIds = Service::availableTo($data['gender'])->whereIn('id', $data['services'])->pluck('id');
        if ($eligibleServiceIds->count() !== count(array_unique($data['services']))) {
            return back()->withInput()->withErrors(['services' => 'Pilihan layanan tidak tersedia untuk profilmu.']);
        }

        $profile = DB::transaction(function () use ($request, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => 'therapist',
                // Disalin ke akun supaya terapis tak ditanyai lagi saat ia sendiri memesan.
                'gender' => $data['gender'],
                'avatar_path' => $request->file('avatar')->store("therapist/{$data['email']}", 'public'),
                'legal_version' => config('legal.version'),
                'legal_accepted_at' => now(),
            ]);

            /** @var TherapistProfile $profile */
            $profile = $user->therapistProfile()->create([
                'gender' => $data['gender'],
                'experience_years' => $data['experience_years'],
                'bio' => $data['bio'] ?? null,
                'province' => $data['province'],
                'city' => $data['city'],
                'district' => $data['district'] ?? null,
                'serves_call' => $request->boolean('serves_call'),
                'serves_place' => $request->boolean('serves_place'),
                'transport_fee' => $request->boolean('serves_call') ? ($data['transport_fee'] ?? 0) : 0,
                'place_address' => $request->boolean('serves_place') ? ($data['place_address'] ?? null) : null,
                'verification_status' => 'anggota',
            ]);

            // Layanan + harga/durasi (pivot therapist_service)
            $attach = [];
            foreach ($data['services'] as $serviceId) {
                $attach[$serviceId] = [
                    'price' => $data['price'][$serviceId] ?? 0,
                    'duration_min' => $data['duration'][$serviceId] ?? 60,
                ];
            }
            $profile->services()->attach($attach);

            // Dokumen — hanya yang diunggah
            foreach (self::DOCUMENTS as $type) {
                if ($request->hasFile($type)) {
                    $profile->documents()->create([
                        'type' => $type,
                        'path' => $request->file($type)->store("therapist/{$data['email']}/dokumen"),
                    ]);
                }
            }

            return $profile;
        });

        Auth::login($profile->user);
        $request->session()->regenerate();

        $otp->sendQuietly($profile->user, 'daftar');

        return redirect()->route('phone.verify')
            ->with('success', 'Pendaftaran terkirim! Verifikasi nomor WhatsApp-mu dulu, ya.');
    }
}
