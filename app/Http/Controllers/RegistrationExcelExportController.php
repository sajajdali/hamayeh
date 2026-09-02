<?php

namespace App\Http\Controllers;

use App\Jobs\ExportRegistrationsExcel;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RegistrationExcelExportController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $path = 'exports/registrations-'.now()->format('Ymd-His').'-'.Str::random(8).'.xlsx';

        ExportRegistrationsExcel::dispatch($path)->onQueue('default');

        return response()->json(['path' => $path], 202);
    }
}
