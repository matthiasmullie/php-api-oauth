# defaults for `make test`
PHP ?= '7.2'
UP ?= 1
DOWN ?= 1
REQUEST ?= 'default'

install:
	curl -vs https://getcomposer.org/installer 2>&1 | php
	./composer.phar install
	rm composer.phar

docs:
	curl -O http://apigen.org/apigen.phar
	chmod +x apigen.phar
	php apigen.phar generate --source=src,testhelpers,vendor --skip-doc-path="*/vendor/*" --destination=docs --template-theme=bootstrap
	rm apigen.phar

up:
	docker-compose -f tests/docker/docker-compose.yml -f tests/docker/docker-compose.$(PHP).yml up -d client testserver

down:
	docker-compose -f tests/docker/docker-compose.yml -f tests/docker/docker-compose.$(PHP).yml stop -t0 client testserver

test:
	[ $(UP) -eq 1 ] && make up PHP=$(PHP) || true
	$(eval cmd='docker-compose -f tests/docker/docker-compose.yml -f tests/docker/docker-compose.$(PHP).yml run client sh -c "REQUEST=$(REQUEST) vendor/bin/phpunit"')
	eval $(cmd); status=$$?; [ $(DOWN) -eq 1 ] && make down PHP=$(PHP); exit $$status

serve:
	docker-compose -f docker-compose.yml up -d server

halt:
	docker-compose -f docker-compose.yml stop -t0 server
