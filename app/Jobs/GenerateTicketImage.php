<?php

namespace App\Jobs;

use App\Models\Registration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class GenerateTicketImage implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $registrationId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $registration = Registration::query()->findOrFail($this->registrationId);
        $html = view('tickets.show', compact('registration'))->render();
        $path = "tickets/{$registration->ticket_code}.png";
        Storage::disk('local')->makeDirectory('tickets');
        $temporaryPath = Storage::disk('local')->path($path);

        $browsershot = Browsershot::html($html)->windowSize(900, 1200);
        $browsershot->save($temporaryPath);
        $browsershot->savePdf(Storage::disk('local')->path("tickets/{$registration->ticket_code}.pdf"));

        $registration->update(['ticket_path' => $path]);
    }
}
