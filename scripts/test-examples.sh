#!/bin/bash

# Test examples script for tommyknocker/struct
# This script runs all example files to ensure they work correctly

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}Testing tommyknocker/struct examples...${NC}"
echo "Project root: $PROJECT_ROOT"
echo

# Check if we're in the right directory
if [ ! -f "$PROJECT_ROOT/composer.json" ]; then
    echo -e "${RED}Error: composer.json not found. Please run this script from the project root.${NC}"
    exit 1
fi

# Check if vendor directory exists
if [ ! -d "$PROJECT_ROOT/vendor" ]; then
    echo -e "${YELLOW}Warning: vendor directory not found. Running composer install...${NC}"
    cd "$PROJECT_ROOT"
    composer install --no-dev --optimize-autoloader
fi

# Change to project root
cd "$PROJECT_ROOT"

# Array of example files to test
EXAMPLES=(
    "examples/01_basic_api_validation.php"
    "examples/02_advanced_validation.php"
    "examples/03_complex_api_structures.php"
    "examples/04_api_response_formatting.php"
    "examples/05_field_mapping_aliases.php"
    "examples/06_union_types_and_transformations.php"
    "examples/07_factory_pattern.php"
    "examples/08_strict_mode_and_exceptions.php"
)

# Counters
TOTAL_EXAMPLES=${#EXAMPLES[@]}
PASSED=0
FAILED=0

echo -e "${BLUE}Found $TOTAL_EXAMPLES example files to test${NC}"
echo

# Test each example
for example in "${EXAMPLES[@]}"; do
    if [ ! -f "$example" ]; then
        echo -e "${RED}✗ $example - File not found${NC}"
        FAILED=$((FAILED + 1))
        continue
    fi
    
    echo -n "Testing $example... "
    
    # Run the example and capture output
    if php "$example" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ PASSED${NC}"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}✗ FAILED${NC}"
        echo -e "${RED}Error output:${NC}"
        php "$example" 2>&1 | head -10
        echo
        FAILED=$((FAILED + 1))
    fi
done

echo
echo "=========================================="
echo -e "${BLUE}Test Results Summary:${NC}"
echo -e "Total examples: $TOTAL_EXAMPLES"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}All examples passed successfully! ✓${NC}"
    exit 0
else
    echo -e "${RED}Some examples failed. Please check the output above.${NC}"
    exit 1
fi
