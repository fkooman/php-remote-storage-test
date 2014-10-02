# Introduction
This is an integration test suite for [remoteStorage](http://remotestorage.io)
servers. It can be used to validate the specification compliancy of a 
remoteStorage implementation.

This tool aims at validating `draft-dejong-remotestorage-03.txt` compliance, 
including the WebFinger discovery step. The OAuth token needs to be manually
obtained, see below.

# Requirements
You need the following installed on your system:
- PHP >= 5.4
- php-openssl
- [PHPUnit](https://phpunit.de)

You may need to install OpenSSL support for PHP by installing `php5-openssl`, 
for instance on Debian. Make sure the module is enabled in your `php.ini` 
file.

# Installation
You need [Composer](https://getcomposer.org) to install the dependencies to
run the tests.

    $ /path/to/composer.phar install

# Configuration
Copy `phpunit.xml.dist` to `phpunit.xml` and modify the configuration variables
accordingly.

You need to obtain the `RS_TOKEN` value from your browser. You can modify this 
in `phpunit.xml`.

    <?xml version="1.0"?>
    <phpunit bootstrap="vendor/autoload.php" strict="true" colors="true">
      <php>
        <var name="WEBFINGER_ID" value="me@example.org"/>
        <var name="WEBFINGER_VERIFY_CERT" value="true"/>
        <var name="WEBFINGER_IGNORE_MEDIA_TYPE" value="false"/>
        <var name="RS_VERIFY_CERT" value="true"/>
        <var name="RS_TOKEN" value="12345"/>
      </php>
    </phpunit>

# Obtaining an Access Token
It is not so easy to obtain `RS_TOKEN`, because one needs to perform the
OAuth dance and give permission to the application. To make this somewhat
easier, a simple remoteStorage app was written to obtain `RS_TOKEN`. You can
find it at [https://www.php-oauth.net/app/integration-test/](https://www.php-oauth.net/app/integration-test/).
Use the widget to connect to your remoteStorage instance and copy/paste the
token to the `RS_TOKEN` value in `phpunit.xml`.

# Run 
To run the tests, run [PHPUnit](https://phpunit.de). PHPUnit should also be 
installed by Composer, so the following should work:

    $ phpunit tests/

This should run the test against your server and show you the output on how
the server validates the compliancy test.
