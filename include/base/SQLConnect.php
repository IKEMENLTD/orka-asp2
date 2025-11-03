<?php
/**
 * SQLConnect Class - Database connection
 */
class SQLConnect {
    public $connect = false;
    protected $pdo = null;

    public static function Create($master, $id, $pass, $dbname, $server, $port) {
        if ($master === 'PostgreSQLDatabase') {
            return new PostgreSQLConnect($id, $pass, $dbname, $server, $port);
        } elseif ($master === 'SQLiteDatabase') {
            return new SQLiteConnect($dbname);
        }
        return new self();
    }

    public function run($query, $params = []) {
        if (!$this->pdo) {
            return false;
        }
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("SQL Error: " . $e->getMessage());
            return false;
        }
    }

    public function fetch($result) {
        if ($result) {
            return $result->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
}

class PostgreSQLConnect extends SQLConnect {
    public function __construct($id, $pass, $dbname, $server, $port) {
        try {
            $dsn = "pgsql:host={$server};port={$port};dbname={$dbname}";
            $this->pdo = new PDO($dsn, $id, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $this->connect = true;
        } catch (PDOException $e) {
            error_log("PostgreSQL Connection Error: " . $e->getMessage());
            $this->connect = false;
        }
    }
}

class SQLiteConnect extends SQLConnect {
    public function __construct($dbname) {
        try {
            $this->pdo = new PDO("sqlite:{$dbname}");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connect = true;
        } catch (PDOException $e) {
            error_log("SQLite Connection Error: " . $e->getMessage());
            $this->connect = false;
        }
    }
}
?>
