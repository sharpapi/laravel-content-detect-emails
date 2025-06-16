![SharpAPI GitHub cover](https://sharpapi.com/sharpapi-github-laravel-bg.jpg "SharpAPI Laravel Client")

# AI Email Detection for Laravel

## 🚀 Leverage AI API to detect and extract email addresses from text content.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sharpapi/laravel-content-detect-emails.svg?style=flat-square)](https://packagist.org/packages/sharpapi/laravel-content-detect-emails)
[![Total Downloads](https://img.shields.io/packagist/dt/sharpapi/laravel-content-detect-emails.svg?style=flat-square)](https://packagist.org/packages/sharpapi/laravel-content-detect-emails)

Check the details at SharpAPI's [Content API](https://sharpapi.com/en/catalog/ai/content) page.

---

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

---

## Installation

Follow these steps to install and set up the SharpAPI Laravel Email Detection package.

1. Install the package via `composer`:

```bash
composer require sharpapi/laravel-content-detect-emails
```

2. Register at [SharpAPI.com](https://sharpapi.com/) to obtain your API key.

3. Set the API key in your `.env` file:

```bash
SHARP_API_KEY=your_api_key_here
```

4. **[OPTIONAL]** Publish the configuration file:

```bash
php artisan vendor:publish --tag=sharpapi-content-detect-emails
```

---
## Key Features

- **AI-Powered Email Detection**: Efficiently detect email addresses in any text content.
- **Multiple Email Detection**: Identifies all email addresses present in the provided text.
- **Format Validation**: Ensures detected emails are properly formatted.
- **Obfuscated Email Detection**: Can detect emails that are partially obfuscated or formatted in non-standard ways.
- **Robust Polling for Results**: Polling-based API response handling with customizable intervals.
- **API Availability and Quota Check**: Check API availability and current usage quotas with SharpAPI's endpoints.

---

## Usage

You can inject the `ContentDetectEmailsService` class to access email detection functionality. For best results, especially with batch processing, use Laravel's queuing system to optimize job dispatch and result polling.

### Basic Workflow

1. **Dispatch Job**: Send text content to the API using `detectEmails`, which returns a status URL.
2. **Poll for Results**: Use `fetchResults($statusUrl)` to poll until the job completes or fails.
3. **Process Result**: After completion, retrieve the results from the `SharpApiJob` object returned.

> **Note**: Each job typically takes a few seconds to complete. Once completed successfully, the status will update to `success`, and you can process the results as JSON, array, or object format.

---

### Controller Example

Here is an example of how to use `ContentDetectEmailsService` within a Laravel controller:

```php
<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use SharpAPI\ContentDetectEmails\ContentDetectEmailsService;

class ContentController extends Controller
{
    protected ContentDetectEmailsService $emailDetectionService;

    public function __construct(ContentDetectEmailsService $emailDetectionService)
    {
        $this->emailDetectionService = $emailDetectionService;
    }

    /**
     * @throws GuzzleException
     */
    public function detectEmailAddresses(string $text)
    {
        $statusUrl = $this->emailDetectionService->detectEmails($text);
        
        $result = $this->emailDetectionService->fetchResults($statusUrl);

        return response()->json($result->getResultJson());
    }
}
```

### Handling Guzzle Exceptions

All requests are managed by Guzzle, so it's helpful to be familiar with [Guzzle Exceptions](https://docs.guzzlephp.org/en/stable/quickstart.html#exceptions).

Example:

```php
use GuzzleHttp\Exception\ClientException;

try {
    $statusUrl = $this->emailDetectionService->detectEmails('Contact us at support@example.com or sales@example.com');
} catch (ClientException $e) {
    echo $e->getMessage();
}
```

---

## Optional Configuration

You can customize the configuration by setting the following environment variables in your `.env` file:

```bash
SHARP_API_KEY=your_api_key_here
SHARP_API_JOB_STATUS_POLLING_WAIT=180
SHARP_API_JOB_STATUS_USE_POLLING_INTERVAL=true
SHARP_API_JOB_STATUS_POLLING_INTERVAL=10
SHARP_API_BASE_URL=https://sharpapi.com/api/v1
```

---

## Email Detection Data Format Example

```json
{
  "data": {
    "type": "api_job_result",
    "id": "5a113c4d-38e9-43e5-80f4-ec3fdea3420e",
    "attributes": {
      "status": "success",
      "type": "content_detect_emails",
      "result": [
        {
          "email": "support@example.com"
        },
        {
          "email": "sales@example.com"
        }
      ]
    }
  }
}
```

---

## Support & Feedback

For issues or suggestions, please:

- [Open an issue on GitHub](https://github.com/sharpapi/laravel-content-detect-emails/issues)
- Join our [Telegram community](https://t.me/sharpapi_community)

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for a detailed list of changes.

---

## Credits

- [A2Z WEB LTD](https://github.com/a2zwebltd)
- [Dawid Makowski](https://github.com/makowskid)
- Enhance your [Laravel AI](https://sharpapi.com/) capabilities!

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## Follow Us

Stay updated with news, tutorials, and case studies:

- [SharpAPI on X (Twitter)](https://x.com/SharpAPI)
- [SharpAPI on YouTube](https://www.youtube.com/@SharpAPI)
- [SharpAPI on Vimeo](https://vimeo.com/SharpAPI)
- [SharpAPI on LinkedIn](https://www.linkedin.com/products/a2z-web-ltd-sharpapicom-automate-with-aipowered-api/)
- [SharpAPI on Facebook](https://www.facebook.com/profile.php?id=61554115896974)