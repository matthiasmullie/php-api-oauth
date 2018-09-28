# matthiasmullie/php-api-auth

[![Build status](https://api.travis-ci.org/matthiasmullie/php-api-oauth.svg?branch=master)](https://travis-ci.org/matthiasmullie/php-api-oauth)
[![License](http://img.shields.io/packagist/l/matthiasmullie/php-api-oauth.svg)](https://github.com/matthiasmullie/php-api-oauth/blob/master/LICENSE)


This repository is a simple authentication API, providing only the endpoints to create/edit/get accounts & applications.
It's an easy starting point for any such API, but you'll have to add the other domain-specific details yourself - an API that does nothing other than accounts & applications isn't too useful, right?


## Code

Just clone this project and use/enrich/change it to your needs.

```
git clone matthiasmullie/php-api-oauth
```


## Configuration

### [config/config.yml](https://github.com/matthiasmullie/php-api-oauth/blob/master/config/config.yml)

This one holds the database credentials & the name of the "default" application.

### [config/routes.yml](https://github.com/matthiasmullie/php-api-oauth/blob/master/config/routes.yml)

This one holds the routes to the API controllers, the expected parameters & responses.


## Docker & Travis CI

In order to quickly get your API running on your local machine (or anything
supporting Docker images), just build the docker-compose suite by issuing this
makefile command:

```
make test
```

With the included **.travis.yml** config, you should have those tests on
Travis CI in no time!
