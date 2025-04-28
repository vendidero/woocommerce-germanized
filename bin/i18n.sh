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

# Use wp i18n to make sure JS build files are parsed instead of non-compiled assets (PoEdit does not support that)
wp i18n make-pot --ignore-domain ./packages/shiptastic-integration-for-dhl ./packages/shiptastic-integration-for-dhl/i18n/shiptastic-integration-for-dhl.pot --exclude="assets/,.github,release/,lib/"
wp i18n make-pot --ignore-domain ./packages/shiptastic-for-woocommerce ./packages/shiptastic-for-woocommerce/i18n/shiptastic-for-woocommerce.pot --exclude="assets/,.github,release/,lib/"

output 3 "Update i18n script paths in POT file to make sure WP is able to load the right translations from packages dir"
sed -i '' -e "s/\#: build\//\#: packages\/shiptastic-integration-for-dhl\/build\//g" ./packages/shiptastic-integration-for-dhl/i18n/shiptastic-integration-for-dhl.pot
sed -i '' -e "s/\#: build\//\#: packages\/shiptastic-for-woocommerce\/build\//g" ./packages/shiptastic-for-woocommerce/i18n/shiptastic-for-woocommerce.pot
output 2 "Done"

# Create Germanized Pro POT file and merge with StoreaBill
wp i18n make-pot ./ ./i18n/languages/woocommerce-germanized.pot --ignore-domain --exclude="assets/,release/,lib/" --merge="./packages/shiptastic-integration-for-dhl/i18n/shiptastic-integration-for-dhl.pot,./packages/shiptastic-for-woocommerce/i18n/shiptastic-for-woocommerce.pot"

# Run composer update to make sure POT file paths are being updated
composer update

# Refresh po from pot
msgmerge -U --suffix=off --backup=none ./i18n/languages/woocommerce-germanized-de_DE.po ./i18n/languages/woocommerce-germanized.pot
msgmerge -U --suffix=off --backup=none ./i18n/languages/woocommerce-germanized-de_DE_formal.po ./i18n/languages/woocommerce-germanized.pot

output 2 "Done! You may now edit the merged po files"