<?php

namespace App\Http\Controllers;

use App\Models\TherapistProfile;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CariController extends Controller
{
    public function index(Request $request)
    {
        $categories = ['pijat' => 'Pijat', 'bekam' => 'Bekam', 'kretek' => 'Kretek', 'lainnya' => 'Kerik & Totok'];
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', Rule::in(array_keys($categories))],
            'kota' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', Rule::in(['panggilan', 'tempat'])],
            'sort' => ['nullable', Rule::in(['rekomendasi', 'rating', 'termurah', 'terlaris', 'terdekat'])],
            'gender' => ['nullable', Rule::in(['pria', 'wanita'])],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            'radius' => ['nullable', 'integer', 'between:1,100', 'required_with:lat,lng'],
        ]);
        $q = trim($data['q'] ?? '');
        $kategori = $data['kategori'] ?? null;
        $kota = $data['kota'] ?? null;
        $model = $data['model'] ?? null;
        $gender = $data['gender'] ?? null;
        $sort = $data['sort'] ?? 'rekomendasi';
        $latitude = isset($data['lat']) ? (float) $data['lat'] : null;
        $longitude = isset($data['lng']) ? (float) $data['lng'] : null;
        $radius = isset($data['radius']) ? (int) $data['radius'] : 25;

        $query = TherapistProfile::query()
            ->eligible()
            ->with(['user', 'services' => fn ($query) => $query->where('is_active', true)])
            ->where('is_available', true)
            ->whereHas('user', fn ($query) => $query->whereNull('blocked_at'))
            ->addSelect(['starting_price' => DB::table('therapist_service')->selectRaw('MIN(price)')->whereColumn('therapist_profile_id', 'therapist_profiles.id')])
            ->when($kota && $latitude === null, fn ($query) => $query->where('city', $kota))
            ->when($gender, fn ($query) => $query->where('gender', $gender))
            ->when($model === 'panggilan', fn ($query) => $query->where('serves_call', true))
            ->when($model === 'tempat', fn ($query) => $query->where('serves_place', true))
            ->when($kategori, fn ($query) => $query->whereHas('services', fn ($service) => $service->where('category', $kategori)->where('is_active', true)))
            ->when($q !== '', fn ($query) => $query->where(fn ($where) => $where
                ->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%"))
                ->orWhereHas('services', fn ($service) => $service->where('name', 'like', "%{$q}%")->where('is_active', true))));

        if ($latitude !== null && $longitude !== null) {
            $latitudeDelta = $radius / 111.32;
            $longitudeDelta = $radius / max(111.32 * cos(deg2rad($latitude)), 0.01);
            $nearby = $query->whereBetween('service_lat', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
                ->whereBetween('service_lng', [$longitude - $longitudeDelta, $longitude + $longitudeDelta])
                ->get()
                ->each(fn ($therapist) => $therapist->distance_km = $this->distance($latitude, $longitude, $therapist->service_lat, $therapist->service_lng))
                ->filter(fn ($therapist) => $therapist->distance_km <= $radius);
            $nearby = $sort === 'terdekat' || $sort === 'rekomendasi' ? $nearby->sortBy('distance_km') : $this->sort($nearby, $sort);
            $page = LengthAwarePaginator::resolveCurrentPage();
            $therapists = new LengthAwarePaginator($nearby->forPage($page, 9)->values(), $nearby->count(), 9, $page, ['path' => $request->url(), 'query' => $request->query()]);
        } else {
            $query = match ($sort) {
                'termurah' => $query->orderBy('starting_price'),
                'rating' => $query->orderByDesc('rating_avg'),
                'terlaris' => $query->orderByDesc('completed_count'),
                default => $query->orderByDesc('is_featured')->orderByDesc('rating_avg'),
            };
            $therapists = $query->paginate(9)->withQueryString();
        }

        $cities = TherapistProfile::query()->distinct()->orderBy('city')->pluck('city')->filter()->values();

        return view('cari', compact('therapists', 'q', 'kategori', 'kota', 'model', 'gender', 'sort', 'categories', 'cities', 'latitude', 'longitude', 'radius'));
    }

    public function show(TherapistProfile $therapist)
    {
        abort_if(! $therapist->is_available || ! $therapist->isEligible(), 404);
        $therapist->load(['user', 'services' => fn ($query) => $query->where('is_active', true), 'schedules', 'reviews.user']);

        return view('terapis', compact('therapist'));
    }

    private function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat = deg2rad($lat2 - $lat1);
        $lng = deg2rad($lng2 - $lng1);
        $a = sin($lat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function sort($therapists, string $sort)
    {
        return match ($sort) {
            'termurah' => $therapists->sortBy('starting_price'),
            'rating' => $therapists->sortByDesc('rating_avg'),
            'terlaris' => $therapists->sortByDesc('completed_count'),
            default => $therapists->sortByDesc('is_featured')->sortByDesc('rating_avg'),
        };
    }
}
