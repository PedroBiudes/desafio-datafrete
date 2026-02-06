<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCsvImport;
use App\Models\Import;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index()
    {
        return Import::orderByDesc('id')->paginate(10);
    }

    public function show($id)
    {
        return Import::findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $file = $request->file('file');
        $name = uniqid('import_', true) . '.csv';
        $path = $file->storeAs('imports', $name);

        $import = Import::create([
            'filename' => $path,
            'status' => 'queued',
            'total_rows' => 0,
            'processed_rows' => 0,
            'failed_rows' => 0,
        ]);

        dispatch(new ProcessCsvImport($import->id));

        return response()->json($import, 201);
    }
}
