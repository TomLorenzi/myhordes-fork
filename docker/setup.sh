#!/bin/bash

docker-compose run php sh -c "\
    cd /var/www/html
	cp .env .env.local

	# Converts Windows EOL to UNIX EOL, for Docker on Windows.
	sed -i 's/\r//g' bin/console
	chmod +x bin/console

	# Install PHP dependencies
	composer install

	# Create database
	bin/console doctrine:database:create --if-not-exists
	bin/console doctrine:schema:update --force
	bin/console doctrine:fixtures:load --append

	# Install node dependencies
	# yarn

	yarn encore dev
"

echo "Connecting to the PHP container..."
docker-compose exec php bash