#!/usr/bin/env bash

if [[ ${TRAVIS_PHP_VERSION:0:3} != "5.2" ]]; then
	$HOME/.composer/vendor/bin/phpunit -c phpunit.xml $@
else
	phpunit -c phpunit.xml $@
fi