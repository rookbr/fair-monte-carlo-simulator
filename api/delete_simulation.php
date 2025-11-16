<?php
/**
 * Delete Simulation API Endpoint
 * Deletes a simulation and its results
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db/connection.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept DELETE or POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $simulationId = $input['id'] ?? intval($_GET['id'] ?? 0);
    
    if ($simulationId <= 0) {
        throw new Exception('Invalid simulation ID');
    }
    
    $db = getDbConnection();
    
    // Delete simulation (cascade will delete results)
    $stmt = $db->prepare("DELETE FROM simulations WHERE id = ?");
    $stmt->execute([$simulationId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        throw new Exception('Simulation not found');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Simulation deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
