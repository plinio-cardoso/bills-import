down:
	docker-compose down

up:
	docker-compose up -d

migration-fresh:
	docker-compose run --rm php bin/console doctrine:schema:drop --force
	docker-compose run --rm php bin/console doctrine:schema:create
	docker-compose run --rm php bin/console doctrine:fixtures:load --no-interaction
	docker-compose run --rm php bin/console cache:clear

composer-install:
	docker-compose run --rm composer install

fresh-start: up composer-install

watch:
	docker-compose run --rm node npm run watch

dev-frontend:
	docker-compose run --rm node npm run dev

pull:
	git pull origin main

clear-cache:
	docker-compose run --rm php bin/console cache:clear

deploy: pull dev-frontend clear-cache

npm-install:
	docker-compose run --rm node npm install
