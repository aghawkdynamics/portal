<?php
namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $cfg = Config::get('db');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $cfg['host'],
                $cfg['port'],
                $cfg['database_name']
            );
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }

    /**
     * Get the PDO instance.
     *
     * @return PDO
     * @throws \RuntimeException if the database connection is not established
     */
    public function getPdo(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database connection not established.');
        }
        return self::$pdo;
    }

    /**
     * Disconnect from the database.
     */
    public static function disconnect(): void
    {
        self::$pdo = null;
    }

    /**
     * Check if the database connection is established.
     *
     * @return bool True if connected, false otherwise
     */
    public static function isConnected(): bool
    {
        return self::$pdo !== null;
    }

    public static function assertConnected(): void
    {
        if (!self::isConnected()) {
            throw new \RuntimeException('Database connection not established.');
        }
    }

    /**
     * Get the last inserted ID.
     *
     * @return int|null The last inserted ID or null if no ID was generated
     * @throws \RuntimeException if the database connection is not established
     */
    public static function getLastInsertId(): ?int
    {
        static::assertConnected();

        return self::$pdo->lastInsertId() ?: null;
    }

    /**
     * Prepare and execute a SQL query with parameters.
     *
     * @param string $sql The SQL query to prepare
     * @param array $params Parameters to bind to the SQL query
     * @return \PDOStatement The prepared statement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        static::assertConnected();

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Execute a SQL statement with parameters.
     *
     * @param string $sql The SQL statement to execute
     * @param array $params Parameters to bind to the SQL statement
     * @return bool True on success, false on failure
     */
    public static function execute(string $sql, array $params = []): bool
    {
        static::assertConnected();

        $stmt = self::$pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Begin a transaction.
     *
     * @throws \RuntimeException if the database connection is not established
     */
    public static function beginTransaction(): void
    {
        static::assertConnected();

        self::$pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     *
     * @throws \RuntimeException if the database connection is not established
     */
    public static function commit(): void
    {
        static::assertConnected();
        
        self::$pdo->commit();
    }

    /**
     * Roll back the current transaction.
     *
     * @throws \RuntimeException if the database connection is not established
     */
    public static function rollBack(): void
    {
        static::assertConnected();

        self::$pdo->rollBack();
    }

    /**
     * Escape a string for use in a SQL query.
     *
     * @param string $value The string to escape
     * @return string The escaped string
     * @throws \RuntimeException if the database connection is not established
     */
    public static function quote(string $value): string
    {
        static::assertConnected();
       
        return self::$pdo->quote($value);
    }

}
