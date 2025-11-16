<?php
/**
 * Get Simulations List API Endpoint
 * Returns paginated list of past simulations
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $db = getDbConnection();
    
    // Get query parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : RESULTS_PER_PAGE;
    $search = $_GET['search'] ?? '';
    $tag = $_GET['tag'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(notes LIKE ? OR tags LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($tag)) {
        $whereConditions[] = "tags LIKE ?";
        $params[] = "%$tag%";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM simulations $whereClause");
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch()['total'];
    
    // Get simulations
    $stmt = $db->prepare("
        SELECT 
            id,
            asset_value,
            tef_min,
            tef_max,
            vulnerability_percent,
            primary_loss_min,
            primary_loss_max,
            secondary_loss_min,
            secondary_loss_max,
            num_iterations,
            mean_loss,
            median_loss,
            min_loss,
            max_loss,
            percentile_90,
            percentile_95,
            percentile_99,
            notes,
            tags,
            created_at
        FROM simulations
        $whereClause
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    
    $simulations = $stmt->fetchAll();
    
    // Format data
    foreach ($simulations as &$sim) {
        $sim['asset_value'] = floatval($sim['asset_value']);
        $sim['tef_min'] = floatval($sim['tef_min']);
        $sim['tef_max'] = floatval($sim['tef_max']);
        $sim['vulnerability_percent'] = floatval($sim['vulnerability_percent']);
        $sim['primary_loss_min'] = floatval($sim['primary_loss_min']);
        $sim['primary_loss_max'] = floatval($sim['primary_loss_max']);
        $sim['secondary_loss_min'] = floatval($sim['secondary_loss_min']);
        $sim['secondary_loss_max'] = floatval($sim['secondary_loss_max']);
        $sim['num_iterations'] = intval($sim['num_iterations']);
        $sim['mean_loss'] = floatval($sim['mean_loss']);
        $sim['median_loss'] = floatval($sim['median_loss']);
        $sim['min_loss'] = floatval($sim['min_loss']);
        $sim['max_loss'] = floatval($sim['max_loss']);
        $sim['percentile_90'] = floatval($sim['percentile_90']);
        $sim['percentile_95'] = floatval($sim['percentile_95']);
        $sim['percentile_99'] = floatval($sim['percentile_99']);
    }
    
    echo json_encode([
        'success' => true,
        'simulations' => $simulations,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => intval($totalCount),
            'pages' => ceil($totalCount / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
