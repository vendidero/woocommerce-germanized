if [[ ${TRAVIS_PHP_VERSION} == ${PHP_LATEST_STABLE} ]]; then
	vendor/bin/phpunit -c phpunit.xml.dist --coverage-clover ./tmp/clover.xml
else
	vendor/bin/phpunit -c phpunit.xml.dist
fi