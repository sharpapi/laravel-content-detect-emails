<?php

declare(strict_types=1);

namespace SharpAPI\ContentDetectEmails;

use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use SharpAPI\Core\Client\SharpApiClient;

/**
 * @api
 */
class ContentDetectEmailsService extends SharpApiClient
{
    /**
     * Initializes a new instance of the class.
     *
     * @throws InvalidArgumentException if the API key is empty.
     */
    public function __construct()
    {
        parent::__construct(config('sharpapi-content-detect-emails.api_key'));
        $this->setApiBaseUrl(
            config(
                'sharpapi-content-detect-emails.base_url',
                'https://sharpapi.com/api/v1'
            )
        );
        $this->setApiJobStatusPollingInterval(
            (int) config(
                'sharpapi-content-detect-emails.api_job_status_polling_interval',
                5)
        );
        $this->setApiJobStatusPollingWait(
            (int) config(
                'sharpapi-content-detect-emails.api_job_status_polling_wait',
                180)
        );
        $this->setUserAgent('SharpAPILaravelContentDetectEmails/1.0.0');
    }

    /**
     * Parses the provided text for any possible emails. Might come in handy in case of processing and validating
     * big chunks of data against email addresses or f.e. if you want to detect emails in places
     * where they're not supposed to be.
     *
     * @throws GuzzleException
     *
     * @api
     */
    public function detectEmails(string $text): string
    {
        $response = $this->makeRequest(
            'POST',
            '/content/detect_emails',
            ['content' => $text]
        );

        return $this->parseStatusUrl($response);
    }
}