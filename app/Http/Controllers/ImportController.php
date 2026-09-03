<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Http\Requests\StoreImportRequest;
use App\Models\Import;
use App\Models\Supplier;
use App\Jobs\ProcessImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function store(StoreImportRequest $request): JsonResponse
    {
        $import = DB::transaction(function () use ($request) {
            $supplier = Supplier::query()
                ->where('code', $request->string('supplier'))
                ->firstOrFail();

            $import = Import::query()
                ->where('supplier_id', $supplier->id)
                ->where('external_import_id', $request->string('external_import_id'))
                ->first();

            if ($import) {
                return $import;
            }

            return Import::create([
                'supplier_id' => $supplier->id,
                'external_import_id' => $request->string('external_import_id'),
                'sent_at' => $request->date('sent_at'),
                'status' => ImportStatus::Pending,
                'total_offers' => count($request->input('offers')),
            ]);
        });

        if ($import->wasRecentlyCreated) {
            ProcessImport::dispatch(
                $import,
                $request->validated('offers'),
            )->afterCommit();
        }

        return response()->json([
            'data' => [
                'id' => $import->id,
                'status' => $import->status->value,
            ],
        ], 202);
    }
}
