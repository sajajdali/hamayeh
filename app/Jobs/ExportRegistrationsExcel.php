<?php

namespace App\Jobs;

use App\Exports\RegistrationsExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;

class ExportRegistrationsExcel implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $path) {}

    public function handle(): void
    {
        Excel::store(new RegistrationsExport, $this->path, 'public');
    }
}
