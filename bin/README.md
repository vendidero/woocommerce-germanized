### Release guide

1. Bump versions in readme.txt and main plugin file.
2. Adjust composer.json (latest package versions) and run composer update to make sure latest package data exists.
3. Login to VVV SSH
```bash
cd /Users/dennis/wordpress-vagrant-dev/www/germanized
vagrant ssh
cd /srv/www/germanized/public_html/wp-content/plugins
```
4. Turn off xdebug as this might lead to mem errors while parings POTs
```bash
xdebug_off
```
5. Update POT files via 
```bash
# Create the POT files for each package
wp i18n make-pot --ignore-domain woocommerce-germanized/packages/woocommerce-germanized-dhl woocommerce-germanized/packages/woocommerce-germanized-dhl/i18n/languages/woocommerce-germanized-dhl.pot --exclude="assets/,.github,release/"
wp i18n make-pot --ignore-domain woocommerce-germanized/packages/woocommerce-germanized-shipments woocommerce-germanized/packages/woocommerce-germanized-shipments/i18n/languages/woocommerce-germanized-shipments.pot --exclude="assets/,.github,release/"

# Create main POT file and merge package POTs
wp i18n make-pot woocommerce-germanized woocommerce-germanized/i18n/languages/woocommerce-germanized.pot --ignore-domain --exclude="assets/,release/,build/" --merge="woocommerce-germanized/packages/woocommerce-germanized-shipments/i18n/languages/woocommerce-germanized-shipments.pot,woocommerce-germanized/packages/woocommerce-germanized-dhl/i18n/languages/woocommerce-germanized-dhl.pot"
```
6. Publish the release via GitHub and prepare SVN
```bash
# Run within the main plugin folder
./bin/deploy.sh
```