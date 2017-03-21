#!/usr/bin/env bash
# see https://github.com/wp-cli/wp-cli/blob/master/templates/install-wp-tests.sh

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
WOO_VERSION=${6-latest}

# TODO: allow environment vars for WP_TESTS_DIR & WP_CORE_DIR
WP_TESTS_DIR="${PWD}/tmp/wordpress-tests-lib"
WOO_TESTS_DIR="${PWD}/tmp/woocommerce"
WP_CORE_DIR="${PWD}/tmp/wordpress/"
WOO_CORE_DIR="${PWD}/tmp/woocommerce/"

set -ex

install_woo() {
    mkdir -p $WOO_CORE_DIR

    if [ $WOO_VERSION == 'latest' ]; then

		local RELEASES=$(curl https://api.github.com/repos/woocommerce/woocommerce/releases | sed -n 's/.*"tarball_url": "\(.*\)",/\1/p')
	    local a=($RELEASES)
	    local ARCHIVE_URL=$a

	elif [ $WOO_VERSION == 'latest_stable' ]; then
	    local ARCHIVE_URL=$(curl https://api.github.com/repos/woocommerce/woocommerce/releases/latest | sed -n 's/.*"tarball_url": "\(.*\)",/\1/p')
	else
		local ARCHIVE_URL=$(curl https://api.github.com/repos/woocommerce/woocommerce/releases/tags/$WOO_VERSION | sed -n 's/.*"tarball_url": "\(.*\)",/\1/p')
	fi

    curl -L $ARCHIVE_URL --output /tmp/woocommerce.tar.gz

    tar --strip-components=1 -zxmf /tmp/woocommerce.tar.gz -C $WOO_CORE_DIR
}

install_wp() {
	mkdir -p $WP_CORE_DIR

	if [ $WP_VERSION == 'latest' ]; then
		local ARCHIVE_NAME='latest'
	else
		local ARCHIVE_NAME="wordpress-$WP_VERSION"
	fi

	curl https://wordpress.org/${ARCHIVE_NAME}.tar.gz --output /tmp/wordpress.tar.gz --silent

	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

	curl https://raw.github.com/markoheijnen/wp-mysqli/master/db.php --output $WP_CORE_DIR/wp-content/db.php --silent
}

install_test_suite() {
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	# set up testing suite
	mkdir -p $WP_TESTS_DIR
	cd $WP_TESTS_DIR
	svn co --quiet http://develop.svn.wordpress.org/trunk/tests/phpunit/includes/
	svn co --quiet https://develop.svn.wordpress.org/trunk/tests/phpunit/data/ $WP_TESTS_DIR/data

	curl http://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php --output wp-tests-config.php --silent

	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
	sed $ioption "s/wptests_/wcgzdtests_/" wp-tests-config.php
	sed $ioption "s/example.org/vendidero.de/" wp-tests-config.php
	sed $ioption "s/admin@example.org/tests@vendidero.de/" wp-tests-config.php
	sed $ioption "s/Test Blog/WC GZD Unit Tests/" wp-tests-config.php
}

install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [[ "$DB_SOCK_OR_PORT" =~ ^[0-9]+$ ]] ; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_wp
install_woo
install_test_suite
install_db