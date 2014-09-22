# Introduction
This is a integration test suite for [remoteStorage](http://remotestorage.io)
servers. It can be used to validate the specification compliancy of a 
remoteStorage implementation.

# Installation
You need [Composer](https://getcomposer.org) to install the dependencies to
run the tests.

    $ /path/to/composer.phar install

# Configuration
Copy `phpunit.xml.dist` to `phpunit.xml` and modify the configuration variables 
to suit your server configuration.

# Run 
To run the tests, run [PHPUnit](https://phpunit.de):

    $ /path/to/phpunit.phar tests/

This should run the test against your server and show you the output on how
the server validates the compliancy test.
