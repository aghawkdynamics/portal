<?php
namespace App\Core;

abstract class ApiController extends Controller
{
    public function json(array $data = []): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function jsonError(string $message, int $code = 500): void
    {
        http_response_code($code);
        $this->json(['error' => $message]);
    }

    public function template(string $template, array $params = []): void
    {
        //
    }
}
