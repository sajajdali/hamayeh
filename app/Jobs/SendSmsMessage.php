<?php

namespace App\Jobs;

use App\Enums\ActivityType;
use App\Enums\SmsStatus;
use App\Models\ActivityLog;
use App\Models\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSmsMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 30];

    public function __construct(public int $smsMessageId, public string $actorType, public int $actorId) {}

    public function handle(): void
    {
        $message = SmsMessage::query()->with(['registration', 'smsTemplate'])->findOrFail($this->smsMessageId);

        try {
            if (config('shsms.sandbox')) {
                Log::info('SMS sandbox', ['to' => $message->to, 'body' => $message->body]);
            } else {
                $response = Http::withToken((string) config('shsms.api_token'))
                    ->connectTimeout(3)
                    ->timeout(10)
                    ->get((string) config('shsms.endpoint'), ['receptor' => $message->to, 'message' => $message->body]);

                $response->throw();
                $message->provider_message_id = (string) data_get($response->json(), 'id', '');
            }

            $message->forceFill(['status' => SmsStatus::Sent, 'sent_at' => now()])->save();
            ActivityLog::query()->create([
                'registration_id' => $message->registration_id,
                'actor_type' => $this->actorType,
                'actor_id' => $this->actorId,
                'type' => ActivityType::Sms,
                'body' => 'ارسال «'.$message->smsTemplate->name.'» به '.$message->to."\n".$message->body,
            ]);
        } catch (Throwable $exception) {
            $message->forceFill(['status' => SmsStatus::Failed, 'error' => $exception->getMessage()])->save();

            throw $exception;
        }
    }
}
