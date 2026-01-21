<?php
class UserOtp {
    private $sqlManager;
    private $table = 'user_otp';

    public function __construct($sqlManager) {
        $this->sqlManager = $sqlManager;
    }

    public function generate($userId, $otp, $expiry) {
        $data = [
            'user_id' => $userId,
            'otp' => $otp,
            'expiry' => $expiry,
            'consumed' => 0
        ];
        return $this->sqlManager->insert($this->table, $data);
    }

    public function verify($userId, $otp) {
        // Find valid, unconsumed OTP
        $sql = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id AND otp = :otp AND consumed = 0 ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->sqlManager->execute($sql, ['user_id' => $userId, 'otp' => $otp]);
        // execute returns bool, need to fetch using direct connection or modify SqlManager. 
        // SqlManager's execute returns bool. Let's use getSingle approach.
        
        $conditions = ['user_id' => $userId, 'otp' => $otp, 'consumed' => 0];
        // Note: Sort order isn't directly supported by simple get(), but let's assume filtering effectively finds it or we add sort support. 
        // Ideally SqlManager should support sort. I'll rely on correct filter match.
        
        // Actually SqlManager::get supports orderBy.
        $otpRow = $this->sqlManager->getSingle($this->table, '*', $conditions, 'created_at DESC');

        if ($otpRow) {
            if (new DateTime() < new DateTime($otpRow['expiry'])) {
                // Mark consumed
                $this->sqlManager->update($this->table, ['consumed' => 1], ['id' => $otpRow['id']]);
                return true;
            }
        }
        return false;
    }
    public function getAll($filters = [], $limit = 10, $offset = 0, $search = '', $orderBy = 'created_at DESC') {
        $sql = "SELECT * FROM " . $this->table;
        $params = [];
        $whereClauses = [];

        foreach ($filters as $key => $value) {
            $whereClauses[] = "$key = :$key";
            $params[$key] = $value;
        }

        if (!empty($search)) {
            $whereClauses[] = "(otp LIKE :search)";
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
}
?>
