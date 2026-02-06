<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distance extends Model
{
    protected $fillable = [
        'cep_origin',
        'cep_destination',
        'origin_lat',
        'origin_lng',
        'destination_lat',
        'destination_lng',
        'distance_km',
        'road_distance_km',
    ];
}
