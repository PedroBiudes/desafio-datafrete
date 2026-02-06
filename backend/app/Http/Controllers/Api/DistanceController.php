<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distance;
use App\Services\CepService;
use App\Services\DistanceService;
use Illuminate\Http\Request;

class DistanceController extends Controller
{
    public function index()
    {
        return Distance::orderByDesc('id')->paginate(10);
    }

    public function store(Request $request, CepService $cepService, DistanceService $distanceService)
    {
        $data = $request->validate([
            'cep_origin' => ['required', 'string'],
            'cep_destination' => ['required', 'string'],
            'mode' => ['nullable', 'in:haversine,road'],
        ]);

        try {

            $mode = $data['mode'] ?? 'haversine';
            $origin = $cepService->getCoordinates($data['cep_origin']);
            $dest = $cepService->getCoordinates($data['cep_destination']);

            $km = $distanceService->haversineKm($origin['lat'], $origin['lng'], $dest['lat'], $dest['lng']);
            $kmRounded = round($km, 3);

            $roadKm = null;
            if ($mode === 'road') {
                try {
                    $roadKm = app(\App\Services\RoadDistanceService::class)
                        ->distanceKm($origin['lat'], $origin['lng'], $dest['lat'], $dest['lng']);
                } catch (\Throwable $e) {
                    $roadKm = null;
                }
            }

            $distance = Distance::create([
                'cep_origin' => $origin['cep'],
                'cep_destination' => $dest['cep'],
                'origin_lat' => $origin['lat'],
                'origin_lng' => $origin['lng'],
                'destination_lat' => $dest['lat'],
                'destination_lng' => $dest['lng'],
                'distance_km' => $kmRounded,
                'road_distance_km' => $roadKm,
            ]);

            return response()->json($distance, 201);
        } catch (\Throwable $e) {
            \Log::error('Failed to create distance', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
