<?php
/**
 * Database Configuration - Migration vers PDO
 * À utiliser pour remplacer le code mysqli disparate
 */

class Database {
    private static $instance = null;
    private $pdo = null;
    private $prepared_statements = [];

    private function __construct() {
        try {
            $dsn = 'mysql:host=' . get_env('DB_HOST', 'localhost') 
                 . ';port=' . get_env('DB_PORT', 3306)
                 . ';dbname=' . get_env('DB_NAME', 'accountopening_db')
                 . ';charset=utf8mb4';
            
            $this->pdo = new PDO(
                $dsn,
                get_env('DB_USER', 'admin'),
                get_env('DB_PASS', 'Passw@rd'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            log_action('ERROR', 'Database Connection Failed', ['error' => $e->getMessage()]);
            die('Database connection failed');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            log_action('ERROR', 'Query Error', ['sql' => $sql, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        try {
            $this->query($sql, array_values($data));
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            log_action('ERROR', 'Insert Failed', ['table' => $table, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update($table, $data, $where) {
        $set = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
        $sql = "UPDATE $table SET $set WHERE $where";
        
        try {
            return $this->query($sql, array_values($data))->rowCount();
        } catch (PDOException $e) {
            log_action('ERROR', 'Update Failed', ['table' => $table, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getPDO() {
        return $this->pdo;
    }
}

// Usage Examples:
/*
$db = Database::getInstance();

// SELECT
$user = $db->fetch("SELECT * FROM tblemployees WHERE emp_id = ?", [$emp_id]);

// INSERT
$id = $db->insert('tblemployees', [
    'FirstName' => $fname,
    'EmailId' => $email,
    'Password' => password_hash($password, PASSWORD_DEFAULT)
]);

// UPDATE
$db->update('tblemployees', ['Status' => 'active'], "emp_id = '$emp_id'");
*/
?>
