#!/bin/bash
# Test Runner - Ejecuta todos los tests unitarios del proyecto
# Uso: bash run_all_tests.sh

cd "$(dirname "$0")"

echo "======================================"
echo " SUITE DE TESTS UNITARIOS - Minimarcket"
echo "======================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

total_passed=0
total_failed=0

run_test() {
    local test_file=$1
    local test_name=$(basename "$test_file" .php)
    
    echo "► Ejecutando: $test_name"
    echo "--------------------------------------"
    
    if php "$test_file" 2>&1; then
        echo -e "${GREEN}✓ $test_name PASÓ${NC}"
        ((total_passed++))
    else
        echo -e "${RED}✗ $test_name FALLÓ${NC}"
        ((total_failed++))
    fi
    echo ""
}

# Ejecutar cada test
# Ejecutar cada test
run_test "tests/run_orders_test.php"
run_test "tests/run_payroll_test.php"
run_test "tests/run_credit_test.php"
run_test "tests/run_precision_test.php"
run_test "tests/run_advanced_inventory_test.php"
run_test "tests/run_auth_test.php"
run_test "tests/run_cash_register_test.php"
run_test "tests/run_supplier_test.php"
run_test "tests/run_transaction_test.php"

echo "======================================"
echo " RESUMEN"
echo "======================================"
echo -e "${GREEN}Tests Pasados: $total_passed${NC}"
echo -e "${RED}Tests Fallidos: $total_failed${NC}"
echo ""

if [ $total_failed -eq 0 ]; then
    echo -e "${GREEN}✓ Todos los tests pasaron exitosamente!${NC}"
    exit 0
else
    echo -e "${RED}✗ Algunos tests fallaron. Revisar salida.${NC}"
    exit 1
fi
