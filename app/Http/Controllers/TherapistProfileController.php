<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\TherapistDocument;
use App\Support\Otp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TherapistProfileController extends Controller
{
    public function verification(Request $request): View
    {
        $profile = $request->user()->therapistProfile()->with('documents')->firstOrFail();

        return view('mitra.verifikasi', compact('profile'));
    }

    public function edit(Request $request): View
    {
        $profile = $request->user()->therapistProfile()->with(['services', 'schedules'])->firstOrFail();

        return view('mitra.profil-edit', [
            'profile' => $profile,
            'services' => Service::availableTo($profile->gender)->orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Otp $otp): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->therapistProfile()->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'avatar' => ['nullable', 'image', 'max:4096'],
            'gender' => ['required', 'in:pria,wanita'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:70'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'service_lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:service_lng'],
            'service_lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:service_lat'],
            'serves_call' => ['nullable', 'boolean'],
            'serves_place' => ['nullable', 'boolean'],
            'transport_fee' => ['nullable', 'integer', 'min:0'],
            'place_address' => ['nullable', 'required_if:serves_place,1', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:100', 'required_with:bank_account_number,bank_account_name'],
            'bank_account_number' => ['nullable', 'string', 'max:50', 'required_with:bank_name,bank_account_name'],
            'bank_account_name' => ['nullable', 'string', 'max:255', 'required_with:bank_name,bank_account_number'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['integer', 'exists:services,id'],
            'price' => ['required', 'array'],
            'price.*' => ['nullable', 'integer', 'min:0'],
            'duration' => ['required', 'array'],
            'duration.*' => ['nullable', 'integer', 'min:15', 'max:480'],
            'schedules' => ['required', 'array', 'size:7'],
            'schedules.*.day' => ['required', 'integer', 'between:0,6', 'distinct'],
            'schedules.*.active' => ['nullable', 'boolean'],
            'schedules.*.start' => ['nullable', 'required_if:schedules.*.active,1', 'date_format:H:i'],
            'schedules.*.end' => ['nullable', 'required_if:schedules.*.active,1', 'date_format:H:i'],
        ], [
            'services.required' => 'Pilih minimal satu jenis layanan.',
            'place_address.required_if' => 'Alamat tempat praktik wajib diisi bila melayani di tempat.',
        ]);

        if (! $request->boolean('serves_call') && ! $request->boolean('serves_place')) {
            return back()->withInput()->withErrors(['serves_call' => 'Pilih minimal satu model layanan.']);
        }
        // Nomor WhatsApp adalah kunci penarikan saldo, jadi penggantiannya harus disetujui dari nomor lama.
        $phoneChanged = $data['phone'] !== $user->phone;
        if ($phoneChanged && $user->phone_verified_at !== null) {
            $code = (string) $request->input('code');
            if ($code === '' || ! $otp->verify($user, 'nomor', $code)) {
                return back()->withInput()->withErrors(['code' => 'Masukkan kode verifikasi yang dikirim ke nomor WhatsApp lamamu.']);
            }
        }
        $eligibleServiceIds = Service::availableTo($data['gender'])->whereIn('id', $data['services'])->pluck('id');
        if ($eligibleServiceIds->count() !== count(array_unique($data['services']))) {
            return back()->withInput()->withErrors(['services' => 'Pilihan layanan tidak tersedia untuk profilmu.']);
        }
        foreach ($data['schedules'] as $index => $schedule) {
            if (($schedule['active'] ?? false) && $schedule['end'] <= $schedule['start']) {
                return back()->withInput()->withErrors(["schedules.{$index}.end" => 'Jam selesai harus setelah jam mulai.']);
            }
        }

        $oldAvatar = $user->avatar_path;
        $newAvatar = $request->file('avatar')?->store("therapist/{$user->id}", 'public');

        DB::transaction(function () use ($request, $user, $profile, $data, $newAvatar, $phoneChanged) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                ...($newAvatar ? ['avatar_path' => $newAvatar] : []),
            ]);
            // Nomor baru belum terbukti dimiliki; pembuktiannya lewat kode penarikan berikutnya.
            if ($phoneChanged) {
                $user->forceFill(['phone_verified_at' => null])->save();
            }
            $profile->update([
                'gender' => $data['gender'],
                'experience_years' => $data['experience_years'],
                'bio' => $data['bio'] ?? null,
                'province' => $data['province'],
                'city' => $data['city'],
                'district' => $data['district'] ?? null,
                'service_lat' => $data['service_lat'] ?? null,
                'service_lng' => $data['service_lng'] ?? null,
                'serves_call' => $request->boolean('serves_call'),
                'serves_place' => $request->boolean('serves_place'),
                'transport_fee' => $request->boolean('serves_call') ? ($data['transport_fee'] ?? 0) : 0,
                'place_address' => $request->boolean('serves_place') ? ($data['place_address'] ?? null) : null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'schedule_configured' => true,
            ]);
            $profile->schedules()->delete();
            foreach ($data['schedules'] as $schedule) {
                if ($schedule['active'] ?? false) {
                    $profile->schedules()->create([
                        'day_of_week' => $schedule['day'],
                        'start_time' => $schedule['start'],
                        'end_time' => $schedule['end'],
                    ]);
                }
            }
            $profile->services()->sync(collect($data['services'])->mapWithKeys(fn ($id) => [$id => [
                'price' => $data['price'][$id] ?? 0,
                'duration_min' => $data['duration'][$id] ?? 60,
            ]])->all());
        });

        if ($newAvatar && $oldAvatar && ! str_starts_with($oldAvatar, 'http')) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function replaceDocument(Request $request, TherapistDocument $document): RedirectResponse
    {
        abort_unless($document->therapist_profile_id === $request->user()->therapistProfile()->value('id'), 403);
        abort_unless($document->status === 'rejected', 422);

        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
        $oldPath = $document->path;
        $newPath = $data['document']->store("therapist/{$request->user()->id}/dokumen");

        try {
            DB::transaction(function () use ($document, $newPath): void {
                $document->update(['path' => $newPath, 'status' => 'pending', 'note' => null]);
                $document->therapistProfile()->update(['verification_status' => 'anggota', 'is_featured' => false, 'is_available' => false]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        Storage::disk('local')->delete($oldPath);

        return back()->with('success', 'Dokumen pengganti berhasil dikirim dan menunggu tinjauan.');
    }
}
