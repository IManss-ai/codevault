<?php
/**
 * Database Connection Class
 * 
 * Provides a singleton PDO connection to the Supabase PostgreSQL database.
 * Uses credentials from the .env file loaded by config/env.php.
 * 
 * Usage: $pdo = Database::connect();
 */

class Database
{
    private static ?PDO $instance = null;

    /**
     * Get the PDO connection instance (creates it on first call).
     */
    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? '';
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? '5432';
            $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'postgres';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? '';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';

            if (empty($host) || empty($user)) {
                die('Debug: host=' . ($host ?: 'EMPTY') . ' user=' . ($user ?: 'EMPTY') . ' env_keys=' . implode(',', array_keys($_ENV)));
            }

            $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode=require";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Never expose raw DB errors to users in production
                error_log('Database connection failed: ' . $e->getMessage());
                die('DB Error: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    // Prevent cloning and unserialization
    private function __construct() {}
    private function __clone() {}
    public function __wakeup() { throw new \Exception("Cannot unserialize singleton"); }
}
