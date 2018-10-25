# How to contribute

## Issues

When [filing bugs](https://github.com/matthiasmullie/php-api-oauth/issues/new),
try to be as thorough as possible:
* What version did you use?
* What did you try to do? ***Please post the relevant parts of your code.***
* What went wrong? ***Please include error messages, if any.***
* What was the expected result?


## Pull requests

Bug fixes and general improvements to the existing codebase are always welcome.
New features are also welcome, but will be judged on an individual basis. If
you'd rather not risk wasting your time implementing a new feature only to see
it turned down, please start the discussion by
[opening an issue](https://github.com/matthiasmullie/php-api-oauth/issues/new).

Don't forget to add your changes to the [changelog](CHANGELOG.md).


## Testing

### Running the tests

Docker images has been created to set up the entire environment.
These can be launched from the command line, as configured in the makefile.
Just make sure you have installed
[Docker](https://docs.docker.com/engine/installation/) &
[Docker-compose](https://docs.docker.com/compose/install/).

To run the complete test suite on the latest PHP release:

```sh
make test
```

Or to make the requests over HTTP rather than routing them internally:

```sh
make test REQUEST=http
```

Or with a specific PHP version:

```sh
make test PHP=7.0
```

Travis CI has been [configured](.travis.yml) to run a matrix of all supported
PHP versions & http/internal routing individually.
Upon submitting a new pull request, that test suite will be run & report
back on your pull request. Please make sure the test suite passes.


### Writing tests

Please include tests for every change or addition to the code.


## Coding standards

All code must follow [PSR-2](http://www.php-fig.org/psr/psr-2/). Just make sure
to run php-cs-fixer before submitting the code, it'll take care of the
formatting for you:

```sh
vendor/bin/php-cs-fixer fix html
vendor/bin/php-cs-fixer fix src
vendor/bin/php-cs-fixer fix tests
```

Document the code thoroughly!


## License

Note that php-api-oauth is MIT-licensed, which basically allows anyone
to do anything they like with it, without restriction.
