<?php

namespace App\Jobs;

use App\Enums\ActivityType;
use App\Enums\SmsStatus;
use App\Models\ActivityLog;
use App\Models\Registration;
use App\Models\SmsMessage;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class SendSmsMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $smsMessageId, public string $actorType, public int $actorId) {}

    public function handle(): void
    {
        $message = SmsMessage::query()->with(['registration', 'smsTemplate'])->findOrFail($this->smsMessageId);
        $template = $message->smsTemplate?->name ?? 'پیامک SHSMS';

        try {
            if (config('shsms.sandbox')) {
                Log::info('SMS sandbox', ['to' => $message->to, 'body' => $message->body]);
            } else {
                $token = (string) config('shsms.api_token');
                $template = (string) DB::table('settings')->where('key', 'shsms_template')->value('value');
                $template = $template !== '' ? $template : ($message->smsTemplate?->name ?? '');

                if ($token === '' || $template === '') {
                    throw new LogicException('تنظیمات SHSMS یا نام الگوی پیامک کامل نشده است.');
                }

                $response = Http::withToken((string) config('shsms.api_token'))
                    ->connectTimeout(3)
                    ->timeout(10)
                    ->get((string) config('shsms.endpoint'), [
                        'receptor' => $message->to,
                        'template' => $template,
                        'param' => $this->parameters($message->registration),
                    ]);

                $response->throw();
                $message->provider_message_id = (string) data_get($response->json(), 'id', '');
            }

            $message->forceFill(['status' => SmsStatus::Sent, 'sent_at' => now()])->save();
            ActivityLog::query()->create([
                'registration_id' => $message->registration_id,
                'actor_type' => $this->actorType,
                'actor_id' => $this->actorId,
                'type' => ActivityType::Sms,
                'body' => 'ارسال «'.$template.'» به '.$message->to."\n".$message->body,
            ]);
        } catch (Throwable $exception) {
            $message->forceFill(['status' => SmsStatus::Failed, 'error' => $exception->getMessage()])->save();

            throw $exception;
        }
    }

    /** @return array<int, string> */
    private function parameters(Registration $registration): array
    {
        $eventStartsAt = CarbonImmutable::createFromTimestamp(
            (int) (DB::table('settings')->where('key', 'event_starts_at')->value('value') ?: now()->getTimestamp()),
            config('app.timezone'),
        );
        $landing = json_decode((string) DB::table('settings')->where('key', 'landing_content')->value('value'), true);
        $title = is_array($landing) ? data_get($landing, 'hero.eyebrow') : null;

        return [
            $registration->full_name,
            $eventStartsAt->locale('fa')->isoFormat('D MMMM YYYY'),
            $eventStartsAt->format('H:i'),
            $title ?: 'گردهمایی آموزشی',
        ];
    }
}
