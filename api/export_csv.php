<?php
/**
 * Export Simulation to CSV
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $simulationId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($simulationId <= 0) {
        throw new Exception('Invalid simulation ID');
    }
    
    $db = getDbConnection();
    
    // Get simulation data
    $stmt = $db->prepare("SELECT * FROM simulations WHERE id = ?");
    $stmt->execute([$simulationId]);
    $simulation = $stmt->fetch();
    
    if (!$simulation) {
        throw new Exception('Simulation not found');
    }
    
    // Get results
    $stmt = $db->prepare("
        SELECT iteration_number, tef_value, vulnerability_value, loss_magnitude, annual_loss
        FROM simulation_results
        WHERE simulation_id = ?
        ORDER BY iteration_number
    ");
    $stmt->execute([$simulationId]);
    $results = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="simulation_' . $simulationId . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write simulation parameters
    fputcsv($output, ['Simulation ID', $simulation['id']]);
    fputcsv($output, ['Created At', $simulation['created_at']]);
    fputcsv($output, ['Asset Value', $simulation['asset_value']]);
    fputcsv($output, ['TEF Min', $simulation['tef_min']]);
    fputcsv($output, ['TEF Max', $simulation['tef_max']]);
    fputcsv($output, ['Vulnerability %', $simulation['vulnerability_percent']]);
    fputcsv($output, ['Primary Loss Min', $simulation['primary_loss_min']]);
    fputcsv($output, ['Primary Loss Max', $simulation['primary_loss_max']]);
    fputcsv($output, ['Secondary Loss Min', $simulation['secondary_loss_min']]);
    fputcsv($output, ['Secondary Loss Max', $simulation['secondary_loss_max']]);
    fputcsv($output, ['Iterations', $simulation['num_iterations']]);
    fputcsv($output, []);
    
    // Write statistics
    fputcsv($output, ['Statistics']);
    fputcsv($output, ['Mean Loss', $simulation['mean_loss']]);
    fputcsv($output, ['Median Loss', $simulation['median_loss']]);
    fputcsv($output, ['Min Loss', $simulation['min_loss']]);
    fputcsv($output, ['Max Loss', $simulation['max_loss']]);
    fputcsv($output, ['90th Percentile', $simulation['percentile_90']]);
    fputcsv($output, ['95th Percentile', $simulation['percentile_95']]);
    fputcsv($output, ['99th Percentile', $simulation['percentile_99']]);
    fputcsv($output, []);
    
    // Write results header
    fputcsv($output, ['Iteration', 'TEF Value', 'Vulnerability Value', 'Loss Magnitude', 'Annual Loss']);
    
    // Write results
    foreach ($results as $result) {
        fputcsv($output, [
            $result['iteration_number'],
            $result['tef_value'],
            $result['vulnerability_value'],
            $result['loss_magnitude'],
            $result['annual_loss']
        ]);
    }
    
    fclose($output);
    exit();
    
} catch (Exception $e) {
    http_response_code(404);
    echo 'Error: ' . $e->getMessage();
}
