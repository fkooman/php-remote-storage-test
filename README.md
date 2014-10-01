# Introduction
This is an integration test suite for [remoteStorage](http://remotestorage.io)
servers. It can be used to validate the specification compliancy of a 
remoteStorage implementation.

This tool aims at validating `draft-dejong-remotestorage-03.txt` compliance, 
including the WebFinger discovery step. The OAuth token needs to be manually
obtained, see below.

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

# Obtaining `RS_TOKEN`
It is not so easy to obtain `RS_TOKEN`, because it is needed to perform the
OAuth dance and give permission to the application. For `php-oauth-as` one
can just query the auth URI (from WebFinger response) with the following
parameters:

    https://localhost/php-oauth-as/authorize.php?client_id=http://demo.example.org&redirect_uri=http://demo.example.org&response_type=token&scope=foo:rw bar:r

The parameters `client_id`, `redirect_uri` need to be the same, `scope` needs
to be `foo:rw bar:r` and `response_type` needs to be `token`. After accepting
the application the `access_token` parameter needs to be fetched from the 
URI fragment the browser gets redirected to.

Hopefully there will be a way to make this easier in the future, like an app
that will just display the `RS_TOKEN` value.

# Run 
To run the tests, run [PHPUnit](https://phpunit.de). PHPUnit should also be 
installed by Composer, so the following should work:

    $ phpunit tests/

This should run the test against your server and show you the output on how
the server validates the compliancy test.
