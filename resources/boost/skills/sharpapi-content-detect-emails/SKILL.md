---
name: sharpapi-content-detect-emails
description: Detect and extract email addresses from free text with SharpAPI via `SharpAPI\ContentDetectEmails\ContentDetectEmailsService` (sharpapi/laravel-content-detect-emails). Use when finding, extracting or masking emails in user content, moderating contact details, or touching `detectEmails()`, `fetchResults()` or `config/sharpapi-content-detect-emails.php`.
---

# SharpAPI Content Detect Emails

`sharpapi/laravel-content-detect-emails` wraps one SharpAPI endpoint (`POST /content/detect_emails`) to extract email addresses from free text. The work is async: `detectEmails()` submits a job and returns a status URL, then `fetchResults()` polls until the job finishes.

## When to use this skill

- Pulling email addresses out of messages, listings, CVs or other free text, including obfuscated ones a regex misses.
- Moderating user content that must not contain contact details.
- Debugging empty or odd results from `detectEmails()` / `fetchResults()`.

## Install / wiring checklist

- `composer require sharpapi/laravel-content-detect-emails`. It pulls in `sharpapi/php-core`; this skill assumes php-core ≥ 1.4.1.
- `.env`: `SHARP_API_KEY=...` is required. If it is missing, constructing the service throws `InvalidArgumentException`.
- Optional env keys, shared by every SharpAPI wrapper:
  - `SHARP_API_BASE_URL` (default `https://sharpapi.com/api/v1`)
  - `SHARP_API_JOB_STATUS_POLLING_WAIT` (default `180`): the maximum seconds `fetchResults()` keeps polling.
  - `SHARP_API_JOB_STATUS_POLLING_INTERVAL` (default `10`): seconds between polls when the API sends no `Retry-After`.
  - `SHARP_API_JOB_STATUS_USE_POLLING_INTERVAL` (default `false`): when `true`, the fixed interval above replaces the server's `Retry-After`.
- The config file is optional. To publish it: `php artisan vendor:publish --tag=sharpapi-content-detect-emails` (creates `config/sharpapi-content-detect-emails.php`).
- The service provider is auto-discovered. There is **no facade and no container binding**. Type-hint `ContentDetectEmailsService` (the container builds it) or call `new ContentDetectEmailsService()`. The constructor takes no arguments and reads the config.

## API & config reference

```php
use SharpAPI\ContentDetectEmails\ContentDetectEmailsService;

public function detectEmails(string $text): string
```

- `$text` — the content to scan. The only parameter.

**Returns the status URL (a string), not the result.** Pass it to the inherited `fetchResults(string $statusUrl): SharpAPI\Core\DTO\SharpApiJob`, which blocks while it polls.

`SharpApiJob` has the public properties `id`, `type` (`"content_detect_emails"`), `status` (a string: `"success"` or `"failed"`) and `result` (`?stdClass`). It also has `getResultJson()`, `getResultArray()` (shallow), `getResultObject()` and `toArray()`.

Example `result` on success (shape from the SharpAPI response template; the values are illustrative):

```json
[
    "example@email.com",
    "lorem.ipsum@email.com"
]
```

A list of strings, or an empty list when nothing is found. php-core hands it over as a `stdClass` with numeric keys (`$job->result->{0}`), so decode it as shown below.

Exceptions:
- `SharpAPI\Core\Exceptions\ApiException`: polling ran past `SHARP_API_JOB_STATUS_POLLING_WAIT`, or HTTP 429 retries ran out.
- `GuzzleHttp\Exception\ClientException` (4xx, e.g. 401 bad key, 422 validation) and other `GuzzleHttp\Exception\GuzzleException`s for transport or 5xx errors.

## Recipes

### Queued job (the default pattern)

```php
namespace App\Jobs;

use App\Models\Message;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable; // Laravel 10: Dispatchable, InteractsWithQueue, Queueable, SerializesModels
use Illuminate\Support\Facades\Log;
use SharpAPI\Core\Enums\SharpApiJobStatusEnum;
use SharpAPI\Core\Exceptions\ApiException;
use SharpAPI\ContentDetectEmails\ContentDetectEmailsService;

class ScanMessageForEmails implements ShouldQueue
{
    use Queueable;

    public int $timeout = 240; // must exceed SHARP_API_JOB_STATUS_POLLING_WAIT (180)

    public int $tries = 1;     // every retry re-submits the text and is billed again

    public function __construct(public Message $message) {}

    public function handle(ContentDetectEmailsService $service): void
    {
        try {
            $statusUrl = $service->detectEmails($this->message->body);
            $job = $service->fetchResults($statusUrl); // blocks and polls; no loop needed
        } catch (ApiException|GuzzleException $e) {
            Log::warning('SharpAPI detectEmails failed: '.$e->getMessage());

            return;
        }

        if ($job->status !== SharpApiJobStatusEnum::SUCCESS->value) {
            Log::warning('SharpAPI detectEmails job did not succeed', $job->toArray());

            return;
        }

        $emails = array_values(json_decode($job->getResultJson(), true) ?? []);
        $this->message->update(['contains_email' => $emails !== []]);
    }
}
```

Resolve the service in `handle()`, as above, and never store it on a job property. It holds a Guzzle client, which cannot be serialized onto the queue.

## Gotchas

php-core is a transitive dependency, so these rules are repeated here:

1. **`fetchResults()` already polls.** It sleeps between polls (honouring `Retry-After` and rate-limit headers) until the job succeeds, fails or `SHARP_API_JOB_STATUS_POLLING_WAIT` runs out. Never write your own `while ($status === 'pending')` loop, and never call `detectEmails()` again to "retry": each call is a new billed job.
2. **A failed job does not throw.** Always compare `$job->status` with `SharpApiJobStatusEnum::SUCCESS->value` (`SharpAPI\Core\Enums\SharpApiJobStatusEnum`). On failure `result` can be an empty `stdClass`, so reading `$job->result->field` without `?? null` raises an "Undefined property" `ErrorException` in Laravel.
3. **Never call `fetchResults()` inside an HTTP request.** It can block for up to 180 s. Use a queued job whose `$timeout` exceeds the polling wait, keep `$tries` low, and make the worker/Horizon supervisor `timeout` at least the job timeout, with the queue connection's `retry_after` above it. Artisan commands are fine to run inline.
4. **For arrays, decode the JSON:** `json_decode($job->getResultJson(), true)`. `getResultArray()` only converts the top level, so nested objects stay `stdClass`, and list results arrive as objects with numeric keys. This package returns a list, so always decode it this way.

## Testing

- Mock the service. It must reach your code through the container (constructor/`handle()` injection or `app(ContentDetectEmailsService::class)`); `new ContentDetectEmailsService()` bypasses the mock.

```php
use SharpAPI\Core\DTO\SharpApiJob;
use SharpAPI\ContentDetectEmails\ContentDetectEmailsService;

$this->mock(ContentDetectEmailsService::class, function ($mock) {
    $mock->shouldReceive('detectEmails')->once()->andReturn('https://sharpapi.com/api/v1/job/status/fake-id');
    $mock->shouldReceive('fetchResults')->once()->andReturn(new SharpApiJob(
        id: 'fake-id',
        type: 'content_detect_emails',
        status: 'success',
        result: (object) ['john@example.com'],
    ));
});
```

- Test the failure path too: return `status: 'failed'` with `result: new \stdClass`.
- `Http::fake()` does **not** intercept these calls, because php-core sends them through its own Guzzle client. Mock the service instead. Without a mock, a test with no `SHARP_API_KEY` throws `InvalidArgumentException` as soon as the service is built.
