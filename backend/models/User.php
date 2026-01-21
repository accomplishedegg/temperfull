<?php
class User {
    private $sqlManager;
    private $table = 'users';

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $phone_number;
    public $is_active;
    public $deactivated_remarks;

    public function __construct($sqlManager) {
        $this->sqlManager = $sqlManager;
    }

    public function create($data) {
        // Hash password if present
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        // Set defaults if not present (though Admin might force them)
        if (!isset($data['role'])) $data['role'] = 'user';
        if (!isset($data['is_active'])) $data['is_active'] = 1;

        return $this->sqlManager->insert($this->table, $data);
    }

    public function login($email, $password) {
        $user = $this->sqlManager->getSingle($this->table, '*', ['email' => $email]);
        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_active']) return ['error' => 'Account deactivated: ' . $user['deactivated_remarks']];
            
            $this->id = $user['id'];
            $this->name = $user['name'];
            $this->email = $user['email'];
            $this->role = $user['role'];
            return $user;
        }
        return false;
    }

    public function getById($id) {
        return $this->sqlManager->getSingle($this->table, '*', ['id' => $id]);
    }

    public function update($id, $data) {
        // If password is being updated, hash it
        if (isset($data['password'])) {
             if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
             } else {
                 // If empty password sent, likely means "don't change"
                 unset($data['password']);
             }
        }
        return $this->sqlManager->update($this->table, $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->sqlManager->delete($this->table, ['id' => $id]);
    }
    
    // Standardized getAll for Admin
    public function getAll($filters = [], $limit = 10, $offset = 0, $search = '', $orderBy = 'id DESC') {
         $sql = "SELECT * FROM " . $this->table;
         $params = [];
         $whereClauses = [];

         // Precise Filters
         foreach ($filters as $key => $value) {
             $whereClauses[] = "$key = :$key";
             $params[$key] = $value;
         }

         // Search (generic implementation for name/email)
         if (!empty($search)) {
             $whereClauses[] = "(name LIKE :search OR email LIKE :search OR phone_number LIKE :search)";
             $params['search'] = "%$search%";
         }

         if (!empty($whereClauses)) {
             $sql .= " WHERE " . implode(" AND ", $whereClauses);
         }

         // Count Total
         $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as sub";
         // Ideally replace SELECT * with SELECT COUNT(*) and remove order/limit
         // But reusing where logic is key.
         // Let's optimize:
         $countSql = "SELECT COUNT(*) as total FROM " . $this->table;
         if (!empty($whereClauses)) {
             $countSql .= " WHERE " . implode(" AND ", $whereClauses);
         }
         $totalResult = $this->sqlManager->query($countSql, $params);
         $total = $totalResult[0]['total'];

         // Order & Limit
         $sql .= " ORDER BY $orderBy LIMIT $limit OFFSET $offset";
         
         $data = $this->sqlManager->query($sql, $params);
         
         return ['data' => $data, 'total' => $total];
    }
}
?>
