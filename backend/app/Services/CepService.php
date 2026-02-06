<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class CepService
{
    public function normalize(string $cep): string
    {
        return preg_replace('/\D+/', '', $cep ?? '');
    }

    public function getCoordinates(string $cep): array
    {
        $cep = $this->normalize($cep);

        if (strlen($cep) !== 8) {
            throw new \InvalidArgumentException('CEP inválido');
        }

        $cacheKey = "cep:{$cep}";

        if ($cached = Redis::get($cacheKey)) {
            return json_decode($cached, true);
        }

        $resp = Http::timeout(8)->get("https://brasilapi.com.br/api/cep/v2/{$cep}");

        if (!$resp->successful()) {
            throw new \RuntimeException('CEP não encontrado na BrasilAPI');
        }

        $data = $resp->json();

        $lat = data_get($data, 'location.coordinates.latitude');
        $lng = data_get($data, 'location.coordinates.longitude');

        if ($lat === null || $lng === null) {
            $latLng = $this->fallbackGeocode($data);

            if (!$latLng) {
                throw new \RuntimeException('Não foi possível obter coordenadas para o CEP');
            }

            [$lat, $lng] = $latLng;
        }

        $result = [
            'cep' => $cep,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];

        Redis::setex($cacheKey, 60 * 60 * 24 * 7, json_encode($result));

        return $result;
    }

    protected function fallbackGeocode(array $data): ?array
    {
        $query = implode(', ', array_filter([
            data_get($data, 'street'),
            data_get($data, 'neighborhood'),
            data_get($data, 'city'),
            data_get($data, 'state'),
            'Brasil',
        ]));

        if (!$query) {
            return null;
        }

        $resp = Http::timeout(10)
            ->withHeaders(['User-Agent' => 'desafio-datafrete'])
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1,
            ]);

        if (!$resp->successful() || empty($resp->json())) {
            return null;
        }

        return [
            (float) $resp->json()[0]['lat'],
            (float) $resp->json()[0]['lon'],
        ];
    }
}
