<?php
namespace App\Helper;

use App\Core\Config;
use App\Core\Helper\Helper;

class Recaptcha extends Helper
{
    /**
     * Validate the reCAPTCHA response.
     *
     * @param string $recaptchaResponse The reCAPTCHA response token.
     * @return bool True if validation is successful, false otherwise.
     * @throws \RuntimeException If the reCAPTCHA configuration is missing or invalid.
     */
    public static function validateRecaptcha(string $recaptchaResponse): bool
    {
        //$recaptchaResponse = 'test'; //simulate wrong recaptcha response for testing purposes

        $config = Config::get('recaptcha');

        if (empty($config) || !is_array($config)) {
            throw new \RuntimeException('Recaptcha configuration is missing or invalid');
        }

        $secretKey = $config['secret_key'] ?? null;

        if (empty($secretKey)) {
            throw new \RuntimeException('Recaptcha secret key is not set in the configuration');
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';

        $data = [
            'secret' => $secretKey,
            'response' => $recaptchaResponse,
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            return false; // Failed to connect to recaptcha service
        }

        $response = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode recaptcha response: ' . json_last_error_msg());
        }

        if (!isset($response['success'])) {
            throw new \RuntimeException('Recaptcha response does not contain success field');
        }

        // if (!isset($response['score'])) {
        //     throw new \RuntimeException('Recaptcha response does not contain score field');
        // }

        if (!isset($response['hostname']) || $response['hostname'] !== $_SERVER['HTTP_HOST']) {
            throw new \RuntimeException('Recaptcha response hostname does not match current host');
        }

        if (!$response['success'] && $response['success'] !== true) {
            throw new \RuntimeException('Recaptcha validation failed: ' . ($response['error-codes'][0] ?? 'Unknown error'));
        }

        return isset($response['success']) && $response['success'] === true;
    }
}