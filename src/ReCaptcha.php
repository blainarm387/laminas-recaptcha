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

    private static bool $is_rendered = false;

    public function __construct(
        private readonly string $siteKey,
        private readonly string $secretKey,
        private readonly string $ip,
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
        if (self::$is_rendered) {
            return '';
        }

        self::$is_rendered = true;

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
        if ($this->secretKey === null) {
            throw new Exception('Missing secret key');
        }

        if ($this->ip === null) {
            throw new Exception('Missing ip address');
        }


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
        return new Response($response);
    }
}
