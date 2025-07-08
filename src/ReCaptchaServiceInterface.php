<?php

declare(strict_types=1);

namespace Laminas\ReCaptcha;

/**
 * An interface for interacting with a recaptcha service provider
 */
interface ReCaptchaServiceInterface
{
    /**
     * Verify the user input
     *
     * @param string $responseField
     * @return Response
     */
    public function verify(string $responseField): Response;
}
