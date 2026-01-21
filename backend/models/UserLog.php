<?php
class UserLog {
    private $sqlManager;
    private $table = 'user_log';

    public function __construct($sqlManager) {
        $this->sqlManager = $sqlManager;
    }

    public function log($userId, $actionName, $actionData = null) {
        return $this->sqlManager->insert($this->table, [
            'user_id' => $userId,
            'action_name' => $actionName,
            'action_data' => json_encode($actionData)
        ]);
    }

    public function getLogs($userId) {
        return $this->sqlManager->get($this->table, '*', ['user_id' => $userId], 'created_at DESC');
    }
    
    // Read-Only Admin Access
    public function getAll($filters = [], $limit = 10, $offset = 0, $search = '', $orderBy = 'created_at DESC') {
         $sql = "SELECT * FROM " . $this->table;
         $params = [];
         $whereClauses = [];

         foreach ($filters as $key => $value) {
             $whereClauses[] = "$key = :$key";
             $params[$key] = $value;
         }

         if (!empty($search)) {
             $whereClauses[] = "(action_name LIKE :search)";
             $params['search'] = "%$search%";
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
    
    // Stub Create/Update/Delete to prevent write access if Admin Manager tries calling them?
    // The Admin Manager configuration will mark 'read_only' => true, so these won't be called.
    // But good to have if needed for interface compliance.
    public function create($data) { return false; }
    public function update($id, $data) { return false; }
    public function delete($id) { return false; }
}
?>
