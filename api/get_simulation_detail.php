<?php
/**
 * Get Simulation Detail API Endpoint
 * Returns full details of a specific simulation including histogram data
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
    $simulationId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($simulationId <= 0) {
        throw new Exception('Invalid simulation ID');
    }
    
    $db = getDbConnection();
    
    // Get simulation summary
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
        WHERE id = ?
    ");
    
    $stmt->execute([$simulationId]);
    $simulation = $stmt->fetch();
    
    if (!$simulation) {
        http_response_code(404);
        throw new Exception('Simulation not found');
    }
    
    // Format numeric values
    $simulation['asset_value'] = floatval($simulation['asset_value']);
    $simulation['tef_min'] = floatval($simulation['tef_min']);
    $simulation['tef_max'] = floatval($simulation['tef_max']);
    $simulation['vulnerability_percent'] = floatval($simulation['vulnerability_percent']);
    $simulation['primary_loss_min'] = floatval($simulation['primary_loss_min']);
    $simulation['primary_loss_max'] = floatval($simulation['primary_loss_max']);
    $simulation['secondary_loss_min'] = floatval($simulation['secondary_loss_min']);
    $simulation['secondary_loss_max'] = floatval($simulation['secondary_loss_max']);
    $simulation['num_iterations'] = intval($simulation['num_iterations']);
    $simulation['mean_loss'] = floatval($simulation['mean_loss']);
    $simulation['median_loss'] = floatval($simulation['median_loss']);
    $simulation['min_loss'] = floatval($simulation['min_loss']);
    $simulation['max_loss'] = floatval($simulation['max_loss']);
    $simulation['percentile_90'] = floatval($simulation['percentile_90']);
    $simulation['percentile_95'] = floatval($simulation['percentile_95']);
    $simulation['percentile_99'] = floatval($simulation['percentile_99']);
    
    // Get simulation results for histogram
    $stmt = $db->prepare("
        SELECT annual_loss
        FROM simulation_results
        WHERE simulation_id = ?
        ORDER BY annual_loss
    ");
    
    $stmt->execute([$simulationId]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Create histogram
    $histogram = createHistogram($results, 50);
    
    echo json_encode([
        'success' => true,
        'simulation' => $simulation,
        'histogram' => $histogram
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Create histogram data for charting
 */
function createHistogram($losses, $numBins) {
    if (empty($losses)) {
        return ['labels' => [], 'data' => []];
    }
    
    $min = min($losses);
    $max = max($losses);
    $binWidth = ($max - $min) / $numBins;
    
    if ($binWidth == 0) {
        return [
            'labels' => [number_format($min, 0)],
            'data' => [count($losses)]
        ];
    }
    
    $bins = array_fill(0, $numBins, 0);
    $binLabels = [];
    
    // Create bin labels
    for ($i = 0; $i < $numBins; $i++) {
        $binStart = $min + ($i * $binWidth);
        $binLabels[] = number_format($binStart, 0);
    }
    
    // Count values in each bin
    foreach ($losses as $loss) {
        $binIndex = min(floor(($loss - $min) / $binWidth), $numBins - 1);
        $bins[$binIndex]++;
    }
    
    return [
        'labels' => $binLabels,
        'data' => $bins
    ];
}
