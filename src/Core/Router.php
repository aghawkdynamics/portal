<?php
namespace App\Core;

use App\Controller\ErrorController;
use App\Controller\NotFoundController;

class Router
{
    public function dispatch(): void
    {
        try {
            $route = $_GET['q'] ?? 'home/index';

            // Handle route as separate Controller for Action
            // This allows for cleaner routing and better separation of concerns
            if ($this->dispatchV2($route)) {
                return;
            }

            // Fallback to controllers with multiple segments
            $parts = explode('/', $route);
            if (count($parts) === 1) {
                $parts[] = 'index';
            }
            $action = array_pop($parts) ?: 'index';
            $controllerName = implode('\\', array_map('ucfirst', $parts)) ?: 'Home';

            $class = 'App\\Controller\\' . ucfirst($controllerName) . 'Controller';
            if (!class_exists($class)) {
                http_response_code(404);
                echo 'Controller not found';
                return;
            }
            $controller = new $class();
            if (!method_exists($controller, $action) || !is_callable([$controller, $action])) {
                http_response_code(404);
                $controller = new NotFoundController();
                $controller->index();
                die();
            }
                
            $controller->$action();

        } catch (\App\Core\Exception\ApiException $e) {
            // Handle API exceptions with specific status codes
            http_response_code($e->getStatusCode());
            echo $e->getMessage();

        } catch (\Throwable $e) {
            try {
                http_response_code(500);
                $controller = new ErrorController();
                $controller->index($e);
            } catch (\Throwable $e) {
                // Fallback to a simple error message
                http_response_code(500);
                echo 'Internal Server Error';
                error_log($e->getMessage());
                echo '<pre>';
                echo $e->getMessage();
                echo $e->getTraceAsString();
                echo '</pre>';
            }
        }
    }

    /**
     * Dispatch a route to the appropriate controller/action.
     * e.g. 'account/profile' -> \App\Controller\Account\ProfileController
     */
    private function dispatchV2(string $route): bool
    {
        $parts = explode('/', $route);
        if (count($parts) < 2) {
            $parts[] = 'index'; // Default action if not specified
        }
        $route = implode('\\', array_map('ucfirst', $parts));
        $route = str_replace('-', '', $route); // Remove dashes for class names
        $route = str_replace('_', '', $route); // Remove underscores for class names
        $controllerClass = '\\App\\Controller\\' . $route;
        if (!class_exists($controllerClass) || !is_subclass_of($controllerClass, Controller::class)) {
            return false;
        }

        $controller = new $controllerClass();
        $controller->handle();

        return true;
    }

}
