<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['زمان', 'نوع', 'مدیر', 'کد بلیط', 'نام', 'شرح']);

            ActivityLog::query()->with(['actor', 'registration'])->latest()->lazyById()->each(function (ActivityLog $log) use ($output): void {
                fputcsv($output, [$log->created_at->toIso8601String(), $log->type->label(), $log->actor?->name ?? 'سامانه', $log->registration->ticket_code, $log->registration->full_name, $log->body]);
            });
        }, 'activity-log.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
