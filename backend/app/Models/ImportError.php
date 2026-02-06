<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    protected $fillable = [
        'import_id',
        'row_number',
        'cep_origin',
        'cep_destination',
        'error_message',
    ];
}
