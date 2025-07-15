<?php

declare(strict_types=1);

namespace Laminas\ReCaptcha;

/**
 * Model responses from the ReCaptcha and MailHide APIs.
 *
 * @final This class should not be extended and will be marked final in version 4.0
 */
class Response
{
    private bool $isValid;

    private array $errorCodes;

    public function __construct(bool $isValid, ?array $errorCodes = null)
    {
        $this->isValid = $isValid;
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
