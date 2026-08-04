<?php

namespace App\Http\Controllers;

use App\Events\TherapistLocationUpdated;
use App\Models\Order;
use App\Support\Geo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class TherapistLocationController extends Controller
{
    public function update(Request $request, Order $order): JsonResponse
    {
        abort_unless(
            $request->user()->therapistProfile?->id === $order->therapist_profile_id
            && $order->model === 'panggilan'
            && $order->status === 'therapist_en_route',
            404,
        );

        $validator = Validator::make($request->all(), [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['required', 'numeric', 'between:0,1000'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }
        $data = $validator->validated();
        $updatedAt = now();
        $location = [
            'lat' => (float) $data['lat'],
            'lng' => (float) $data['lng'],
            'accuracy' => (int) round($data['accuracy']),
            'updated_at' => $updatedAt->toIso8601String(),
        ];

        Cache::put(self::cacheKey($order), $location, now()->addMinutes(2));

        if ($order->lat !== null && $order->lng !== null) {
            TherapistLocationUpdated::dispatch(
                $order,
                (int) round(Geo::distanceMeters($location['lat'], $location['lng'], $order->lat, $order->lng)),
                $location['accuracy'],
                $location['updated_at'],
            );
        }

        return response()->json(['updated_at' => $location['updated_at']]);
    }

    public static function cacheKey(Order $order): string
    {
        return "orders.{$order->id}.therapist_location";
    }
}
