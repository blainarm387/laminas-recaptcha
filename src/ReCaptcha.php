<?php

declare(strict_types=1);

namespace Laminas\ReCaptcha;

use GuzzleHttp\Client;

/**
 * Render and verify v3 ReCaptchas
 */
final class ReCaptcha implements ReCaptchaServiceInterface
{
    public const API_SERVER = 'https://www.google.com/recaptcha/api';

    public const VERIFY_SERVER = 'https://www.google.com/recaptcha/api/siteverify';

    public const DEFAULT_MIN_SCORE_THRESHOLD = 0.5;

    private static bool $isRendered = false;

    private string $ip;

    private string $action;

    public function __construct(
        private readonly string $siteKey,
        private readonly string $secretKey,
        private readonly float $minScoreThreshold = self::DEFAULT_MIN_SCORE_THRESHOLD,
        private readonly Client $client = new Client()
    ) {
    }

    /**
     * Get the HTML code for the captcha
     *
     * This method uses the public key to fetch a recaptcha form.
     *
     * @return string
     */
    public function getHtml(): string
    {
        if (self::$isRendered) {
            return '';
        }

        self::$isRendered = true;

        return <<<HTML
<script>
function onReady(callbackFunc) {
    if (document.readyState === 'loading') {
        // Brwosers >= ie9
        document.addEventListener('DOMContentLoaded', callbackFunc);
    } else {
        callbackFunc();
    }
}

function addRecaptchaResponse(event) {
    event.preventDefault();
    var form = this;
    grecaptcha.ready(function () {
        grecaptcha.execute('{$this->siteKey}', {
            action: form.getAttribute('data-action')
        }).then(function (token) {
            form.querySelector('input[value="g-recaptcha-response"]').value = token;
            form.submit();
        })
    });
}

onReady(function () {
    var forms = document.getElementsByTagName('form');
    if (forms.length) {
        for (var i = 0; i < forms.length; i++) {
            var formElement = forms[i];
            if (formElement.querySelector('input[value="g-recaptcha-response"]') !== null) {
                formElement.addEventListener('submit',  addRecaptchaResponse);
            }
        }
    }
})
</script>
HTML;
    }

    public function verify(string $responseField): Response
    {
        $response = $this
            ->client
            ->post(
                self::VERIFY_SERVER,
                [
                    'form_params' => [
                        'secret' => $this->secretKey,
                        'remoteip' => $this->ip,
                        'response' => $responseField,
                    ],
                ]
            );

        $body = (string)$response->getBody();
        $parts = trim($body) === '' ? [] : json_decode($body, true, flags: JSON_THROW_ON_ERROR);

        $isValid = false;
        $errorCodes = [];

        if (is_array($parts) && array_key_exists('success', $parts)) {
            $isValid = $this->isValid($parts);
            if (array_key_exists('error-codes', $parts)) {
                $errorCodes = $parts['error-codes'];
            }
        }

        return new Response($isValid, $errorCodes);
    }

    private function isValid(array $parts)
    {
        if ($parts['success'] !== true) {
            return false;
        }

        if ($parts['action'] ?? null !== $this->action) {
            return false;
        }

        return $parts['score'] ?? 0 >= $this->minScoreThreshold;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }
}
