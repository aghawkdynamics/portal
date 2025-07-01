<?php

namespace App\Helper;

use App\Core\Config;

class Paypal
{
    private string $accessToken = '';

    private function basicAuth()
    {
        $config = Config::get('paypal');
        
        if (empty($config['client_id']) || empty($config['secret'])) {
            throw new \RuntimeException('PayPal client ID or secret is not configured');
        }

        $clientId = $config['client_id'];
        $secret = $config['secret'];

        $credentials = base64_encode("$clientId:$secret");

        return "Authorization: Basic $credentials";
    }

    public function httpRequest($method, $url, $headers = [], $body = null)
    {
        $headers = array_merge(
            $headers,
            [
                'Content-Type: application/json',
                $this->basicAuth(),
            ]
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $code, 'body' => $resp];
    }

    protected function requestToken()
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $config = Config::get('paypal');

        $url = $config['url'] . '/v1/oauth2/token';

        $headers = [
            'Accept: application/json',
            'Accept-Language: en_US',
        ];
        $body = 'grant_type=client_credentials';

        $response = $this->httpRequest('POST', $url, $headers, $body);
        if ($response['status'] !== 200) {
            throw new \RuntimeException('Failed to obtain access token from PayPal');
        }

        $json = json_decode($response['body'], true);
        $token = $json['access_token'] ?? null;
        if (!$token) {
            throw new \RuntimeException('Access token not found in PayPal response');
        }

        return $this->accessToken = $token;
    }


}
