<?php
class UserSubscription {
    private $sqlManager;
    private $table = 'user_subscriptions';

    public function __construct($sqlManager) {
        $this->sqlManager = $sqlManager;
    }

    public function create($data) {
        if (!isset($data['is_active'])) $data['is_active'] = 1;
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
         
         // Search by User ID or Subscription ID if numeric search provided? 
         // Or generic text search logic isn't very useful here unless we join.
         // For now, assume search is empty or precise ID matching if needed.
         
         if (!empty($whereClauses)) {
             $sql .= " WHERE " . implode(" AND ", $whereClauses);
         }

         // Count
         $countSql = "SELECT COUNT(*) as total FROM " . $this->table;
         if (!empty($whereClauses)) {
             $countSql .= " WHERE " . implode(" AND ", $whereClauses);
         }
         $totalResult = $this->sqlManager->query($countSql, $params);
         $total = $totalResult[0]['total'];

         // Data
         $sql .= " ORDER BY $orderBy LIMIT $limit OFFSET $offset";
         $data = $this->sqlManager->query($sql, $params);
         
         return ['data' => $data, 'total' => $total];
    }
    
    public function getActiveByUserId($userId) {
        return $this->sqlManager->get($this->table, '*', ['user_id' => $userId, 'is_active' => 1]);
    }

    public function hasValidSubscription($userId) {
        $sql = "SELECT id FROM " . $this->table . " 
                WHERE user_id = :user_id 
                AND is_active = 1 
                AND start_date <= NOW() 
                AND end_date >= NOW() 
                LIMIT 1";
        
        $result = $this->sqlManager->query($sql, ['user_id' => $userId]);
        return count($result) > 0;
    }
}
?>
