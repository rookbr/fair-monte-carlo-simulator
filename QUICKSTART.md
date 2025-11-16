# Quick Start Guide

Get up and running with FAIR Monte Carlo Simulator in 5 minutes!

## Step 1: Run Setup Script

```bash
cd /var/www/html/fair-monte-carlo
./setup.sh
```

The script will:
- Verify PHP and MySQL installation
- Create the database
- Set up tables
- Configure database connection
- Test connectivity

## Step 2: Access the Application

Open your browser and navigate to:
```
http://localhost/fair-monte-carlo/
```

## Step 3: Run Your First Simulation

### Example: Ransomware Risk Assessment

Use these sample parameters:

- **Asset Value**: $5,000,000
- **TEF Min**: 0.5 (threat occurs every 2 years minimum)
- **TEF Max**: 3.0 (up to 3 times per year)
- **Vulnerability**: 30% (30% chance of successful attack)
- **Primary Loss Min**: $50,000 (direct costs)
- **Primary Loss Max**: $2,000,000 (full recovery costs)
- **Secondary Loss Min**: $10,000 (reputation damage)
- **Secondary Loss Max**: $500,000 (regulatory fines)
- **Number of Simulations**: 10,000

### Interpreting Results

After running the simulation, you'll see:

1. **Mean Annual Loss**: Average expected loss per year
2. **Median Annual Loss**: Middle value (50th percentile)
3. **90th/95th/99th Percentile**: Risk at different confidence levels
4. **Histogram**: Visual distribution of possible losses

### What the Numbers Mean

- **Mean = $375,000**: On average, expect $375K in losses annually
- **95th Percentile = $1.2M**: 95% of the time, losses will be under $1.2M
- Use these numbers to:
  - Set insurance coverage
  - Budget for security controls
  - Justify security investments

## Step 4: View History

Click "Simulation History" in the navigation to:
- Review past simulations
- Export results to CSV
- Compare different scenarios
- Delete old simulations

## Common Use Cases

### 1. Security Budget Justification
Run simulations with and without proposed security controls to show ROI.

### 2. Insurance Coverage
Use 95th or 99th percentile to determine appropriate cyber insurance limits.

### 3. Risk Appetite
Compare different risk scenarios to set organizational risk thresholds.

### 4. Regulatory Compliance
Document risk analysis for compliance frameworks (ISO 27001, NIST, etc.)

## Troubleshooting

### Can't access the page?
```bash
# Restart Apache
sudo systemctl restart apache2

# Check Apache is running
sudo systemctl status apache2
```

### Database connection errors?
```bash
# Check MySQL is running
sudo systemctl status mysql

# Verify credentials in config.php
cat config.php
```

### Simulation taking too long?
- Reduce number of iterations (1,000 is faster, 10,000 is more accurate)
- Check PHP max_execution_time in php.ini
- Monitor server resources

## Tips for Accurate Simulations

1. **Use Realistic Ranges**: Base TEF and loss magnitudes on industry data
2. **Run Multiple Scenarios**: Test best case, worst case, and realistic
3. **Iterate**: Start with 1,000 iterations for testing, use 10,000+ for final
4. **Document Assumptions**: Use the notes field to record your reasoning
5. **Tag Simulations**: Use tags like "ransomware", "data-breach" for organization

## Next Steps

- Read the full README.md for API documentation
- Customize the interface (edit assets/css/style.css)
- Integrate with your threat intelligence platform
- Export results for executive reporting

## Need Help?

- Check README.md for detailed documentation
- Review the FAIR methodology: https://www.fairinstitute.org/
- Check server logs for errors

---

**Ready to quantify your cyber risk!**
