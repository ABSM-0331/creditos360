<?php
class DBC
{
    private static ?PDO  $instance = null;

    private static array $config = [
        'host' => 'localhost',
        'port' => 3306,
        'db' => 'credioxcopia',
        'user' => 'root',
        'password' => 'Benjaminsosa?',
        'charset' => 'utf8mb4',
    ];


    public static function get(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                    self::$config['host'],
                    self::$config['port'],
                    self::$config['db'],
                    self::$config['charset']
                );
                self::$instance = new PDO(
                    $dsn,
                    self::$config['user'],
                    self::$config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,

                    ]
                );
            } catch (PDOException $e) {
                die("Error de conexión a la base de datos.");
            }
        }
        return self::$instance;
    }
}
