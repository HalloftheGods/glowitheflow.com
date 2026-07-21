#!/bin/bash
set -x

LOG_DIR="/home/xopher/www/x/glowitheflow.com/.agents/worker_m6_verification"
mkdir -p "$LOG_DIR"

echo "=== Starting Verification ===" > "$LOG_DIR/run.status"

# 1. Composer Install
echo "Running composer install..."
cd /home/xopher/www/x/glowitheflow.com/wordpress-plugin
composer install --no-interaction --prefer-dist > "$LOG_DIR/composer.log" 2>&1
COMPOSER_STATUS=$?
echo "Composer exit code: $COMPOSER_STATUS" >> "$LOG_DIR/run.status"

# 2. PHPUnit
echo "Running PHPUnit..."
./vendor/bin/phpunit > "$LOG_DIR/phpunit.log" 2>&1
PHPUNIT_STATUS=$?
echo "PHPUnit exit code: $PHPUNIT_STATUS" >> "$LOG_DIR/run.status"

# 3. DB Edge Cases
echo "Running DB Edge Cases..."
php tests/verify-db-edge-cases.php > "$LOG_DIR/db-edge-cases.log" 2>&1
DB_STATUS=$?
echo "DB Edge Cases exit code: $DB_STATUS" >> "$LOG_DIR/run.status"

# 4. Vitest
echo "Running Vitest..."
cd /home/xopher/www/x/glowitheflow.com
pnpm test > "$LOG_DIR/vitest.log" 2>&1
VITEST_STATUS=$?
echo "Vitest exit code: $VITEST_STATUS" >> "$LOG_DIR/run.status"

# 5. Nuxt Build
echo "Running Nuxt Build..."
pnpm build > "$LOG_DIR/build.log" 2>&1
BUILD_STATUS=$?
echo "Build exit code: $BUILD_STATUS" >> "$LOG_DIR/run.status"

echo "=== Verification Finished ===" >> "$LOG_DIR/run.status"
echo "Composer: $COMPOSER_STATUS, PHPUnit: $PHPUNIT_STATUS, DB: $DB_STATUS, Vitest: $VITEST_STATUS, Build: $BUILD_STATUS"
