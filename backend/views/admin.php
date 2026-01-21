<?php
// backend/views/admin.php

// Helper to filter data keys
function filter_keys($data, $allowed) {
    return array_intersect_key($data, array_flip($allowed));
}

function handle_admin_crud($sqlManager, $models, $data) {
    // 1. Role Validation
    if (!isset($_SESSION['user_id'])) {
        return ['code' => 401, 'body' => ['message' => 'Not authenticated.']];
    }
    
    // We need to fetch the current user's role again to be safe 
    // or rely on session if we stored role? Ideally fetch from DB.
    $currentUser = $models['User']->getById($_SESSION['user_id']);
    if (!$currentUser || !in_array($currentUser['role'], ['admin', 'sales_manager'])) {
        return ['code' => 403, 'body' => ['message' => 'Access denied. Admin or Sales Manager only.']];
    }
    $role = $currentUser['role'];

    // 2. Parse Request
    $modelKey = isset($data->model) ? $data->model : (isset($_GET['model']) ? $_GET['model'] : '');
    $action = isset($data->action) ? $data->action : (isset($_GET['action']) ? $_GET['action'] : 'list');
    
    if (empty($modelKey)) {
        return ['code' => 400, 'body' => ['message' => 'Model required.']];
    }

    // 3. Configuration Dictionary
    // allowed_fields: fields that can be written to
    // search_fields: fields that can be searched via generic OR
    
    $adminConfig = [
        'User' => [
            'allowed_fields' => ['name', 'email', 'phone_number', 'role', 'password', 'is_active', 'deactivated_remarks'],
            'search_fields' => ['name', 'email', 'phone_number'],
            'read_only' => false,
            'model' => 'User'
        ],
        'Subscription' => [
            'allowed_fields' => ['name', 'price', 'number_of_days', 'is_active'],
            'search_fields' => ['name'],
            'read_only' => false,
            'model' => 'Subscription'
        ],
        'UserSubscription' => [
            'allowed_fields' => ['user_id', 'subscription_id', 'start_date', 'end_date', 'is_active'],
            'search_fields' => [], // ID search probably handled via filters
            'read_only' => false,
            'model' => 'UserSubscription'
        ],
        'UserSession' => [
            'allowed_fields' => ['is_active'], // Maybe only allow deactivating?
            'search_fields' => ['device', 'ip'],
            'read_only' => false, // Can delete/update
            'model' => 'UserSession'
        ],
        'UserStock' => [
            'allowed_fields' => ['user_id', 'temper_id', 'available_stock'],
            'search_fields' => [],
            'read_only' => false,
            'model' => 'UserStock'
        ],
        'UserOtp' => [
            'allowed_fields' => [], 
            'search_fields' => ['otp', 'type'],
            'read_only' => true,
            'model' => 'UserOtp' 
        ],
        'UserLog' => [
            'allowed_fields' => [],
            'search_fields' => ['action_name'],
            'read_only' => true,
            'model' => 'UserLog'
        ],
        'LeadForm' => [
            'allowed_fields' => ['name', 'email', 'phone_number', 'subscription_id', 'start_date', 'created_by', 'status'],
            'search_fields' => ['name', 'email', 'phone_number'],
            'read_only' => false,
            'model' => 'LeadForm'
        ]
    ];

    if (!array_key_exists($modelKey, $adminConfig)) {
        return ['code' => 400, 'body' => ['message' => "Invalid model '$modelKey'."]];
    }

    $config = $adminConfig[$modelKey];
    $modelName = $config['model'];
    
    if (!isset($models[$modelName])) {
        return ['code' => 500, 'body' => ['message' => "Model '$modelName' class not initialized."]];
    }
    
    $modelInstance = $models[$modelName];

    // 4. Handle Actions
    switch ($action) {
        case 'list':
            $page = isset($data->page) ? (int)$data->page : (isset($_GET['page']) ? (int)$_GET['page'] : 1);
            $limit = isset($data->limit) ? (int)$data->limit : (isset($_GET['limit']) ? (int)$_GET['limit'] : 10);
            $q = isset($data->q) ? $data->q : (isset($_GET['q']) ? $_GET['q'] : '');
            $offset = ($page - 1) * $limit;
            
            // Extract filters from GET/POST that strictly match DB columns?
            // For now, let's allow precise filtering via 'filters' object in JSON
            $filters = isset($data->filters) ? (array)$data->filters : [];
            
            // Execute
            $result = $modelInstance->getAll($filters, $limit, $offset, $q);
            
            return ['code' => 200, 'body' => [
                'data' => $result['data'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $page,
                    'limit' => $limit
                ]
            ]];

        case 'create':
            if ($config['read_only']) return ['code' => 403, 'body' => ['message' => 'Read only model.']];
            if (!isset($data->data)) return ['code' => 400, 'body' => ['message' => 'Data payload required.']];
            
            $payload = (array)$data->data;
            $cleanData = filter_keys($payload, $config['allowed_fields']);
            
            $newId = $modelInstance->create($cleanData);
            if ($newId) {
                return ['code' => 200, 'body' => ['message' => 'Created successfully.', 'id' => $newId]];
            }
            return ['code' => 500, 'body' => ['message' => 'Create failed.']];

        case 'update':
            if ($config['read_only']) return ['code' => 403, 'body' => ['message' => 'Read only model.']];
            if (!isset($data->id) || !isset($data->data)) return ['code' => 400, 'body' => ['message' => 'ID and Data required.']];
            
            $payload = (array)$data->data;
            if ($modelName === 'User' && empty($payload['password'])) {
               // If User update and password empty, remove from payload so strict filter doesn't keep it as null/empty if the model treats it specially.
               // Actually filter_keys keeps it if allowed. The Model handles empty password check.
            }
            $cleanData = filter_keys($payload, $config['allowed_fields']);
            
            if ($modelInstance->update($data->id, $cleanData)) {
                 return ['code' => 200, 'body' => ['message' => 'Updated successfully.']];
            }
            return ['code' => 500, 'body' => ['message' => 'Update failed.']];

        case 'delete':
            if ($config['read_only']) return ['code' => 403, 'body' => ['message' => 'Read only model.']];
            if (!isset($data->id)) return ['code' => 400, 'body' => ['message' => 'ID required.']];
            
            if ($modelInstance->delete($data->id)) {
                return ['code' => 200, 'body' => ['message' => 'Deleted successfully.']];
            }
            return ['code' => 500, 'body' => ['message' => 'Delete failed.']];

        default:
            return ['code' => 400, 'body' => ['message' => "Invalid action '$action'."]];
    }
}
?>
