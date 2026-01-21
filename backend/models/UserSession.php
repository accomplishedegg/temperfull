<?php
class UserSession {
    private $sqlManager;
    private $table = 'user_session';

    public function __construct($sqlManager) {
        $this->sqlManager = $sqlManager;
    }

    public function create($dataOrUserId, $device=null, $ip=null) {
        // Handle both older signature and new array signature
        if (is_array($dataOrUserId)) {
            $data = $dataOrUserId;
             if (!isset($data['is_active'])) $data['is_active'] = 1;
             return $this->sqlManager->insert($this->table, $data);
        } else {
             // Backward compatibility for existing auth.php calls
             // Expect $device to effectively allow optional session_code if passed in newer calls?
             // Actually, let's just assume new calls use the array format if possible, 
             // OR add specific argument.
             // Given the dynamic nature, I will update it to:
             return $this->sqlManager->insert($this->table, [
                'user_id' => $dataOrUserId,
                'device' => $device,
                'ip' => $ip,
                'session_code' => func_num_args() > 3 ? func_get_arg(3) : null, // Handle optional 4th arg
                'is_active' => 1
            ]);
        }
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
         
         if (!empty($search)) {
             $whereClauses[] = "(device LIKE :search OR ip LIKE :search)";
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
    
    public function getActiveSessions($userId) {
        return $this->sqlManager->get($this->table, '*', ['user_id' => $userId, 'is_active' => 1]);
    }

    public function getBySessionCode($sessionCode) {
        // get only single instance 
        return $this->sqlManager->get($this->table, '*', ['session_code' => $sessionCode], 1);
    }
}
?>
