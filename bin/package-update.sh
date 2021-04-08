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
find ./packages/woocommerce-trusted-shops -iname '*.php' -exec sed -i.bak -e "s/, 'woocommerce-trusted-shops'/, 'woocommerce-germanized'/g" {} \;
find ./packages/woocommerce-germanized-shipments -iname '*.php' -exec sed -i.bak -e "s/, 'woocommerce-germanized-shipments'/, 'woocommerce-germanized'/g" {} \;
find ./packages/woocommerce-germanized-dhl -iname '*.php' -exec sed -i.bak -e "s/, 'woocommerce-germanized-dhl'/, 'woocommerce-germanized'/g" {} \;

# Replace template module comment for TS support
find ./packages/woocommerce-trusted-shops/templates -iname '*.php' -exec sed -i.bak -e "s|Module: WooCommerce Trusted Shops|Module: WooCommerce Germanized|g" {} \;

# Delete vendor directory in packages to avoid duplicate dependencies
rm -rf ./packages/woocommerce-trusted-shops/vendor
rm -rf ./packages/woocommerce-trusted-shops/.wordpress-org

rm -rf ./packages/woocommerce-germanized-shipments/vendor
rm -rf ./packages/woocommerce-germanized-dhl/vendor

output 3 "Clean vendor dirs to save space..."

rm -rf ./vendor/dvdoug/boxpacker/visualiser/*
rm -rf ./vendor/dvdoug/boxpacker/tests/data/*

output 2 "Done!"

# Cleanup backup files
find ./packages -name "*.bak" -type f -delete
output 2 "Done!"