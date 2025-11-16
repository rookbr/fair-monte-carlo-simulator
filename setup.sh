#!/bin/bash

# FAIR Monte Carlo Simulator - Setup Script
# This script helps set up the database and configuration

echo "======================================"
echo "FAIR Monte Carlo Simulator - Setup"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}Error: MySQL is not installed.${NC}"
    echo "Please install MySQL 8.x first."
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP is not installed.${NC}"
    echo "Please install PHP 8.x first."
    exit 1
fi

echo -e "${GREEN}✓ MySQL found: $(mysql --version)${NC}"
echo -e "${GREEN}✓ PHP found: $(php --version | head -n 1)${NC}"
echo ""

# Get database credentials
echo "Please enter your MySQL database credentials:"
read -p "MySQL Host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "MySQL Username [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "MySQL Password: " DB_PASS
echo ""

read -p "Database Name [fair_monte_carlo]: " DB_NAME
DB_NAME=${DB_NAME:-fair_monte_carlo}

echo ""
echo "Creating database and tables..."

# Create database and import schema
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE $DB_NAME;
SOURCE db/schema.sql;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database created successfully${NC}"
else
    echo -e "${RED}✗ Error creating database${NC}"
    exit 1
fi

# Create config.php with credentials
echo ""
echo "Updating configuration file..."

cat > config.php << EOF
<?php
/**
 * Configuration file for FAIR Monte Carlo Simulator
 */

// Database configuration
define('DB_HOST', '$DB_HOST');
define('DB_NAME', '$DB_NAME');
define('DB_USER', '$DB_USER');
define('DB_PASS', '$DB_PASS');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('DEFAULT_SIMULATIONS', 10000);
define('MAX_SIMULATIONS', 100000);
define('MIN_SIMULATIONS', 1000);

// Pagination
define('RESULTS_PER_PAGE', 20);

// Timezone
date_default_timezone_set('UTC');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
EOF

echo -e "${GREEN}✓ Configuration updated${NC}"

# Set proper permissions
echo ""
echo "Setting file permissions..."

if [ -w "." ]; then
    chmod 755 *.php
    chmod 755 api/*.php
    chmod 755 db/*.php
    chmod 644 assets/css/*.css
    chmod 644 assets/js/*.js
    echo -e "${GREEN}✓ Permissions set${NC}"
else
    echo -e "${YELLOW}⚠ Warning: Cannot set permissions. You may need to run with sudo.${NC}"
fi

# Test database connection
echo ""
echo "Testing database connection..."

php -r "
require 'db/connection.php';
try {
    \$db = getDbConnection();
    echo 'Connection successful!' . PHP_EOL;
    exit(0);
} catch (Exception \$e) {
    echo 'Connection failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database connection successful${NC}"
else
    echo -e "${RED}✗ Database connection failed${NC}"
    echo "Please check your credentials in config.php"
    exit 1
fi

echo ""
echo "======================================"
echo -e "${GREEN}Setup completed successfully!${NC}"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Ensure Apache mod_rewrite is enabled"
echo "2. Navigate to http://localhost/fair-monte-carlo/"
echo "3. Run your first simulation"
echo ""
echo "For detailed instructions, see README.md"
echo ""
