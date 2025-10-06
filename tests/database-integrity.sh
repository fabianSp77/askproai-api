#!/bin/bash

# ========================================
# DATABASE INTEGRITY CHECK
# Validates foreign keys, orphaned records, and data consistency
# ========================================

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Counters
ISSUES_FOUND=0
CHECKS_RUN=0

echo -e "${BLUE}DATABASE INTEGRITY CHECK${NC}"
echo "======================================"

# Database connection
DB_NAME="askproai_db"
DB_USER="root"

# Function to run SQL query
run_query() {
    mysql -u $DB_USER $DB_NAME -e "$1" 2>/dev/null
}

# Function to get count from query
get_count() {
    mysql -u $DB_USER $DB_NAME -sN -e "$1" 2>/dev/null
}

# 1. Table Statistics
echo -e "${BLUE}1. TABLE STATISTICS${NC}"
echo "--------------------------------------"

MAIN_TABLES=("customers" "calls" "appointments" "invoices" "services" "companies" "staff" "users")

for table in "${MAIN_TABLES[@]}"; do
    count=$(get_count "SELECT COUNT(*) FROM $table")
    printf "  %-20s: %s records\n" "$table" "$count"
    CHECKS_RUN=$((CHECKS_RUN + 1))
done
echo ""

# 2. Foreign Key Integrity
echo -e "${BLUE}2. FOREIGN KEY INTEGRITY${NC}"
echo "--------------------------------------"

# Check orphaned calls (no customer)
echo -n "  Orphaned calls (no customer): "
orphaned=$(get_count "SELECT COUNT(*) FROM calls WHERE customer_id IS NOT NULL AND customer_id NOT IN (SELECT id FROM customers)")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$orphaned" -gt 0 ]; then
    echo -e "${RED}✗ Found $orphaned orphaned records${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + orphaned))
else
    echo -e "${GREEN}✓ OK${NC}"
fi

# Check orphaned appointments (no customer)
echo -n "  Orphaned appointments (no customer): "
orphaned=$(get_count "SELECT COUNT(*) FROM appointments WHERE customer_id IS NOT NULL AND customer_id NOT IN (SELECT id FROM customers)")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$orphaned" -gt 0 ]; then
    echo -e "${RED}✗ Found $orphaned orphaned records${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + orphaned))
else
    echo -e "${GREEN}✓ OK${NC}"
fi

# Check orphaned invoices (no customer)
echo -n "  Orphaned invoices (no customer): "
orphaned=$(get_count "SELECT COUNT(*) FROM invoices WHERE customer_id IS NOT NULL AND customer_id NOT IN (SELECT id FROM customers)")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$orphaned" -gt 0 ]; then
    echo -e "${RED}✗ Found $orphaned orphaned records${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + orphaned))
else
    echo -e "${GREEN}✓ OK${NC}"
fi

# Check calls with invalid staff_id
echo -n "  Calls with invalid staff_id: "
orphaned=$(get_count "SELECT COUNT(*) FROM calls WHERE staff_id IS NOT NULL AND staff_id NOT IN (SELECT id FROM staff)")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$orphaned" -gt 0 ]; then
    echo -e "${RED}✗ Found $orphaned invalid references${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + orphaned))
else
    echo -e "${GREEN}✓ OK${NC}"
fi

# Check appointments with invalid service_id
echo -n "  Appointments with invalid service_id: "
orphaned=$(get_count "SELECT COUNT(*) FROM appointments WHERE service_id IS NOT NULL AND service_id NOT IN (SELECT id FROM services)")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$orphaned" -gt 0 ]; then
    echo -e "${RED}✗ Found $orphaned invalid references${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + orphaned))
else
    echo -e "${GREEN}✓ OK${NC}"
fi

echo ""

# 3. Data Consistency Checks
echo -e "${BLUE}3. DATA CONSISTENCY${NC}"
echo "--------------------------------------"

# Check for duplicate customer emails
echo -n "  Duplicate customer emails: "
duplicates=$(get_count "SELECT COUNT(*) FROM (SELECT email, COUNT(*) as cnt FROM customers WHERE email IS NOT NULL GROUP BY email HAVING cnt > 1) as t")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$duplicates" -gt 0 ]; then
    echo -e "${YELLOW}⚠ Found $duplicates duplicate emails${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + duplicates))
else
    echo -e "${GREEN}✓ OK${NC}"
fi

# Check for future appointments in past status
echo -n "  Future appointments marked as completed: "
invalid=$(get_count "SELECT COUNT(*) FROM appointments WHERE starts_at > NOW() AND status = 'completed'")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$invalid" -gt 0 ]; then
    echo -e "${YELLOW}⚠ Found $invalid inconsistent records${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + invalid))
else
    echo -e "${GREEN}✓ OK${NC}"
fi

# Check for negative invoice amounts
echo -n "  Negative invoice amounts: "
negative=$(get_count "SELECT COUNT(*) FROM invoices WHERE total_amount < 0")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$negative" -gt 0 ]; then
    echo -e "${YELLOW}⚠ Found $negative negative amounts${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + negative))
else
    echo -e "${GREEN}✓ OK${NC}"
fi

echo ""

# 4. Index Analysis
echo -e "${BLUE}4. INDEX ANALYSIS${NC}"
echo "--------------------------------------"

echo "  Checking for missing indexes on foreign keys..."
missing_indexes=$(run_query "
    SELECT
        t.TABLE_NAME,
        c.COLUMN_NAME
    FROM
        information_schema.KEY_COLUMN_USAGE c
    JOIN
        information_schema.TABLES t ON c.TABLE_NAME = t.TABLE_NAME
    WHERE
        c.REFERENCED_TABLE_NAME IS NOT NULL
        AND c.TABLE_SCHEMA = '$DB_NAME'
        AND NOT EXISTS (
            SELECT 1
            FROM information_schema.STATISTICS s
            WHERE s.TABLE_SCHEMA = c.TABLE_SCHEMA
            AND s.TABLE_NAME = c.TABLE_NAME
            AND s.COLUMN_NAME = c.COLUMN_NAME
        )
    LIMIT 5;
")

if [ -z "$missing_indexes" ]; then
    echo -e "  ${GREEN}✓ All foreign keys have indexes${NC}"
else
    echo -e "  ${YELLOW}⚠ Missing indexes found:${NC}"
    echo "$missing_indexes"
    ISSUES_FOUND=$((ISSUES_FOUND + 1))
fi

echo ""

# 5. Table Health
echo -e "${BLUE}5. TABLE HEALTH${NC}"
echo "--------------------------------------"

# Check for crashed tables
echo -n "  Checking for crashed tables: "
crashed=$(run_query "CHECK TABLE customers, calls, appointments, invoices, services, staff, companies FAST QUICK" | grep -c "error\|warning")
CHECKS_RUN=$((CHECKS_RUN + 1))
if [ "$crashed" -gt 0 ]; then
    echo -e "${RED}✗ Found $crashed table issues${NC}"
    ISSUES_FOUND=$((ISSUES_FOUND + crashed))
else
    echo -e "${GREEN}✓ All tables healthy${NC}"
fi

# Check table sizes
echo ""
echo "  Largest tables by size:"
run_query "
    SELECT
        table_name AS 'Table',
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
    FROM information_schema.tables
    WHERE table_schema = '$DB_NAME'
    ORDER BY (data_length + index_length) DESC
    LIMIT 5;
"

echo ""

# 6. Recent Activity
echo -e "${BLUE}6. RECENT ACTIVITY${NC}"
echo "--------------------------------------"

echo "  Records created today:"
printf "    %-20s: %s\n" "Customers" "$(get_count "SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURDATE()")"
printf "    %-20s: %s\n" "Calls" "$(get_count "SELECT COUNT(*) FROM calls WHERE DATE(created_at) = CURDATE()")"
printf "    %-20s: %s\n" "Appointments" "$(get_count "SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURDATE()")"
printf "    %-20s: %s\n" "Invoices" "$(get_count "SELECT COUNT(*) FROM invoices WHERE DATE(created_at) = CURDATE()")"

echo ""

# Summary
echo "======================================"
echo -e "${BLUE}DATABASE INTEGRITY SUMMARY${NC}"
echo "--------------------------------------"
echo "Checks Run: $CHECKS_RUN"
if [ $ISSUES_FOUND -eq 0 ]; then
    echo -e "Issues Found: ${GREEN}$ISSUES_FOUND (Database is healthy)${NC}"
else
    echo -e "Issues Found: ${RED}$ISSUES_FOUND${NC}"
    echo -e "${YELLOW}⚠ Please review the issues above${NC}"
fi
echo "======================================"

# Exit code
if [ $ISSUES_FOUND -gt 0 ]; then
    exit 1
else
    exit 0
fi