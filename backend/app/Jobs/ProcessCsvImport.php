<?php

namespace App\Jobs;

use App\Models\Distance;
use App\Models\Import;
use App\Models\ImportError;
use App\Services\CepService;
use App\Services\DistanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;
    public $tries = 3;

    private int $importId;

    public function __construct(int $importId)
    {
        $this->importId = $importId;
    }

    public function handle(CepService $cepService, DistanceService $distanceService)
    {
        $import = Import::findOrFail($this->importId);

        $import->update(['status' => 'processing']);

        $fullPath = Storage::path($import->filename);

        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            $import->update(['status' => 'failed']);
            return;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $import->update(['status' => 'failed']);
            return;
        }

        $map = array_flip(array_map('trim', $header));
        $hasOrigin = array_key_exists('cep_origem', $map);
        $hasDest = array_key_exists('cep_destino', $map);

        if (!$hasOrigin || !$hasDest) {
            fclose($handle);
            $import->update(['status' => 'failed']);
            return;
        }

        $rowNumber = 1;
        $total = 0;
        $processed = 0;
        $failed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $total++;

            $cepOrigin = (string)($row[$map['cep_origem']] ?? '');
            $cepDest = (string)($row[$map['cep_destino']] ?? '');

            try {
                $origin = $cepService->getCoordinates($cepOrigin);
                $dest = $cepService->getCoordinates($cepDest);

                $km = $distanceService->haversineKm($origin['lat'], $origin['lng'], $dest['lat'], $dest['lng']);

                Distance::create([
                    'cep_origin' => $origin['cep'],
                    'cep_destination' => $dest['cep'],
                    'origin_lat' => $origin['lat'],
                    'origin_lng' => $origin['lng'],
                    'destination_lat' => $dest['lat'],
                    'destination_lng' => $dest['lng'],
                    'distance_km' => round($km, 3),
                ]);

                $processed++;
            } catch (\Throwable $e) {
                $failed++;

                ImportError::create([
                    'import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'cep_origin' => preg_replace('/\D+/', '', $cepOrigin),
                    'cep_destination' => preg_replace('/\D+/', '', $cepDest),
                    'error_message' => $e->getMessage(),
                ]);
            }

            if (($total % 25) === 0) {
                $import->update([
                    'total_rows' => $total,
                    'processed_rows' => $processed,
                    'failed_rows' => $failed,
                ]);
            }
        }

        fclose($handle);

        $import->update([
            'status' => 'done',
            'total_rows' => $total,
            'processed_rows' => $processed,
            'failed_rows' => $failed,
        ]);
    }
}
