<?php
namespace App\Core;

class Config
{
    const FILE = __DIR__ . '/../../config/config.php';
    const LOCAL_FILE = __DIR__ . '/../../config/config.php.local.php';
    const TABLE = 'config';

    private static array $data = [];
    private static $isLoaded = false;

    public function __construct()
    {
        // Load the default configuration file
        $this->load();
        // Merge with local configuration if available
        //$this->loadLocal();
    }

    /**
     * Load configuration data from a file.
     *
     * @param string $file Path to the configuration file.
     * @return array The loaded configuration data.
     * @throws \RuntimeException If the file does not exist or cannot be loaded.
     */
    public static function load(?string $file = null): array
    {
        $file = $file ?? self::FILE;

        if (!file_exists($file)) {
            throw new \RuntimeException("Config file not found: " . $file);
        }
        $data = require $file;
        $localData = @include ($file . '.local.php') ?: []; // Load local config if exists
        if (is_array($localData)) {
            $data = array_merge($data, $localData);
        }

        $data = array_merge($data, self::loadDb()); // Load database config if needed

        if (!is_array($data)) {
            throw new \RuntimeException("Config file must return an array: " . $file);
        }

        self::$data = array_merge(self::$data, $data);

        self::$isLoaded = true;

        return self::$data;
    }

    /**
     * Load the default configuration file.
     *
     * This method loads the default configuration file and merges it with local configuration if available.
     *
     * @return array The merged configuration data.
     */
    private function loadLocal(): array
    {
        $this->load(self::LOCAL_FILE);
        return self::$data;
    }

    private static function loadDb(): array
    {
        // This method is intended to load configuration from a database.
        // For now, it returns an empty array as a placeholder.
        return [];
    }

    /**
     * Get a configuration value by key.
     *
     * This method allows you to retrieve a configuration value using a dot notation key.
     * For example, if the configuration is structured as ['database' => ['host' => 'localhost']],
     * you can retrieve the host with Config::get('database.host').
     *
     * @param string $key The configuration key in dot notation.
     * @param mixed $default The default value to return if the key does not exist.
     * @return mixed The configuration value or the default value if not found.
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$isLoaded) {
            // Load the default configuration file if not already loaded
            self::load();
            self::$isLoaded = true;
        }

        $parts = explode('.', $key);
        $value = self::$data;
        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }
        return $value;
    }

    /**
     * Set a configuration value by key.
     *
     * @param string $key The configuration key.
     * @param mixed $value The value to set.
     */
    public static function set(string $key, $value): void
    {
        self::$data[$key] = $value;
        self::save();
    }

    /**
     * Save the current configuration data to the file.
     *
     * @throws \RuntimeException If the configuration file cannot be written.
     */
    public static function save(): void
    {
        // $file = self::get('config_file', 'config.php');
        // $content = "<?php\nreturn " . var_export(self::$data, true) . ";\n";
        // if (file_put_contents($file, $content) === false) {
        //     throw new \RuntimeException("Failed to write config file: $file");
        // }

        // For now, we will not save to a file, but this method can be implemented later.
        self::saveDb();
        self::$isLoaded = true; // Ensure the configuration is marked as loaded after saving
    }

    /**
     * Save the current configuration data to the database.
     *
     * @throws \RuntimeException If the configuration cannot be saved.
     */
    protected static function saveDb(): void
    {
        // This method is intended to save configuration to a database.
        // For now, it does nothing as a placeholder.
    }

    /**
     * Get all configuration data.
     *
     * @return array The entire configuration data.
     */
    public static function all(): array
    {
        return self::$data;
    }

    /**
     * Clear the configuration data.
     */
    public static function clear(): void
    {
        self::$data = [];
    }

}