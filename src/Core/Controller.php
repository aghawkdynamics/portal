<?php
namespace App\Core;

use App\Controller\AuthController;

abstract class Controller
{
    private Request $request;


    public function __construct()
    {
        $this->request = Request::getInstance();
        $this->checkAuth();

        
    }

    /**
     * Get the request instance
     *
     * This method returns the Request instance associated with this controller.
     * It can be used to access request data such as GET, POST, SESSION, etc.
     *
     * @return mixed
     */
    public function getRequest(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            //main request object
            return $this->request;
        }

        //specific request key: @deprecated
        return $this->request->getRequest($key, $default);
    }

    /**
     * Get a value from the session
     *
     * This method retrieves a value from the session using the provided key.
     * If the key is not found, it returns the specified default value.
     *
     * @param string|null $key The key to retrieve from the session
     * @param mixed $default The default value to return if the key is not found
     * @return mixed The value from the session or the default value
     */
    public function getSession(?string $key = null, mixed $default = null): mixed
    {
        return $this->getRequest()->session($key, $default);
    }  

    /**
     * Check if user is authenticated
     */
    protected function checkAuth(): void
    {
        $uid = $this->getRequest()->session('uid');

        if (
            null === $uid 
            && !$this instanceof  AuthController
            //&& !$this instanceof \App\Controller\ErrorController
            && !$this instanceof \App\Controller\NotFoundController
            ) {
            $this->redirect('/?q=auth/login');
            exit;
        }
    }

    /**
     * Get a View instance for the given template
     *
     * This method creates a new View instance with the specified template and parameters.
     * It can be used to render views in the application.
     *
     * @param string $template The name of the view template
     * @param array $params Parameters to pass to the view
     * @return View The View instance
     */
    protected function getView(string $template, array $params = []): View
    {
        return new View($this, $template, $params);
    }

    /**
     * Render a view with the given parameters
     *
     * This method is used to render a view template with the provided parameters.
     * It will instantiate the View class and call its render method.
     *
     * @param string $view The name of the view template to render
     * @param array $params Parameters to pass to the view
     * @param bool $standalone Whether to render the view as a standalone page or not
     * @throws \RuntimeException If the view file does not exist
     */
    protected function render(string $view, array $params = [], bool $standalone = false): void
    {
        $viewObj = $this->getView($view, $params);
        $viewObj->render($params, $standalone);
    }

    /**
     * Forward to another controller and action
     *
     * This method allows you to forward the request to another controller and action.
     * It is useful for internal redirects within the application.
     *
     * @param string $controller The name of the controller to forward to
     * @param string $action The action method to call in the controller
     * @param array $params Additional parameters to pass to the action method
     */
    public function forward(string $controller, string $action = 'index', array $params = []): void
    {
        if ($this->forvardV2($controller, $action, $params)) {
            return;
        }

        $controllerClass = 'App\\Controller\\' . ucfirst($controller) . 'Controller';
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller not found: $controllerClass");
        }

        $controllerInstance = new $controllerClass();
        if (!method_exists($controllerInstance, $action)) {
            throw new \RuntimeException("Action not found: $action in $controllerClass");
        }

        call_user_func_array([$controllerInstance, $action], $params);
    }

    /**
     * Forward to another controller and action (version 2)
     *
     * This method is an alternative implementation of the forward method.
     * It allows for more flexibility in forwarding requests to different controllers and actions.
     *
     * @param string $controller The name of the controller to forward to
     * @param string $action The action method to call in the controller
     * @param array $params Additional parameters to pass to the action method
     * @return bool Returns true if the forwarding was successful, false otherwise
     */
    protected function forvardV2(string $controller, string $action = 'index', array $params = []): bool
    {

        $controllerClass = 'App\\Controller\\' . ucfirst($controller) . '\\'. ucfirst($action) ;
        if (!class_exists($controllerClass)) {
            return false;
        }

        $controllerInstance = new $controllerClass();
        if (!method_exists($controllerInstance, 'handle')) {
            return false;
        }

        call_user_func_array([$controllerInstance, 'handle'], $params);

        return true;
    }

    /**
     * Redirect to a given URL
     *
     * @param string $url The URL to redirect to
     */
    public function redirect(string $url, array $args = []): void
    {
        if (!empty($args)) {
            $url .= '?' . http_build_query($args);
        }

        header("Location: $url");

        exit;
    }

    /**
     * Redirect to the referer URL
     * 
     * This method will redirect the user back to the page they came from.
     * If no referer is available, it will redirect to the home page.
     */
    public function redirectReferer(): void
    {
        $referer = $this->getRequest()->getReferer();
        header("Location: $referer");
        exit;
    }

}
