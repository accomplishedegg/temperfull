<?php
class Temper {
    private $sqlManager;
    private $table = 'temper';

    public function __construct($sqlManager) {
        $this->sqlManager = $sqlManager;
    }

    public function create($name) {
        return $this->sqlManager->insert($this->table, ['name' => $name, 'is_active' => 1]);
    }

    public function getAll($filters = []) {
        return $this->sqlManager->get($this->table, '*', $filters);
    }

    public function getById($id) {
        return $this->sqlManager->getSingle($this->table, '*', ['id' => $id]);
    }

    public function getByUuid($uuid) {
        return $this->sqlManager->getSingle($this->table, '*', ['uuid' => $uuid]);
    }
    
    public function update($id, $data) {
        return $this->sqlManager->update($this->table, $data, ['id' => $id]);
    }

    public function delete($id) {
        return $this->sqlManager->delete($this->table, ['id' => $id]);
    }
}
?>
