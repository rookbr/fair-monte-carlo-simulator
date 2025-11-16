<?php
/**
 * Run Monte Carlo Simulation API Endpoint
 * Receives FAIR parameters, runs simulation, saves to database, returns results
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../config.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate inputs
    $assetValue = floatval($input['asset_value'] ?? 0);
    $tefMin = floatval($input['tef_min'] ?? 0);
    $tefMax = floatval($input['tef_max'] ?? 0);
    $vulnerabilityPercent = floatval($input['vulnerability_percent'] ?? 0);
    $primaryLossMin = floatval($input['primary_loss_min'] ?? 0);
    $primaryLossMax = floatval($input['primary_loss_max'] ?? 0);
    $secondaryLossMin = floatval($input['secondary_loss_min'] ?? 0);
    $secondaryLossMax = floatval($input['secondary_loss_max'] ?? 0);
    $numIterations = intval($input['num_iterations'] ?? DEFAULT_SIMULATIONS);
    $notes = $input['notes'] ?? '';
    $tags = $input['tags'] ?? '';
    
    // Validation
    if ($assetValue <= 0) {
        throw new Exception('Asset value must be greater than 0');
    }
    if ($tefMin < 0 || $tefMax < 0 || $tefMin > $tefMax) {
        throw new Exception('Invalid Threat Event Frequency range');
    }
    if ($vulnerabilityPercent < 0 || $vulnerabilityPercent > 100) {
        throw new Exception('Vulnerability must be between 0 and 100');
    }
    if ($primaryLossMin < 0 || $primaryLossMax < 0 || $primaryLossMin > $primaryLossMax) {
        throw new Exception('Invalid Primary Loss range');
    }
    if ($secondaryLossMin < 0 || $secondaryLossMax < 0 || $secondaryLossMin > $secondaryLossMax) {
        throw new Exception('Invalid Secondary Loss range');
    }
    if ($numIterations < MIN_SIMULATIONS || $numIterations > MAX_SIMULATIONS) {
        throw new Exception('Number of iterations must be between ' . MIN_SIMULATIONS . ' and ' . MAX_SIMULATIONS);
    }
    
    // Run Monte Carlo simulation
    $results = runMonteCarloSimulation(
        $tefMin,
        $tefMax,
        $vulnerabilityPercent / 100,
        $primaryLossMin,
        $primaryLossMax,
        $secondaryLossMin,
        $secondaryLossMax,
        $numIterations
    );
    
    // Calculate statistics
    $stats = calculateStatistics($results);
    
    // Save to database
    $db = getDbConnection();
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Insert simulation summary
        $stmt = $db->prepare("
            INSERT INTO simulations (
                asset_value, tef_min, tef_max, vulnerability_percent,
                primary_loss_min, primary_loss_max, secondary_loss_min, secondary_loss_max,
                num_iterations, mean_loss, median_loss, min_loss, max_loss,
                percentile_90, percentile_95, percentile_99, notes, tags
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $assetValue,
            $tefMin,
            $tefMax,
            $vulnerabilityPercent,
            $primaryLossMin,
            $primaryLossMax,
            $secondaryLossMin,
            $secondaryLossMax,
            $numIterations,
            $stats['mean'],
            $stats['median'],
            $stats['min'],
            $stats['max'],
            $stats['percentile_90'],
            $stats['percentile_95'],
            $stats['percentile_99'],
            $notes,
            $tags
        ]);
        
        $simulationId = $db->lastInsertId();
        
        // Insert individual results (batch insert for performance)
        $batchSize = 1000;
        $batches = array_chunk($results, $batchSize);
        
        foreach ($batches as $batch) {
            $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?, ?),', count($batch)), ',');
            $stmt = $db->prepare("
                INSERT INTO simulation_results (
                    simulation_id, iteration_number, tef_value, vulnerability_value,
                    loss_magnitude, annual_loss
                ) VALUES $placeholders
            ");
            
            $values = [];
            foreach ($batch as $result) {
                $values[] = $simulationId;
                $values[] = $result['iteration'];
                $values[] = $result['tef'];
                $values[] = $result['vulnerability'];
                $values[] = $result['loss_magnitude'];
                $values[] = $result['annual_loss'];
            }
            
            $stmt->execute($values);
        }
        
        $db->commit();
        
        // Prepare histogram data
        $histogram = createHistogram($results, 50);
        
        // Return response
        echo json_encode([
            'success' => true,
            'simulation_id' => $simulationId,
            'statistics' => $stats,
            'histogram' => $histogram,
            'num_iterations' => $numIterations
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Run Monte Carlo simulation
 */
function runMonteCarloSimulation($tefMin, $tefMax, $vulnerability, $primaryMin, $primaryMax, $secondaryMin, $secondaryMax, $iterations) {
    $results = [];
    
    for ($i = 0; $i < $iterations; $i++) {
        // Generate random TEF (Threat Event Frequency)
        $tef = randomFloat($tefMin, $tefMax);
        
        // Generate random vulnerability (0 to 1, but use the input percentage)
        $vulnValue = randomFloat(0, $vulnerability);
        
        // Generate random loss magnitude (primary + secondary)
        $primaryLoss = randomFloat($primaryMin, $primaryMax);
        $secondaryLoss = randomFloat($secondaryMin, $secondaryMax);
        $totalLossMagnitude = $primaryLoss + $secondaryLoss;
        
        // Calculate annual loss: TEF * Vulnerability * Loss Magnitude
        $annualLoss = $tef * $vulnValue * $totalLossMagnitude;
        
        $results[] = [
            'iteration' => $i + 1,
            'tef' => $tef,
            'vulnerability' => $vulnValue,
            'loss_magnitude' => $totalLossMagnitude,
            'annual_loss' => $annualLoss
        ];
    }
    
    return $results;
}

/**
 * Generate random float between min and max
 */
function randomFloat($min, $max) {
    return $min + (random_int(0, PHP_INT_MAX) / PHP_INT_MAX) * ($max - $min);
}

/**
 * Calculate statistics from results
 */
function calculateStatistics($results) {
    $losses = array_column($results, 'annual_loss');
    sort($losses, SORT_NUMERIC);
    
    $count = count($losses);
    $sum = array_sum($losses);
    
    return [
        'mean' => $sum / $count,
        'median' => calculatePercentile($losses, 50),
        'min' => $losses[0],
        'max' => $losses[$count - 1],
        'percentile_90' => calculatePercentile($losses, 90),
        'percentile_95' => calculatePercentile($losses, 95),
        'percentile_99' => calculatePercentile($losses, 99)
    ];
}

/**
 * Calculate percentile from sorted array
 */
function calculatePercentile($sortedArray, $percentile) {
    $count = count($sortedArray);
    $index = ($percentile / 100) * ($count - 1);
    
    if (floor($index) == $index) {
        return $sortedArray[$index];
    }
    
    $lower = $sortedArray[floor($index)];
    $upper = $sortedArray[ceil($index)];
    $fraction = $index - floor($index);
    
    return $lower + ($upper - $lower) * $fraction;
}

/**
 * Create histogram data for charting
 */
function createHistogram($results, $numBins) {
    $losses = array_column($results, 'annual_loss');
    $min = min($losses);
    $max = max($losses);
    $binWidth = ($max - $min) / $numBins;
    
    $bins = array_fill(0, $numBins, 0);
    $binLabels = [];
    
    // Create bin labels
    for ($i = 0; $i < $numBins; $i++) {
        $binStart = $min + ($i * $binWidth);
        $binEnd = $binStart + $binWidth;
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
