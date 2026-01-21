<?php
// backend/views/leads.php

function handle_leads_crud($sqlManager, $models, $data) {
    // 1. Auth Validation
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
            
            // Use standard LeadForm model getAll/query logic
            $result = $models['LeadForm']->getAll([], $limit, $offset, $q);
            
            // Optional: Enrich with Subscription Name or User Name (Creator)?
            // For now standard is fine.
            
            return ['code' => 200, 'body' => [
                'data' => $result['data'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $page,
                    'limit' => $limit
                ]
            ]];

        case 'create':
            if (!isset($data->data)) return ['code' => 400, 'body' => ['message' => 'Data payload required.']];
            
            $payload = (array)$data->data;
            
            // Mandatory Fields
            if (empty($payload['name']) || empty($payload['email'])) {
                return ['code' => 400, 'body' => ['message' => 'Name and Email are required.']];
            }
            
            $email = $payload['email'];
            
            // 1. Check if email exists
            // Manual query as LeadForm model doesn't have specific check method exposed yet via simple API
            // We can use SqlManager
            $exists = $sqlManager->query("SELECT id FROM lead_form WHERE email = :email LIMIT 1", ['email' => $email]);
            if (!empty($exists)) {
                return ['code' => 400, 'body' => ['message' => 'Email already exists.']];
            }
            
            // 2. Prepare Data
            $insertData = [
                'name' => $payload['name'],
                'email' => $email,
                'phone_number' => isset($payload['phone_number']) ? $payload['phone_number'] : null,
                'subscription_id' => isset($payload['subscription_id']) ? $payload['subscription_id'] : null,
                'start_date' => isset($payload['start_date']) ? $payload['start_date'] : null,
                'created_by' => $_SESSION['user_id'],
                'status' => 'pending'
            ];
            
            $id = $models['LeadForm']->create($insertData);
            
            if ($id) {
                return ['code' => 200, 'body' => ['message' => 'Lead created successfully.', 'id' => $id]];
            }
            return ['code' => 500, 'body' => ['message' => 'Failed to create lead.']];

        case 'update':
            // "once saved cant be edited"
            return ['code' => 403, 'body' => ['message' => 'Leads cannot be edited.']];

        case 'process':
            // Approve or Reject
            if (!isset($data->id) || !isset($data->status)) {
                return ['code' => 400, 'body' => ['message' => 'ID and Status required.']];
            }
            
            $status = $data->status;
            if (!in_array($status, ['approved', 'rejected'])) {
                 return ['code' => 400, 'body' => ['message' => 'Invalid status. Must be approved or rejected.']];
            }
            
            $leadId = $data->id;
            
            // Fetch Lead via Query since LeadForm model->getById not explicit? 
            // Standard getAll filter is robust.
            $leadRes = $models['LeadForm']->getAll(['id' => $leadId], 1);
            if ($leadRes['total'] == 0) {
                 return ['code' => 404, 'body' => ['message' => 'Lead not found.']];
            }
            $lead = $leadRes['data'][0];
            
            if ($lead['status'] !== 'pending') {
                 // return ['code' => 400, 'body' => ['message' => 'Lead already processed.']];
                 // sales manager might want to change decision? 
                 // Allow re-decision? Usually safe to block if 'approved' involves side effects.
                 if ($lead['status'] == 'approved') {
                      return ['code' => 400, 'body' => ['message' => 'Lead already approved.']];
                 }
            }
            
            if ($status === 'rejected') {
                 $models['LeadForm']->update($leadId, ['status' => 'rejected']);
                 return ['code' => 200, 'body' => ['message' => 'Lead rejected.']];
            }
            
            // status === 'approved'
            
            // 1. Create/Get User
            $email = $lead['email'];
            $existingUser = $models['User']->getAll(['email' => $email]); // Standard getAll returns ['data'=>[], 'total'=>N]
            
            $userId = null;
            $password = null;
            $isNewUser = false;
            
            if ($existingUser['total'] > 0) {
                $user = $existingUser['data'][0];
                $userId = $user['id'];
                // Don't change password for existing user, just link subscription
            } else {
                // Create New User
                $password = bin2hex(random_bytes(4)); // Random 8 char pass
                // Default role 'user' is set by Model
                $payload = [
                    'name' => $lead['name'],
                    'email' => $email,
                    'password' => $password, 
                    'phone_number' => $lead['phone_number'],
                    'is_active' => 1
                ];
                $userId = $models['User']->create($payload);
                $isNewUser = true;
                if (!$userId) return ['code' => 500, 'body' => ['message' => 'Failed to create user.']];
            }
            
            // 2. Create Subscription
            if ($lead['subscription_id']) {
                $subPlan = $models['Subscription']->getById($lead['subscription_id']);
                if ($subPlan) {
                    $days = $subPlan['number_of_days'];
                    $startDate = $lead['start_date'] ? $lead['start_date'] : date('Y-m-d H:i:s');
                    $endDate = date('Y-m-d H:i:s', strtotime($startDate . " + $days days"));
                    
                    $subData = [
                        'user_id' => $userId,
                        'subscription_id' => $subPlan['id'],
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'approved_at' => date('Y-m-d H:i:s'),
                        'is_active' => 1
                    ];
                    $models['UserSubscription']->create($subData);
                }
            }
            
            // 3. Send Email
            if ($isNewUser) {
                EmailHelper::sendWelcomeEmail($email, $lead['name'], $password);
            } else {
                 // Maybe send "Subscription Activated" email? Not requested but good UX.
                 // send_subscription_active_email($email, ...);
            }
            
            // 4. Update Lead Status
            $models['LeadForm']->update($leadId, ['status' => 'approved']);
            
            return ['code' => 200, 'body' => ['message' => 'Lead approved and processed.']];

        case 'delete':
            // Allow Admin to delete? Prompt imply strict sales manager workflow. 
            // I'll allow Admin role to delete, block Sales Manager.
            if ($currentUser['role'] !== 'admin') {
                return ['code' => 403, 'body' => ['message' => 'Access denied. Only Admin can delete leads.']];
            }
            
            if (!isset($data->id)) return ['code' => 400, 'body' => ['message' => 'ID required.']];
            $models['LeadForm']->delete($data->id);
            
            return ['code' => 200, 'body' => ['message' => 'Lead deleted.']];

        default:
            return ['code' => 400, 'body' => ['message' => 'Invalid action.']];
    }
}
?>
