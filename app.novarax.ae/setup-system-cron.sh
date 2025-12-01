#!/bin/bash
# NovaRax System Cron Setup Script
# This will set up a system cron job to trigger WordPress cron every minute

echo "=== NovaRax System Cron Setup ==="
echo ""

# 1. First, disable WordPress WP-Cron (we'll use system cron instead)
echo "Step 1: Disabling WordPress WP-Cron..."
WP_CONFIG="/var/www/app.novarax.ae/wp-config.php"

# Check if DISABLE_WP_CRON is already set
if grep -q "DISABLE_WP_CRON" "$WP_CONFIG"; then
    echo "✓ DISABLE_WP_CRON already configured in wp-config.php"
else
    # Add before the "That's all, stop editing!" line
    sed -i "/\/\* That's all, stop editing/i define('DISABLE_WP_CRON', true); // Use system cron instead" "$WP_CONFIG"
    echo "✓ Added DISABLE_WP_CRON to wp-config.php"
fi

# 2. Create cron job entry
echo ""
echo "Step 2: Setting up system cron job..."

# Create a temporary cron file
TEMP_CRON="/tmp/novarax_cron_temp"
crontab -l > "$TEMP_CRON" 2>/dev/null || echo "# NovaRax Cron Jobs" > "$TEMP_CRON"

# Check if cron job already exists
if grep -q "wp-cron.php" "$TEMP_CRON"; then
    echo "✓ Cron job already exists"
else
    # Add the cron job to run every minute
    echo "* * * * * cd /var/www/app.novarax.ae && /usr/bin/php wp-cron.php > /dev/null 2>&1" >> "$TEMP_CRON"
    
    # Install the new cron file
    crontab "$TEMP_CRON"
    echo "✓ Cron job added successfully"
fi

# Clean up
rm "$TEMP_CRON"

# 3. Verify cron job was added
echo ""
echo "Step 3: Verifying cron job..."
if crontab -l | grep -q "wp-cron.php"; then
    echo "✓ System cron job is active"
    echo ""
    echo "Current cron jobs:"
    crontab -l | grep "wp-cron"
else
    echo "✗ Failed to add cron job"
    exit 1
fi

# 4. Test the cron execution
echo ""
echo "Step 4: Testing cron execution..."
cd /var/www/app.novarax.ae
/usr/bin/php wp-cron.php

echo ""
echo "✓ Test execution completed"

# 5. Show cron logs location
echo ""
echo "=== Setup Complete ==="
echo ""
echo "The system cron will now run every minute and process:"
echo "  • Provisioning queue (new tenant databases)"
echo "  • License checks"
echo "  • Email notifications"
echo "  • Other scheduled tasks"
echo ""
echo "To monitor cron execution:"
echo "  tail -f /var/log/syslog | grep CRON"
echo ""
echo "To check provisioning queue:"
echo "  Visit: https://app.novarax.ae/provisioning-queue-status.php"
echo ""
echo "To manually trigger provisioning:"
echo "  cd /var/www/app.novarax.ae && /usr/bin/php wp-cron.php"
echo ""