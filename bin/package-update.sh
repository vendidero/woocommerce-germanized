#!/bin/sh

# Output colorized strings
#
# Color codes:
# 0 - black
# 1 - red
# 2 - green
# 3 - yellow
# 4 - blue
# 5 - magenta
# 6 - cian
# 7 - white
output() {
	echo "$(tput setaf "$1")$2$(tput sgr0)"
}

if [ ! -d "packages/" ]; then
	output 1 "./packages doesn't exist!"
	output 1 "run \"composer install\" before proceed."
fi

# Autoloader
output 3 "Updating autoloader classmaps..."
composer dump-autoload
output 2 "Done"

# Convert textdomains
output 3 "Updating package textdomains..."

# Replace text domains within packages with woocommerce
find ./packages/woocommerce-germanized-shipments -iname '*.php' -exec sed -i.bak -e "s/, 'woocommerce-germanized-shipments'/, 'woocommerce-germanized'/g" {} \;
find ./packages/woocommerce-germanized-dhl -iname '*.php' -exec sed -i.bak -e "s/, 'woocommerce-germanized-dhl'/, 'woocommerce-germanized'/g" {} \;
find ./vendor/vendidero/woocommerce-eu-tax-helper -iname '*.php' -exec sed -i.bak -e "s/, 'woocommerce-eu-tax-helper'/, 'woocommerce-germanized'/g" {} \;

rm -rf ./packages/woocommerce-germanized-shipments/vendor
rm -rf ./packages/woocommerce-germanized-dhl/vendor
rm -rf ./vendor/vendidero/woocommerce-eu-tax-helper/vendor

output 3 "Clean vendor dirs to save space..."

rm -rf ./vendor/dvdoug/boxpacker/visualiser/*
rm -rf ./vendor/dvdoug/boxpacker/tests/data/*

output 2 "Done!"

# Cleanup backup files
find ./packages -name "*.bak" -type f -delete
find ./vendor -name "*.bak" -type f -delete
output 2 "Done!"