<?php
class SupportedPhone {
    private $sqlManager;
    private $table = 'supported_phones';

    public function __construct($sqlManager) {
        $this->sqlManager = $sqlManager;
    }

    public function create($temperId, $name) {
        return $this->sqlManager->insert($this->table, ['temper_id' => $temperId, 'name' => $name, 'is_active' => 1]);
    }
    
    public function getByTemperId($temperId) {
        return $this->sqlManager->get($this->table, '*', ['temper_id' => $temperId]);
    }

    public function deleteByTemperId($temperId) {
        return $this->sqlManager->delete($this->table, ['temper_id' => $temperId]);
    }
}
?>
