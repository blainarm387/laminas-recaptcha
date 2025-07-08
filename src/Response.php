<?php

declare(strict_types=1);

namespace Laminas\ReCaptcha;

use Psr\Http\Message\ResponseInterface;

use function array_key_exists;
use function is_array;
use function json_decode;
use function trim;

use const JSON_THROW_ON_ERROR;

/**
 * Model responses from the ReCaptcha and MailHide APIs.
 *
 * @final This class should not be extended and will be marked final in version 4.0
 */
class Response
{
    private bool $isValid;

    private array $errorCodes;

    public function __construct(ResponseInterface $response)
    {
        $body = (string)$response->getBody();
        $parts = trim($body) === '' ? [] : json_decode($body, true, flags: JSON_THROW_ON_ERROR);

        $this->isValid = false;
        $errorCodes = [];

        if (is_array($parts) && array_key_exists('success', $parts)) {
            $this->isValid = $parts['success'];
            if (array_key_exists('error-codes', $parts)) {
                $errorCodes = $parts['error-codes'];
            }
        }

        $this->errorCodes = $errorCodes;
    }

    /**
     * Alias for getStatus()
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Get the error codes
     *
     * @return array
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }
}
