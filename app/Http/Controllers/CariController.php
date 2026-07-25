<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\TherapistProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CariController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->q);
        $kategori = $request->kategori;
        $kota = $request->kota;
        $model = $request->model;
        $sort = $request->sort ?: 'rekomendasi';

        $query = TherapistProfile::query()
            ->with(['user', 'services'])
            ->where('is_available', true)
            ->whereHas('user', fn ($u) => $u->whereNull('blocked_at'))
            // harga mulai (min dari pivot) untuk tampilan & urutan termurah
            ->addSelect(['starting_price' => DB::table('therapist_service')
                ->selectRaw('MIN(price)')
                ->whereColumn('therapist_profile_id', 'therapist_profiles.id')])
            ->when($kota, fn ($qq) => $qq->where('city', $kota))
            ->when($model === 'panggilan', fn ($qq) => $qq->where('serves_call', true))
            ->when($model === 'tempat', fn ($qq) => $qq->where('serves_place', true))
            ->when($kategori, fn ($qq) => $qq->whereHas('services', fn ($s) => $s->where('category', $kategori)))
            ->when($q !== '', fn ($qq) => $qq->where(fn ($w) => $w
                ->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))
                ->orWhereHas('services', fn ($s) => $s->where('name', 'like', "%{$q}%"))));

        $query = match ($sort) {
            'termurah' => $query->orderBy('starting_price'),
            'rating' => $query->orderByDesc('rating_avg'),
            'terlaris' => $query->orderByDesc('completed_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('rating_avg'),
        };

        $therapists = $query->paginate(9)->withQueryString();

        // Kategori untuk chip filter.
        $categories = [
            'pijat' => 'Pijat', 'bekam' => 'Bekam', 'kretek' => 'Kretek',
            'refleksi' => 'Refleksi', 'lainnya' => 'Kerik & Totok',
        ];
        $cities = TherapistProfile::query()->distinct()->orderBy('city')->pluck('city')->filter()->values();

        return view('cari', compact('therapists', 'q', 'kategori', 'kota', 'model', 'sort', 'categories', 'cities'));
    }

    public function show(TherapistProfile $therapist)
    {
        abort_if(! $therapist->is_available, 404);
        $therapist->load(['user', 'services', 'schedules', 'reviews.user']);

        return view('terapis', compact('therapist'));
    }
}
