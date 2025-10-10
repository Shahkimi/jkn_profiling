#!/bin/bash

# Setup script for cache warmer cron job
# This script sets up a cron job to run the cache warmer every hour

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CACHE_WARMER="$SCRIPT_DIR/cache_warmer.php"
LOG_FILE="$SCRIPT_DIR/cache/cache_warmer.log"

echo "Setting up cache warmer cron job..."

# Create cache directory if it doesn't exist
mkdir -p "$SCRIPT_DIR/cache"

# Create log file if it doesn't exist
touch "$LOG_FILE"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "cache_warmer.php"; then
    echo "Cache warmer cron job already exists"
else
    # Add cron job to run every hour at minute 0
    (crontab -l 2>/dev/null; echo "0 * * * * /usr/bin/php $CACHE_WARMER >> $LOG_FILE 2>&1") | crontab -
    echo "Cache warmer cron job added successfully"
fi

echo "Cron job configuration:"
echo "- Runs every hour at minute 0"
echo "- Script: $CACHE_WARMER"
echo "- Log file: $LOG_FILE"
echo ""
echo "To view current cron jobs: crontab -l"
echo "To view cache warmer logs: tail -f $LOG_FILE"
echo ""
echo "Manual cache warming: php $CACHE_WARMER"