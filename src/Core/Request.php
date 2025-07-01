<?php
namespace App\Core;

/**
 * Class Request
 * 
 * Singleton wrapper for HTTP request data (GET, POST, SESSION, etc).
 */
class Request
{
    /**
     * @var Request|null
     */
    private static ?Request $instance = null;

    /**
     * @var array
     */
    private array $session = [];
    /**
     * @var array
     */
    private array $cookies = [];
    /**
     * @var array
     */
    private array $server = [];
    /**
     * @var array
     */
    private array $params = [];
    /**
     * @var array
     */
    private array $queryParams = [];
    /**
     * @var array
     */
    private array $postParams = [];
    /**
     * @var array
     */
    private array $files = [];


    /**
     * Request constructor.
     * Initializes request data from PHP superglobals.
     */
    private function __construct()
    {
        session_start();
        $this->params = &$_REQUEST;
        $this->session = &$_SESSION;
        $this->cookies = &$_COOKIE;
        $this->server = &$_SERVER;
        $this->queryParams = &$_GET;
        $this->postParams = &$_POST;
        $this->files = &$_FILES;
    }

    /**
     * Get the singleton instance.
     * 
     * @return Request
     */
    public static function getInstance(): Request
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __clone()
    {
        throw new \RuntimeException('Cloning is not allowed.');
    }
    public function __wakeup()
    {
        throw new \RuntimeException('Deserialization is not allowed.');
    }

    public function holdReferer(): static
    {
        $this->session['referer'] = $this->session['referer'] ?? $this->server('HTTP_REFERER');

        return $this;
    }

    public function getReferer(): ?string
    {
        $referer = $this->session('referer');
        unset($this->session['referer']);

        return $referer ?? $this->server('HTTP_REFERER') ?? '/';
    }

    public function addMessage(string $message): static
    {
        $this->session['messages'][] = $message;

        return $this;
    }

    /**
     * Get all messages from the session.
     * 
     * @return array
     */
    public function getMessages(): array
    {
        $messages = $this->session['messages'] ?? [];
        unset($this->session['messages']);

        return array_reverse($messages);
    }

    /**
     * Get all errors from the session.
     * 
     * @return array
     */
    public function getErrors(): array
    {
        $errors = $this->session['errors'] ?? [];
        unset($this->session['errors']);

        return array_reverse($errors);
    }

    /**
     * Add an error message to the session.
     * 
     * @param string $error
     * @return static
     */
    public function addError(string $error): static
    {
        $this->session['errors'][] = $error;

        return $this;
    }

    public function addWarning(string $warning): static
    {
        $this->session['warnings'][] = $warning;

        return $this;
    }

    public function getWarnings(): array
    {
        $warnings = $this->session['warnings'] ?? [];
        unset($this->session['warnings']);

        return array_reverse($warnings);
    }

    public function addInfo(string $info): static
    {
        $this->session['infos'][] = $info;

        return $this;
    }

    public function getInfos(): array
    {
        $infos = $this->session['infos'] ?? [];
        unset($this->session['infos']);

        return array_reverse($infos);
    }

    /**
     * Check if the request method is POST.
     * 
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getRequestMethod() === 'POST';
    }

    /**
     * Check if the request method is GET.
     * 
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getRequestMethod() === 'GET';
    }

    /**
     * Get the HTTP request method.
     * 
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->server('REQUEST_METHOD', 'GET');
    }

    /**
     * @deprecated
     * Get a value from $_REQUEST or all params.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function request(?string $key = null, mixed $default = null): mixed
    {
        return $this->getRequest($key, $default);
    }

    /**
     * Get a value from $_REQUEST or all params.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getRequest(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->params;
        }

        return $this->params[$key] ?? $default;
    }

    /**
     * @deprecated
     * Get a value from $_GET or all query params.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $this->getQuery($key, $default);
    }
    
    /**
     * Get a value from $_GET or all query params.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getQuery(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->queryParams;
        }

        return $this->queryParams[$key] ?? $default;
    }

    /**
     * @deprecated
     * Get a value from $_POST or all post params.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $key = null, mixed $default = null): mixed
    {
        return $this->getPost($key, $default);
    }

    /**
     * Get a value from $_POST or all post params.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getPost(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->postParams;
        }

        return $this->postParams[$key] ?? $default;
    }

    /**
     * @deprecated
     * Get a value from $_SESSION or all session data.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function session(?string $key = null, mixed $default = null): mixed
    {
        return $this->getSession($key, $default);
    }

    /**
     * Get a value from $_SESSION or all session data.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getSession(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->session;
        }

        return $this->session[$key] ?? $default;
    }

    /**
     * Set a value in $_SESSION.
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSession(string $key, mixed $value): void
    {
        $this->session[$key] = $value;
    }

    /**
     * Cleans the session data.
     * @return void
     */
    public function clearSession(): void
    {
        $this->session = [];
        $_SESSION = []; //fallback if not enough
    }

    /**
     * @deprecated
     * Get a value from $_COOKIE or all cookies.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function cookie(?string $key = null, mixed $default = null): mixed
    {
        return $this->getCookie($key, $default);
    }

    /**
     * Get a value from $_COOKIE or all cookies.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getCookie(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }

    /**
     * @deprecated
     * Get a value from $_SERVER or all server data.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function server(?string $key = null, mixed $default = null): mixed
    {
        return $this->getServer($key, $default);
    }

    /**
     * Get a value from $_SERVER or all server data.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getServer(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->server;
        }

        return $this->server[$key] ?? $default;
    }

    

    /**
     * Get a value from $_FILES or all files.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getFile(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? $default;
    }
    
}