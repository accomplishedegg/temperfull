<?php
class UserStock {
    private $sqlManager;
    private $table = 'user_stocks';

    public function __construct($sqlManager) {
        $this->sqlManager = $sqlManager;
    }

    public function create($data) {
        return $this->sqlManager->insert($this->table, $data);
    }

    public function update($id, $data) {
        return $this->sqlManager->update($this->table, $data, ['id' => $id]);
    }

    public function delete($id) {
         return $this->sqlManager->delete($this->table, ['id' => $id]);
    }

    public function getAll($filters = [], $limit = 10, $offset = 0, $search = '', $orderBy = 'id DESC') {
         $sql = "SELECT * FROM " . $this->table;
         $params = [];
         $whereClauses = [];

         foreach ($filters as $key => $value) {
             $whereClauses[] = "$key = :$key";
             $params[$key] = $value;
         }

         if (!empty($whereClauses)) {
             $sql .= " WHERE " . implode(" AND ", $whereClauses);
         }

         $countSql = "SELECT COUNT(*) as total FROM " . $this->table;
         if (!empty($whereClauses)) {
             $countSql .= " WHERE " . implode(" AND ", $whereClauses);
         }
         $totalResult = $this->sqlManager->query($countSql, $params);
         $total = $totalResult[0]['total'];

         $sql .= " ORDER BY $orderBy LIMIT $limit OFFSET $offset";
         $data = $this->sqlManager->query($sql, $params);
         
         return ['data' => $data, 'total' => $total];
    }
    
    public function addOrUpdate($userId, $temperId, $stock) {
        // Check if exists
        $existing = $this->sqlManager->getSingle($this->table, '*', ['user_id' => $userId, 'temper_id' => $temperId]);
        
        if ($existing) {
             return $this->sqlManager->update($this->table, ['available_stock' => $stock], ['id' => $existing['id']]);
        } else {
             return $this->sqlManager->insert($this->table, ['user_id' => $userId, 'temper_id' => $temperId, 'available_stock' => $stock]);
        }
    }

    public function getStock($userId, $temperId) {
        return $this->sqlManager->getSingle($this->table, '*', ['user_id' => $userId, 'temper_id' => $temperId]);
    }
}
?>
