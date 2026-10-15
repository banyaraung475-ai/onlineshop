<?php
require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    
    public $conn;
    
    public function __construct() {
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = 'utf8mb4';
        
        $this->connect();
    }
    
    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }
    
    public function insert($table, $data) {
        if (empty($data)) {
            throw new Exception("No data provided for insert operation");
        }
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->query($sql, $values);
        
        if ($stmt) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        if (empty($data)) {
            throw new Exception("No data provided for update operation");
        }
        
        $setParts = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        $values = array_merge($values, $whereParams);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        $stmt = $this->query($sql, $values);
        
        return $stmt ? $stmt->rowCount() : false;
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        
        return $stmt ? $stmt->rowCount() : false;
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM $table WHERE $where";
        $result = $this->fetchOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    public function exists($table, $where, $params = []) {
        $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
        $result = $this->fetchOne($sql, $params);
        return !empty($result);
    }
    
    public function getTableInfo($table) {
        $sql = "DESCRIBE $table";
        return $this->fetchAll($sql);
    }
    
    public function getTables() {
        $sql = "SHOW TABLES";
        $tables = $this->fetchAll($sql);
        return array_column($tables, array_key_first($tables[0] ?? []));
    }
    
    // Pagination helper
    public function paginate($table, $page = 1, $limit = 10, $where = '1=1', $params = [], $orderBy = '') {
        $offset = ($page - 1) * $limit;
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM $table WHERE $where";
        $countResult = $this->fetchOne($countSql, $params);
        $total = $countResult ? (int)$countResult['total'] : 0;
        
        // Get data
        $sql = "SELECT * FROM $table WHERE $where";
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        $sql .= " LIMIT $limit OFFSET $offset";
        
        $data = $this->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    // Search helper
    public function search($table, $columns, $term, $where = '1=1', $params = []) {
        if (empty($columns) || empty($term)) {
            return [];
        }
        
        $searchConditions = [];
        foreach ($columns as $column) {
            $searchConditions[] = "$column LIKE ?";
            $params[] = "%$term%";
        }
        
        $searchWhere = '(' . implode(' OR ', $searchConditions) . ')';
        $sql = "SELECT * FROM $table WHERE $where AND $searchWhere";
        
        return $this->fetchAll($sql, $params);
    }
    
    // Backup table
    public function backupTable($table) {
        $sql = "SELECT * FROM $table";
        $data = $this->fetchAll($sql);
        
        if (empty($data)) {
            return [];
        }
        
        $backup = [
            'table' => $table,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        return $backup;
    }
    
    // Close connection
    public function close() {
        $this->conn = null;
    }
    
    public function __destruct() {
        $this->close();
    }
}

// Create database instance
try {
    $db = new Database();
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    // In production, you might want to show a user-friendly error page
    die("Database connection failed. Please contact the administrator.");
}
?>
