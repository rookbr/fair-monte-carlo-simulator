# FAIR Risk Analysis Monte Carlo Simulator

A comprehensive web-based Monte Carlo simulation tool for FAIR (Factor Analysis of Information Risk) risk analysis. This application helps security professionals quantify cyber risk using probabilistic models.

## Features

### Core Functionality
- **Monte Carlo Simulation**: Run 1,000 to 100,000 iterations for statistical accuracy
- **FAIR Methodology**: Calculate risk using Threat Event Frequency (TEF), Vulnerability, and Loss Magnitude
- **Real-time Results**: Interactive charts and statistical analysis
- **Historical Tracking**: Save and review past simulations
- **Comparison Tool**: Compare up to 3 simulations side-by-side
- **CSV Export**: Download detailed results for external analysis

### Technical Features
- **Responsive Design**: Mobile-friendly Bootstrap 5 interface
- **RESTful API**: Clean JSON API endpoints
- **Security**: PDO prepared statements, input validation, CSRF protection
- **Performance**: Batch database inserts, efficient queries
- **Visualization**: Interactive Chart.js histograms

## Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL 8.x
- **Frontend**: HTML5, Vanilla JavaScript, Bootstrap 5, Chart.js
- **Server**: Apache with mod_rewrite

## Installation

### Prerequisites
- Apache web server with mod_rewrite enabled
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Composer (optional, for dependencies)

### Step 1: Database Setup

1. Create the database and tables:
```bash
mysql -u root -p < db/schema.sql
```

Or manually:
```sql
CREATE DATABASE fair_monte_carlo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then run the schema from `db/schema.sql`.

### Step 2: Configure Database Connection

Edit `config.php` or set environment variables:

```php
// Option 1: Edit config.php directly
define('DB_HOST', 'localhost');
define('DB_NAME', 'fair_monte_carlo');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// Option 2: Use environment variables
export DB_HOST=localhost
export DB_NAME=fair_monte_carlo
export DB_USER=your_username
export DB_PASS=your_password
```

### Step 3: Set Permissions

Ensure the web server has read access to all files:
```bash
sudo chown -R www-data:www-data /var/www/html/fair-monte-carlo
sudo chmod -R 755 /var/www/html/fair-monte-carlo
```

### Step 4: Apache Configuration

Ensure mod_rewrite is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Add to your Apache VirtualHost configuration:
```apache
<Directory /var/www/html/fair-monte-carlo>
    AllowOverride All
    Require all granted
</Directory>
```

### Step 5: Access the Application

Navigate to: `http://localhost/fair-monte-carlo/`

## Usage Guide

### Running a Simulation

1. **Navigate to the main page** (index.php)
2. **Enter simulation parameters**:
   - **Asset Value**: Total value of the asset at risk
   - **TEF Min/Max**: Threat Event Frequency (events per year)
   - **Vulnerability**: Probability of successful exploitation (0-100%)
   - **Primary Loss Min/Max**: Direct financial impact per event
   - **Secondary Loss Min/Max**: Indirect costs (reputation, fines, etc.)
   - **Number of Simulations**: 1,000 to 100,000 iterations
3. **Click "Run Simulation"**
4. **View results**:
   - Histogram showing loss distribution
   - Statistical measures (mean, median, percentiles)
   - Export option to CSV

### Understanding FAIR Terms

- **TEF (Threat Event Frequency)**: How often threat events occur per year
- **Vulnerability**: Probability that a threat will successfully exploit a weakness
- **Loss Magnitude**: Financial impact when an event occurs (Primary + Secondary)
- **Annual Loss Exposure (ALE)**: TEF × Vulnerability × Loss Magnitude

### Viewing History

1. **Navigate to History page** (history.php)
2. **Browse past simulations** with key statistics
3. **Click the eye icon** to view detailed results
4. **Export individual simulations** to CSV
5. **Delete old simulations** to clean up database

### Comparing Simulations

1. **On the History page**, select 2-3 simulations using checkboxes
2. **Click "Compare"** button
3. **View side-by-side** histograms and statistics
4. **Analyze differences** in risk profiles

## API Endpoints

### POST `/api/run_simulation.php`
Run a new Monte Carlo simulation.

**Request Body:**
```json
{
  "asset_value": 1000000,
  "tef_min": 0.1,
  "tef_max": 5,
  "vulnerability_percent": 25,
  "primary_loss_min": 10000,
  "primary_loss_max": 500000,
  "secondary_loss_min": 0,
  "secondary_loss_max": 100000,
  "num_iterations": 10000,
  "notes": "Optional notes",
  "tags": "optional, tags"
}
```

**Response:**
```json
{
  "success": true,
  "simulation_id": 123,
  "statistics": {
    "mean": 125000.50,
    "median": 95000.25,
    "min": 0,
    "max": 600000,
    "percentile_90": 250000,
    "percentile_95": 300000,
    "percentile_99": 450000
  },
  "histogram": {
    "labels": ["0", "10000", "20000", ...],
    "data": [150, 320, 450, ...]
  }
}
```

### GET `/api/get_simulations.php`
Retrieve list of simulations with pagination.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Results per page (default: 20)
- `search` (optional): Search in notes/tags

### GET `/api/get_simulation_detail.php?id={id}`
Get detailed information about a specific simulation.

### POST `/api/delete_simulation.php`
Delete a simulation and its results.

### GET `/api/export_csv.php?id={id}`
Export simulation results to CSV file.

## File Structure

```
fair-monte-carlo/
├── api/
│   ├── run_simulation.php       # Main simulation endpoint
│   ├── get_simulations.php      # List simulations
│   ├── get_simulation_detail.php # Get single simulation
│   ├── delete_simulation.php    # Delete simulation
│   └── export_csv.php           # CSV export
├── assets/
│   ├── css/
│   │   └── style.css            # Custom styles
│   └── js/
│       ├── monte-carlo.js       # Main page logic
│       └── history.js           # History page logic
├── db/
│   ├── connection.php           # Database connection
│   └── schema.sql               # Database schema
├── config.php                   # Configuration
├── index.php                    # Main simulation page
├── history.php                  # Simulation history page
├── .htaccess                    # Apache configuration
└── README.md                    # This file
```

## Database Schema

### `simulations` Table
Stores simulation parameters and summary statistics.

### `simulation_results` Table
Stores individual iteration results (foreign key to simulations).

## Security Considerations

1. **SQL Injection Prevention**: All queries use PDO prepared statements
2. **Input Validation**: Server-side validation of all user inputs
3. **XSS Protection**: HTML entities escaped in output
4. **CSRF Protection**: Consider implementing tokens for production
5. **Environment Variables**: Use for sensitive database credentials
6. **Error Handling**: Errors logged, not exposed to users

## Performance Optimization

- **Batch Inserts**: Results inserted in batches of 1,000
- **Database Indexes**: Optimized queries with proper indexes
- **Caching**: Consider implementing Redis/Memcached for production
- **API Rate Limiting**: Add rate limiting for production environments

## Troubleshooting

### Database Connection Errors
- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `config.php`
- Ensure database exists: `SHOW DATABASES;`

### Apache Issues
- Check error log: `tail -f /var/log/apache2/error.log`
- Verify mod_rewrite: `apache2ctl -M | grep rewrite`
- Check file permissions

### Simulation Errors
- Check PHP error log: `tail -f /var/log/php_errors.log`
- Verify input ranges are valid
- Ensure sufficient memory for large simulations

## Future Enhancements

- [ ] User authentication and multi-tenancy
- [ ] Advanced visualizations (box plots, violin plots)
- [ ] Risk appetite thresholds and alerts
- [ ] API rate limiting and authentication
- [ ] Export to PDF reports
- [ ] Integration with threat intelligence feeds
- [ ] Monte Carlo sensitivity analysis
- [ ] Historical trend analysis

## Contributing

Contributions welcome! Please follow these guidelines:
1. Fork the repository
2. Create a feature branch
3. Test thoroughly
4. Submit pull request with description

## License

MIT License - see LICENSE file for details

## Support

For issues, questions, or contributions:
- Create an issue on GitHub
- Email: support@example.com

## Credits

Developed using:
- Bootstrap 5
- Chart.js
- Bootstrap Icons
- PHP & MySQL
- FAIR methodology by The Open Group

---

**Version**: 1.0.0  
**Last Updated**: 2024
