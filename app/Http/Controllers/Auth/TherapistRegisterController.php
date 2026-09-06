<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Support\Otp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TherapistRegisterController extends Controller
{
    /** Jenis dokumen yang bisa diunggah saat pendaftaran → kolom `type` di therapist_documents. */
    private const DOCUMENTS = ['ktp', 'rekening', 'sertifikat_pelatihan', 'sertifikat_pengalaman', 'stpt', 'foto_tempat'];

    public function show(Request $request)
    {
        if ($redirect = $this->tolakYangTakBerhak($request->user())) {
            return $redirect;
        }

        $services = Service::where('is_active', true)->orderBy('category')->orderBy('name')->get()->groupBy('category');

        return view('auth.daftar-terapis', [
            'services' => $services,
            'categoryLabels' => Category::daftar(),
            // Null berarti pendaftar baru; berisi User berarti pelanggan yang naik jadi mitra.
            'akun' => $request->user(),
        ]);
    }

    public function register(Request $request, Otp $otp)
    {
        $akun = $request->user();

        if ($redirect = $this->tolakYangTakBerhak($akun)) {
            return $redirect;
        }

        $data = $request->validate([
            // Akun — pelanggan yang naik jadi mitra memakai akunnya sendiri, jadi cukup nomor WhatsApp.
            ...($akun ? [
                'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($akun)],
            ] : [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ]),
            'legal_accepted' => ['accepted'],
            // Profil
            // Akun yang sudah punya jenis kelamin memakai nilainya sendiri, tak ditanya lagi.
            'gender' => $akun?->gender ? ['nullable'] : ['required', 'in:pria,wanita'],
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
            'price' => ['required', 'array'],
            'price.*' => ['required', 'integer', 'min:1'],
            'duration' => ['required', 'array'],
            'duration.*' => ['required', 'integer', 'min:15', 'max:480'],
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

        // Jenis kelamin akun jadi acuan — terapis hanya melayani pelanggan sesama jenis.
        $data['gender'] = $akun?->gender ?? $data['gender'];

        if (! $request->boolean('serves_call') && ! $request->boolean('serves_place')) {
            return back()->withInput()->withErrors(['serves_call' => 'Pilih minimal satu model layanan: panggilan atau di tempat praktik.']);
        }

        $eligibleServiceIds = Service::availableTo($data['gender'])->whereIn('id', $data['services'])->pluck('id');
        if ($eligibleServiceIds->count() !== count(array_unique($data['services']))) {
            return back()->withInput()->withErrors(['services' => 'Pilihan layanan tidak tersedia untuk profilmu.']);
        }

        foreach ($data['services'] as $serviceId) {
            if (! array_key_exists($serviceId, $data['price']) || ! array_key_exists($serviceId, $data['duration'])) {
                return back()->withInput()->withErrors(['services' => 'Isi harga dan durasi untuk setiap layanan yang dipilih.']);
            }
        }

        $profile = DB::transaction(function () use ($request, $data, $akun) {
            $email = $akun?->email ?? $data['email'];
            $avatarPath = $request->file('avatar')->store("therapist/{$email}", 'public');
            $atributAkun = [
                'role' => 'therapist',
                // Disalin ke akun supaya terapis tak ditanyai lagi saat ia sendiri memesan.
                'gender' => $data['gender'],
                'avatar_path' => $avatarPath,
                'legal_version' => config('legal.version'),
                'legal_accepted_at' => now(),
            ];

            if ($akun) {
                $user = $akun->fill($atributAkun);

                // Nomor yang diganti saat mendaftar harus lolos OTP lagi.
                if ($user->phone !== $data['phone']) {
                    $user->phone = $data['phone'];
                    $user->phone_verified_at = null;
                }

                $user->save();
            } else {
                $user = User::create($atributAkun + [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => $data['password'],
                ]);
            }

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
                    'price' => $data['price'][$serviceId],
                    'duration_min' => $data['duration'][$serviceId],
                ];
            }
            $profile->services()->attach($attach);

            // Dokumen — hanya yang diunggah
            foreach (self::DOCUMENTS as $type) {
                if ($request->hasFile($type)) {
                    $profile->documents()->create([
                        'type' => $type,
                        'path' => $request->file($type)->store("therapist/{$email}/dokumen"),
                    ]);
                }
            }

            return $profile;
        });

        Auth::login($profile->user);
        $request->session()->regenerate();

        // Nomor yang sudah terbukti tak perlu diverifikasi ulang.
        if ($profile->user->phone_verified_at === null) {
            $otp->sendQuietly($profile->user, 'daftar');
        }

        return redirect()->route('phone.verify')
            ->with('success', 'Pendaftaran terkirim! Verifikasi nomor WhatsApp-mu dulu, ya.');
    }

    /** Terapis diarahkan ke panelnya; admin tak boleh menimpa perannya sendiri. */
    private function tolakYangTakBerhak(?User $user): ?RedirectResponse
    {
        if ($user?->isTherapist()) {
            return redirect()->route('mitra.dashboard')->with('success', 'Kamu sudah terdaftar sebagai terapis.');
        }

        if ($user && $user->role !== 'user') {
            return redirect()->route('home')->with('error', 'Akun admin tidak bisa didaftarkan sebagai terapis.');
        }

        return null;
    }
}
