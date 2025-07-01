<?php
namespace App\Core;

use App\Core\Router;

class App
{
    private static ?App $instance = null;

    private Router $router;

    private function __construct()
    {
        $this->router = new Router();

    }

    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function run(): void
    {
        $this->handleRequest();
    }


    private function handleRequest(): void
    {
        $this->getRouter()->dispatch();

    }

    protected function getRouter(): Router
    {
        return $this->router;
    }
}