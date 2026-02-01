#!/bin/bash

# Test script to validate form filling functionality
# Run: bash test_form_filling.sh

echo "=========================================="
echo "TESTING FORM FILLING FUNCTIONALITY"
echo "=========================================="

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Check PHP syntax
echo -e "\n${YELLOW}Test 1: Checking PHP Syntax${NC}"

php -l "c:\laragon\www\account opening\cso\ecobank_account_form.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ ecobank_account_form.php syntax OK${NC}"
else
    echo -e "${RED}✗ ecobank_account_form.php has syntax errors${NC}"
fi

php -l "c:\laragon\www\account opening\cso\includes\UDFDataMapper.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ UDFDataMapper.php syntax OK${NC}"
else
    echo -e "${RED}✗ UDFDataMapper.php has syntax errors${NC}"
fi

# Test 2: Check if required files exist
echo -e "\n${YELLOW}Test 2: Checking Required Files${NC}"

FILES=(
    "c:\laragon\www\account opening\cso\ecobank_account_form.php"
    "c:\laragon\www\account opening\cso\includes\UDFDataMapper.php"
    "c:\laragon\www\account opening\cso\includes\flexcube_helpers.php"
    "c:\laragon\www\account opening\cso\includes\FlexcubeAPI.php"
    "c:\laragon\www\account opening\cso\fetch_account_flexcube.php"
    "c:\laragon\www\account opening\vendors\js\form_auto_filler.js"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ Found: $(basename $file)${NC}"
    else
        echo -e "${RED}✗ Missing: $(basename $file)${NC}"
    fi
done

# Test 3: Check for critical string patterns
echo -e "\n${YELLOW}Test 3: Checking for Critical Code Patterns${NC}"

# Check if account_number filling is present
if grep -q "setVal('#form-bank-account-number'" "c:\laragon\www\account opening\cso\ecobank_account_form.php"; then
    echo -e "${GREEN}✓ Account number filling code found${NC}"
else
    echo -e "${RED}✗ Account number filling code NOT found${NC}"
fi

# Check if customer_id filling is present
if grep -q "setVal('#customer-id'" "c:\laragon\www\account opening\cso\ecobank_account_form.php"; then
    echo -e "${GREEN}✓ Customer ID filling code found${NC}"
else
    echo -e "${RED}✗ Customer ID filling code NOT found${NC}"
fi

# Check if telephone filling is present
if grep -q "setVal('#telephone'" "c:\laragon\www\account opening\cso\ecobank_account_form.php"; then
    echo -e "${GREEN}✓ Telephone filling code found${NC}"
else
    echo -e "${RED}✗ Telephone filling code NOT found${NC}"
fi

# Check for phoneNo support in form_auto_filler.js
if grep -q "phoneNo" "c:\laragon\www\account opening\vendors\js\form_auto_filler.js"; then
    echo -e "${GREEN}✓ phoneNo variant support in FormAutoFiller found${NC}"
else
    echo -e "${RED}✗ phoneNo variant support NOT found${NC}"
fi

# Check for camelCase handling in UDFDataMapper
if grep -q "preg_replace.*camelCase" "c:\laragon\www\account opening\cso\includes\UDFDataMapper.php"; then
    echo -e "${GREEN}✓ camelCase handling in UDFDataMapper found${NC}"
else
    echo -e "${RED}✗ camelCase handling NOT found${NC}"
fi

echo -e "\n=========================================="
echo -e "${GREEN}Testing complete!${NC}"
echo -e "==========================================\n"

echo "Next steps:"
echo "1. Open: http://localhost/account%20opening/cso/ecobank_account_form.php"
echo "2. Enter an account number in the search bar"
echo "3. Click 'Search' button"
echo "4. Verify that:"
echo "   - Account number field is pre-filled"
echo "   - Customer ID field is pre-filled"
echo "   - Telephone field is pre-filled from API"
echo "5. Open browser console (F12) to check for debug messages"
