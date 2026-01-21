<?php
class SqlManager {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Insert Data
    public function insert($table, $data) {
        $keys = array_keys($data);
        $fields = implode(", ", $keys);
        $placeholders = ":" . implode(", :", $keys);

        $query = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($query);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Update Data
    public function update($table, $data, $conditions) {
        $fields = "";
        foreach ($data as $key => $value) {
            $fields .= "$key = :$key, ";
        }
        $fields = rtrim($fields, ", ");

        $where = "";
        foreach ($conditions as $key => $value) {
            $where .= "$key = :cond_$key AND ";
        }
        $where = rtrim($where, " AND ");

        $query = "UPDATE $table SET $fields WHERE $where";
        $stmt = $this->conn->prepare($query);

        foreach ($data as $key => $value) {
             $stmt->bindValue(":$key", $value);
        }
        foreach ($conditions as $key => $value) {
            $stmt->bindValue(":cond_$key", $value);
        }

        return $stmt->execute();
    }

    // Delete Data
    public function delete($table, $conditions) {
        $where = "";
        foreach ($conditions as $key => $value) {
            $where .= "$key = :$key AND ";
        }
        $where = rtrim($where, " AND ");

        $query = "DELETE FROM $table WHERE $where";
        $stmt = $this->conn->prepare($query);

        foreach ($conditions as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        return $stmt->execute();
    }

    // Get Data (Retrieve with filters)
    public function get($table, $columns = '*', $conditions = [], $orderBy = null, $limit = null) {
        $query = "SELECT $columns FROM $table";
        
        if (!empty($conditions)) {
            $where = "";
            foreach ($conditions as $key => $value) {
                // Support specialized operators like '>', '<', etc. if needed via key naming or value structure, 
                // but for now simple equality.
                $where .= "$key = :$key AND ";
            }
            $where = rtrim($where, " AND ");
            $query .= " WHERE $where";
        }

        if ($orderBy) {
            $query .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $query .= " LIMIT $limit";
        }

        $stmt = $this->conn->prepare($query);

        if (!empty($conditions)) {
            foreach ($conditions as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get Single Row
    public function getSingle($table, $columns = '*', $conditions = []) {
        $results = $this->get($table, $columns, $conditions, null, 1);
        return !empty($results) ? $results[0] : null;
    }

    // Raw Execute (for INSERT/UPDATE/DELETE)
    public function execute($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }
    
    // Raw Query (for SELECT)
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
