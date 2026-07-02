<?php

/**
 * Clase DB
 *
 * Centraliza la conexion a MySQL usando PDO. Tambien ofrece metodos pequeños
 * para ejecutar consultas preparadas de forma segura desde los modelos.
 */
class DB
{
    private string $host;
    private string $dbName;
    private string $user;
    private string $password;
    private string $charset;
    private ?PDO $pdo = null;

    public function __construct()
    {
        $this->cargarVariablesEntorno();

        $this->host = $this->env('DB_HOST', 'localhost');
        $this->dbName = $this->env('DB_DATABASE', 'productosdb');
        $this->user = $this->env('DB_USERNAME', 'root');
        $this->password = $this->env('DB_PASSWORD', 'CelestialVoid9016*/');
        $this->charset = $this->env('DB_CHARSET', 'utf8mb4');
    }

    /**
     * Crea o reutiliza la conexion PDO configurada para lanzar excepciones.
     */
    public function conectar(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset={$this->charset}";
            $this->pdo = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $this->pdo;
        } catch (PDOException $e) {
            throw new RuntimeException('No se pudo conectar a la base de datos.');
        }
    }

    /**
     * Ejecuta una consulta general. Usar solo cuando no haya datos externos.
     */
    public function query(string $sql): PDOStatement
    {
        return $this->conectar()->query($sql);
    }

    /**
     * Ejecuta un INSERT con parametros preparados.
     */
    public function insertSeguro(string $sql, array $params = []): int
    {
        $stmt = $this->conectar()->prepare($sql);
        $stmt->execute($params);

        return (int) $this->conectar()->lastInsertId();
    }

    /**
     * Ejecuta un UPDATE con parametros preparados.
     */
    public function updateSeguro(string $sql, array $params = []): int
    {
        $stmt = $this->conectar()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Ejecuta un SELECT con parametros preparados y devuelve todas las filas.
     */
    public function selectSeguro(string $sql, array $params = []): array
    {
        $stmt = $this->conectar()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Carga un archivo .env simple sin librerias externas. Las variables ya
     * definidas en el servidor tienen prioridad sobre las del archivo.
     */
    private function cargarVariablesEntorno(): void
    {
        $rutaEnv = dirname(__DIR__) . '/.env';

        if (!is_readable($rutaEnv)) {
            return;
        }

        $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);
            $valor = trim($valor, "\"'");

            if ($clave !== '' && getenv($clave) === false) {
                putenv($clave . '=' . $valor);
                $_ENV[$clave] = $valor;
            }
        }
    }

    private function env(string $clave, string $valorPorDefecto = ''): string
    {
        $valor = getenv($clave);

        if ($valor === false && array_key_exists($clave, $_ENV)) {
            $valor = $_ENV[$clave];
        }

        return $valor === false ? $valorPorDefecto : (string) $valor;
    }
}
