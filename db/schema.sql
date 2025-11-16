-- FAIR Monte Carlo Simulator Database Schema
-- MySQL 8.x

CREATE DATABASE IF NOT EXISTS fair_monte_carlo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE fair_monte_carlo;

-- Simulations table: stores summary data and input parameters
CREATE TABLE IF NOT EXISTS simulations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_value DECIMAL(15, 2) NOT NULL,
    tef_min DECIMAL(10, 4) NOT NULL,
    tef_max DECIMAL(10, 4) NOT NULL,
    vulnerability_percent DECIMAL(5, 2) NOT NULL,
    primary_loss_min DECIMAL(15, 2) NOT NULL,
    primary_loss_max DECIMAL(15, 2) NOT NULL,
    secondary_loss_min DECIMAL(15, 2) NOT NULL DEFAULT 0,
    secondary_loss_max DECIMAL(15, 2) NOT NULL DEFAULT 0,
    num_iterations INT NOT NULL,
    mean_loss DECIMAL(15, 2),
    median_loss DECIMAL(15, 2),
    min_loss DECIMAL(15, 2),
    max_loss DECIMAL(15, 2),
    percentile_90 DECIMAL(15, 2),
    percentile_95 DECIMAL(15, 2),
    percentile_99 DECIMAL(15, 2),
    notes TEXT,
    tags VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_asset_value (asset_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Simulation results table: stores individual iteration results for detailed analysis
CREATE TABLE IF NOT EXISTS simulation_results (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    simulation_id INT NOT NULL,
    iteration_number INT NOT NULL,
    tef_value DECIMAL(10, 4) NOT NULL,
    vulnerability_value DECIMAL(5, 4) NOT NULL,
    loss_magnitude DECIMAL(15, 2) NOT NULL,
    annual_loss DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (simulation_id) REFERENCES simulations(id) ON DELETE CASCADE,
    INDEX idx_simulation_id (simulation_id),
    INDEX idx_annual_loss (annual_loss)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create a view for easy querying of simulation summaries
CREATE OR REPLACE VIEW simulation_summary AS
SELECT 
    s.id,
    s.asset_value,
    s.tef_min,
    s.tef_max,
    s.vulnerability_percent,
    s.primary_loss_min,
    s.primary_loss_max,
    s.mean_loss,
    s.median_loss,
    s.percentile_90,
    s.percentile_95,
    s.created_at,
    s.notes,
    s.tags,
    COUNT(sr.id) as result_count
FROM simulations s
LEFT JOIN simulation_results sr ON s.id = sr.simulation_id
GROUP BY s.id;
