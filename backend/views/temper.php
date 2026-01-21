<?php
// backend/views/temper.php

function handle_temper_crud($sqlManager, $models, $data) {
    // 1. Role Validation
    if (!isset($_SESSION['user_id'])) {
        return ['code' => 401, 'body' => ['message' => 'Not authenticated.']];
    }
    
    $currentUser = $models['User']->getById($_SESSION['user_id']);
    if (!$currentUser || !in_array($currentUser['role'], ['admin', 'sales_manager'])) {
        return ['code' => 403, 'body' => ['message' => 'Access denied.']];
    }

    $action = isset($data->action) ? $data->action : (isset($_GET['action']) ? $_GET['action'] : 'list');

    switch ($action) {
        case 'list':
            $page = isset($data->page) ? (int)$data->page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
            $limit = isset($data->limit) ? (int)$data->limit : (isset($_GET['limit']) ? (int)$_GET['limit'] : 10);
            $q = isset($data->q) ? $data->q : (isset($_GET['q']) ? $_GET['q'] : '');
            $offset = ($page - 1) * $limit;

            // Search Query
            // We need to find temper IDs that match name OR have a supported phone matching name
            // SELECT DISTINCT t.id FROM temper t LEFT JOIN supported_phones sp ON t.id = sp.temper_id WHERE ...
            
            $sql = "SELECT DISTINCT t.* FROM temper t LEFT JOIN supported_phones sp ON t.id = sp.temper_id";
            $params = [];
            
            if (!empty($q)) {
                 $sql .= " WHERE t.name LIKE :search OR sp.name LIKE :search";
                 $params['search'] = "%$q%";
            }
            
            // Count
            $countSql = "SELECT COUNT(DISTINCT t.id) as total FROM temper t LEFT JOIN supported_phones sp ON t.id = sp.temper_id";
             if (!empty($q)) {
                 $countSql .= " WHERE t.name LIKE :search OR sp.name LIKE :search";
            }
            
            $totalResult = $sqlManager->query($countSql, $params);
            $total = $totalResult[0]['total'];
            
            // Fetch Data
            $sql .= " ORDER BY t.id DESC LIMIT $limit OFFSET $offset";
            $temperList = $sqlManager->query($sql, $params);
            
            // Hydrate with Supported Phones
            foreach ($temperList as &$row) {
                // Get all supported phones for this temper
                $phones = $models['SupportedPhone']->getByTemperId($row['id']);
                $names = [];
                foreach ($phones as $p) {
                    $names[] = $p['name'];
                }
                $row['supportedPhones'] = $names;
            }

            return ['code' => 200, 'body' => [
                'data' => $temperList,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit
                ]
            ]];

        case 'create':
            if (!isset($data->data)) return ['code' => 400, 'body' => ['message' => 'Data payload required.']];
            
            $payload = (array)$data->data;
            if (!isset($payload['name'])) return ['code' => 400, 'body' => ['message' => 'Temper name required.']];
            
            $temperName = $payload['name'];
            $isActive = isset($payload['is_active']) ? $payload['is_active'] : 1;
            
            // Create Temper
            $temperId = $models['Temper']->create($temperName); // Assuming create accepts name, or we need to standardized it?

            $supportedPhones = $payload['supportedPhones'];

            $supportedPhones[] = $temperName;
            foreach ($supportedPhones as $phone) {
                $models['SupportedPhone']->create($temperId, $phone);
            }
            if (!$temperId) return ['code' => 500, 'body' => ['message' => 'Failed to create Temper.']];
            
            // Update is_active if false (default is 1 in model)
            if (!$isActive) {
                 $models['Temper']->update($temperId, ['is_active' => 0]);
            }
            
            return ['code' => 200, 'body' => ['message' => 'Temper created.', 'id' => $temperId]];

        case 'update':
            if (!isset($data->id) || !isset($data->data)) return ['code' => 400, 'body' => ['message' => 'ID and Data required.']];
            
            $id = $data->id;
            $payload = (array)$data->data;
            
            // Update Temper Fields
            $temperData = [];
            if (isset($payload['name'])) $temperData['name'] = $payload['name'];
            if (isset($payload['is_active'])) $temperData['is_active'] = $payload['is_active'];
            
            if (!empty($temperData)) {
                $models['Temper']->update($id, $temperData);
            }
            
            // Update Phones
            // Full replacement approach is easiest: delete all for ID, re-insert.
            // "delete where temper_id = id"
            $sqlManager->delete('supported_phones', ['temper_id' => $id]);
            
            $temperName = isset($payload['name']) ? $payload['name'] : $models['Temper']->getById($id)['name'];
            
            $phoneList = isset($payload['supportedPhones']) ? $payload['supportedPhones'] : [];
             if (!in_array($temperName, $phoneList)) {
                $phoneList[] = $temperName;
            }
            
            foreach ($phoneList as $pName) {
                $models['SupportedPhone']->create($id, $pName);
            }
            
            return ['code' => 200, 'body' => ['message' => 'Temper updated.']];

        case 'delete':
            if (!isset($data->id)) return ['code' => 400, 'body' => ['message' => 'ID required.']];
            // Cascade delete handles phones usually if configured in DB schema (ON DELETE CASCADE).
            // My schema has ON DELETE CASCADE for supported_phones.temper_id.
            
            $models['Temper']->delete($data->id);
            return ['code' => 200, 'body' => ['message' => 'Temper deleted.']];

        default:
            return ['code' => 400, 'body' => ['message' => 'Invalid action.']];
    }
}
?>
