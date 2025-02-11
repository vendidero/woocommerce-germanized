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
find ./packages/shiptastic-for-woocommerce -iname '*.php' -exec sed -i.bak -e "s/, 'shiptastic-for-woocommerce'/, 'woocommerce-germanized'/g" {} \;
find ./packages/woocommerce-eu-tax-helper -iname '*.php' -exec sed -i.bak -e "s/, 'woocommerce-eu-tax-helper'/, 'woocommerce-germanized'/g" {} \;

output 3 "Updating package JS textdomains..."
find ./packages/shiptastic-for-woocommerce \( -iname '*.js' -o -iname '*.json' \) -exec sed -i.bak -e "s/'shiptastic-for-woocommerce'/'woocommerce-germanized'/g" -e "s/\"shiptastic-for-woocommerce\"/\"woocommerce-germanized\"/g" {} \;

rm -rf ./i18n/languages/*.json

if [ $COMPOSER_DEV_MODE -eq 0 ]; then
    output 3 "Removing POT files"
    rm -f ./packages/shiptastic-for-woocommerce/i18n/shiptastic-for-woocommerce.pot
fi

output 3 "Patching libraries..."
sed -i '' -e 's/get_class()/__CLASS__/g' ./vendor/baltpeter/internetmarke-php/src/baltpeter/Internetmarke/ApiResult.php

rm -rf ./packages/shiptastic-for-woocommerce/vendor
rm -rf ./packages/woocommerce-eu-tax-helper/vendor

output 3 "Clean vendor dirs to save space..."

rm -rf ./vendor/dvdoug/boxpacker/visualiser/*
rm -rf ./vendor/dvdoug/boxpacker/docs/*
rm -rf ./vendor/dvdoug/boxpacker/tests/data/*
rm -rf ./vendor/dvdoug/boxpacker/*.md
rm -rf ./vendor/dvdoug/boxpacker/.github/*

rm -rf ./vendor/setasign/fpdf/tutorial/*
rm -rf ./vendor/setasign/fpdf/doc/*

output 2 "Done!"

# Cleanup backup files
find ./packages -name "*.bak" -type f -delete
output 2 "Done!"