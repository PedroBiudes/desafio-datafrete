<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class RoadDistanceService
{
    public function distanceKm(float $originLat, float $originLng, float $destLat, float $destLng): float
    {
        $key = $this->cacheKey($originLat, $originLng, $destLat, $destLng);

        if ($cached = Redis::get($key)) {
            return (float) $cached;
        }

        $oLng = number_format($originLng, 6, '.', '');
        $oLat = number_format($originLat, 6, '.', '');
        $dLng = number_format($destLng, 6, '.', '');
        $dLat = number_format($destLat, 6, '.', '');

        $url = "https://router.project-osrm.org/route/v1/driving/{$oLng},{$oLat};{$dLng},{$dLat}";

        $resp = Http::timeout(15)->get($url, [
            'overview' => 'false',
        ]);

        if (!$resp->successful()) {
            throw new \RuntimeException('Falha ao calcular rota por estrada (OSRM)');
        }

        $data = $resp->json();
        $meters = data_get($data, 'routes.0.distance');

        if ($meters === null) {
            throw new \RuntimeException('Resposta inválida do OSRM');
        }

        $km = round(((float) $meters) / 1000, 3);

        Redis::setex($key, 60 * 60 * 24 * 7, (string) $km);

        return $km;
    }

    private function cacheKey(float $aLat, float $aLng, float $bLat, float $bLng): string
    {
        $aLat = number_format($aLat, 6, '.', '');
        $aLng = number_format($aLng, 6, '.', '');
        $bLat = number_format($bLat, 6, '.', '');
        $bLng = number_format($bLng, 6, '.', '');

        return "route:osrm:{$aLat},{$aLng}:{$bLat},{$bLng}";
    }
}
