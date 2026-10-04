<?php
namespace App\Core\Database;

use App\Config\DatabaseConfig;
use mysqli;
use Exception;

class Connection {
    private static ?Connection $instance = null;
    private ?mysqli $mysqli = null;

    private function __construct(bool $connectToDb = true) {
        $host = DatabaseConfig::$host;
        $user = DatabaseConfig::$username;
        $pass = DatabaseConfig::$password;
        $port = DatabaseConfig::$port;
        $dbname = $connectToDb ? DatabaseConfig::$dbname : '';

        // Prevent reporting mysqli errors as warnings to handle exceptions cleanly
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            if ($dbname !== '') {
                $this->mysqli = new mysqli($host, $user, $pass, $dbname, $port);
            } else {
                $this->mysqli = new mysqli($host, $user, $pass, '', $port);
            }
            $this->mysqli->set_charset(DatabaseConfig::$charset);
        } catch (Exception $e) {
            throw new Exception("Database Connection Failed: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance(bool $connectToDb = true): Connection {
        if (self::$instance === null) {
            self::$instance = new Connection($connectToDb);
        }
        return self::$instance;
    }

    public function getMysqli(): mysqli {
        return $this->mysqli;
    }

    public function prepare(string $sql) {
        return $this->mysqli->prepare($sql);
    }

    public function query(string $sql) {
        return $this->mysqli->query($sql);
    }

    public function escape(string $value): string {
        return $this->mysqli->real_escape_string($value);
    }

    public function getLastInsertId(): int {
        return (int)$this->mysqli->insert_id;
    }

    public function beginTransaction(): bool {
        return $this->mysqli->begin_transaction();
    }

    public function commit(): bool {
        return $this->mysqli->commit();
    }

    public function rollback(): bool {
        return $this->mysqli->rollback();
    }

    /**
     * Safe parameterized execution
     */
    public function select(string $sql, array $params = [], string $types = ''): array {
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysqli->error);
        }

        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
        }
        $stmt->close();
        return $rows;
    }

    public function selectOne(string $sql, array $params = [], string $types = ''): ?array {
        $rows = $this->select($sql, $params, $types);
        return !empty($rows) ? $rows[0] : null;
    }

    public function execute(string $sql, array $params = [], string $types = ''): bool {
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysqli->error);
        }

        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
