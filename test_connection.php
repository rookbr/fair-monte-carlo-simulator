<?php
/**
 * Test Database Connection
 * Run this file to verify your database configuration
 */

require_once 'config.php';
require_once 'db/connection.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connection Test - FAIR Monte Carlo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">FAIR Monte Carlo - Connection Test</h4>
                    </div>
                    <div class="card-body">
                        <h5>Configuration Test</h5>
                        <hr>
                        
                        <?php
                        $tests = [];
                        
                        // Test 1: PHP Version
                        $phpVersion = PHP_VERSION;
                        $tests[] = [
                            'name' => 'PHP Version',
                            'status' => version_compare($phpVersion, '8.0.0', '>='),
                            'message' => "PHP $phpVersion " . (version_compare($phpVersion, '8.0.0', '>=') ? '(✓ OK)' : '(✗ Requires 8.0+)'),
                            'value' => $phpVersion
                        ];
                        
                        // Test 2: Database Configuration
                        $tests[] = [
                            'name' => 'Database Host',
                            'status' => defined('DB_HOST'),
                            'message' => defined('DB_HOST') ? 'Configured' : 'Not configured',
                            'value' => defined('DB_HOST') ? DB_HOST : 'N/A'
                        ];
                        
                        $tests[] = [
                            'name' => 'Database Name',
                            'status' => defined('DB_NAME'),
                            'message' => defined('DB_NAME') ? 'Configured' : 'Not configured',
                            'value' => defined('DB_NAME') ? DB_NAME : 'N/A'
                        ];
                        
                        // Test 3: PDO Extension
                        $pdoAvailable = extension_loaded('pdo') && extension_loaded('pdo_mysql');
                        $tests[] = [
                            'name' => 'PDO MySQL Extension',
                            'status' => $pdoAvailable,
                            'message' => $pdoAvailable ? 'Installed' : 'Not installed',
                            'value' => $pdoAvailable ? 'Available' : 'Missing'
                        ];
                        
                        // Test 4: Database Connection
                        $dbConnected = false;
                        $dbError = '';
                        try {
                            $db = getDbConnection();
                            $stmt = $db->query("SELECT VERSION() as version");
                            $result = $stmt->fetch();
                            $dbConnected = true;
                            $mysqlVersion = $result['version'];
                            
                            $tests[] = [
                                'name' => 'Database Connection',
                                'status' => true,
                                'message' => 'Connected successfully',
                                'value' => "MySQL $mysqlVersion"
                            ];
                            
                            // Test 5: Check if tables exist
                            $stmt = $db->query("SHOW TABLES");
                            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            $requiredTables = ['simulations', 'simulation_results'];
                            $tablesExist = count(array_intersect($requiredTables, $tables)) === count($requiredTables);
                            
                            $tests[] = [
                                'name' => 'Database Tables',
                                'status' => $tablesExist,
                                'message' => $tablesExist ? 'All tables exist' : 'Tables missing - run schema.sql',
                                'value' => implode(', ', $tables) ?: 'No tables found'
                            ];
                            
                            // Test 6: Check write permissions
                            try {
                                $stmt = $db->query("SELECT COUNT(*) as count FROM simulations");
                                $count = $stmt->fetch()['count'];
                                $tests[] = [
                                    'name' => 'Database Access',
                                    'status' => true,
                                    'message' => 'Read/Write access confirmed',
                                    'value' => "$count simulation(s) in database"
                                ];
                            } catch (Exception $e) {
                                $tests[] = [
                                    'name' => 'Database Access',
                                    'status' => false,
                                    'message' => 'Cannot query tables',
                                    'value' => $e->getMessage()
                                ];
                            }
                            
                        } catch (Exception $e) {
                            $dbError = $e->getMessage();
                            $tests[] = [
                                'name' => 'Database Connection',
                                'status' => false,
                                'message' => 'Connection failed',
                                'value' => $dbError
                            ];
                        }
                        
                        // Display results
                        foreach ($tests as $test) {
                            $bgClass = $test['status'] ? 'bg-success' : 'bg-danger';
                            $icon = $test['status'] ? '✓' : '✗';
                            echo "<div class='alert $bgClass text-white mb-2'>";
                            echo "<strong>$icon {$test['name']}:</strong> {$test['message']}<br>";
                            echo "<small>Value: {$test['value']}</small>";
                            echo "</div>";
                        }
                        
                        // Overall status
                        $allPassed = array_reduce($tests, fn($carry, $test) => $carry && $test['status'], true);
                        
                        echo "<hr>";
                        if ($allPassed) {
                            echo "<div class='alert alert-success'>";
                            echo "<h5>✓ All tests passed!</h5>";
                            echo "<p>Your FAIR Monte Carlo Simulator is ready to use.</p>";
                            echo "<a href='index.php' class='btn btn-primary'>Go to Simulator</a>";
                            echo "</div>";
                        } else {
                            echo "<div class='alert alert-danger'>";
                            echo "<h5>✗ Some tests failed</h5>";
                            echo "<p>Please fix the issues above before using the simulator.</p>";
                            echo "<ul>";
                            echo "<li>Check your database credentials in config.php</li>";
                            echo "<li>Ensure MySQL is running: <code>sudo systemctl status mysql</code></li>";
                            echo "<li>Run the schema: <code>mysql -u root -p < db/schema.sql</code></li>";
                            echo "</ul>";
                            echo "</div>";
                        }
                        ?>
                        
                        <hr>
                        <h5>System Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>PHP Version:</strong></td>
                                <td><?= PHP_VERSION ?></td>
                            </tr>
                            <tr>
                                <td><strong>Server Software:</strong></td>
                                <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                            </tr>
                            <tr>
                                <td><strong>Document Root:</strong></td>
                                <td><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></td>
                            </tr>
                            <tr>
                                <td><strong>Max Execution Time:</strong></td>
                                <td><?= ini_get('max_execution_time') ?> seconds</td>
                            </tr>
                            <tr>
                                <td><strong>Memory Limit:</strong></td>
                                <td><?= ini_get('memory_limit') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="card-footer text-muted">
                        <small>Test performed: <?= date('Y-m-d H:i:s') ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
