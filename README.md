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

# Testing Against the Starter Kit
You can use the values below, but the `RS_AUTH_TOKEN` needs to be obtained 
through your browser by checking the `Authorization` header that is used to 
access the storage.

    <var name="RS_BASE_URL" value="http://localhost:8000/storage"/>
    <var name="RS_AUTH_TOKEN" value="f2485ae09b15...ea03071542a11"/>
    <var name="RS_USER_ID" value="me"/>
    <var name="RS_SCOPE" value="notes:rw"/>

# Testing against php-remote-storage
These are usuable values for the `php-remote-storage` server implementation, 
you do need to obtain the `RS_AUTH_TOKEN` from your browser as well.

    <var name="RS_BASE_URL" value="http://localhost/php-remote-storage/api.php"/>
    <var name="RS_AUTH_TOKEN" value="f2485ae09b15...ea03071542a11"/>
    <var name="RS_USER_ID" value="admin"/>
    <var name="RS_SCOPE" value="foo:rw"/>

The PHP server can be found 
[here](https://github.com/fkooman/php-remote-storage).
