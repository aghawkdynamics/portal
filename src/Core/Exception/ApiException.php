<?php

namespace App\Core\Exception;


class ApiException extends \Exception
{
    /**
     * @var int HTTP status code
     */
    protected int $statusCode;

    /**
     * ApiException constructor.
     *
     * @param string $message
     * @param int $statusCode
     */
    public function __construct(string $message, int $statusCode = 400)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}