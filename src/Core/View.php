<?php
namespace App\Core;

class View 
{
    const DEFAULT_TEMPLATE = 'index';
    const TEMPLATES_DIR = __DIR__ . '/../../views/';
    const LAYOUTS_DIR = __DIR__ . '/../../views/layout/';
    const HEADER_FILE = self::LAYOUTS_DIR . 'header.phtml';
    const CONTENT_FILE = self::LAYOUTS_DIR . 'content.phtml';
    const FOOTER_FILE = self::LAYOUTS_DIR . 'footer.phtml';

    private string $template = self::DEFAULT_TEMPLATE;
    private array $params = [];

    public function __construct(
        private Controller $controller, 
        ?string $template = null, 
        array $params = []
    ) {
        $this->template = $template ?? self::DEFAULT_TEMPLATE;
        $this->params = $params;
    }

    /**
     * Check if the minified CSS file exists.
     *
     * This method checks if the minified CSS file is present in the public directory.
     *
     * @return bool True if the minified CSS file exists, false otherwise
     */
    public static function hasMinifiedCSS(): bool
    {
        return file_exists(__DIR__ . '/../../public/css/minified.css');
    }

    /**
     * Get the template file path.
     *
     * This method returns the full path to the template file based on the current template name.
     *
     * @return string The full path to the template file
     */
    public function getTemplate(): string
    {
        return self::TEMPLATES_DIR . $this->template . '.phtml';
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    /**
     * Render the view with the given parameters.
     *
     * This method will extract the parameters to variables and include the view file.
     * If $standalone is true, it will only include the view file without header/footer.
     *
     * @param array $params Parameters to pass to the view
     * @param bool $standalone Whether to render only the view file
     */
    public function render(array $params = [], bool $standalone = false): void
    {
        try {
            
            $this->params = array_merge($this->params, $params);
            extract($this->params); //allow access to params as variables

            if ($standalone) { // If rendering standalone, include only the view file. f.e. ajax requests
                $viewFile = self::TEMPLATES_DIR . $this->template . '.phtml';
                    if (!file_exists($viewFile)) {
                    throw new \RuntimeException("View not found: $viewFile");
                }
                include $viewFile;
                return;
            }
            /**
             @todo implement render more nicely
            */
            include self::HEADER_FILE;
            include self::CONTENT_FILE;
            include self::FOOTER_FILE;
        } catch (\Throwable $e) {
            // Handle the error gracefully
            http_response_code(500);
            echo 'An error occurred while rendering the view.';
            error_log($e->getMessage());
            echo '<pre>';
            echo $e->getMessage();
            echo $e->getTraceAsString();
            echo '</pre>';
        }
        
    }

    /**
     *
     * @return Controller The controller instance
     */
    public function getController(): Controller
    {
        return $this->controller;
    }

    /**
     *
     * @return Request The request object
     */
    public function getRequest(): Request
    {
        return $this->getController()->getRequest();
    }

    /**
     * Get a variable from the view parameters.
     *
     * @param string $key The key of the variable to retrieve
     * @param mixed $default The default value to return if the key does not exist
     * @return mixed The value of the variable or the default value
     */
    public function var(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Set a variable in the view parameters.
     *
     * @param string $key The key of the variable to set
     * @param mixed $value The value to set for the variable
     */
    public function setVar(string $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }

    /**
     * Get all variables in the view parameters.
     *
     * @return array An associative array of all variables in the view
     */
    public function vars(): array
    {
        return $this->params;
    }

    /**
     * Set multiple variables in the view parameters.
     *
     * @param array $params An associative array of variables to set in the view
     */
    public function setVars(array $params): void
    {
        $this->params = $params;
    }
}