if [ `which vendor/bin/phpunit` ]; then
    vendor/bin/phpunit -c phpunit.xml.dist
else
   phpunit -c phpunit.xml.dist
fi