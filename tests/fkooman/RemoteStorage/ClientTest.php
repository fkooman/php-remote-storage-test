<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ClientTest extends PHPUnit_Framework_TestCase
{

    // global variables used to store the versions of folders and documents
    // for use in later tests...
    protected $backupGlobalsBlacklist = array(
        'TESTS_FOO_VERSION',
        'TESTS_TEST_FOLDER_VERSION'
    );

    private $baseUrl;
    private $userId;
    private $moduleName;
    private $authToken;
    private $testFolder;
    private $baseDataUrl;

    protected static $randomString;

    public static function setUpBeforeClass()
    {
        self::$randomString = self::randomString();
    }

    public function setUp()
    {
        $this->baseUrl = $GLOBALS['RS_BASE_URL'];
        $this->userId = $GLOBALS['RS_USER_ID'];
        $this->moduleName = explode(":", $GLOBALS['RS_SCOPE'])[0];
        $this->authToken = $GLOBALS['RS_AUTH_TOKEN'];
        $this->testFolder = self::$randomString;
        $this->baseDataUrl = sprintf('%s/%s/%s/%s/', $this->baseUrl, $this->userId, $this->moduleName, $this->testFolder);
    }

    public function testNonExistingFolder()
    {
        $client = new Client();
        $response = $client->get($this->baseDataUrl);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());
        $jsonData = $response->json(array("object"=>true));
        $this->assertEquals("http://remotestorage.io/spec/folder-description", $jsonData->{'@context'});
        $this->assertInternalType("object", $jsonData->items);
        $this->assertEquals("application/ld+json", $response->getHeader("Content-Type"));
        $this->assertEquals(0, $response->getHeader("Expires"));

        // make better test, maybe also !
        $this->assertTrue(is_string($response->getHeader("ETag")) && strlen($response->getHeader("ETag")) > 0);
    }

    public function testPutDocument()
    {
        $client = new Client();
        $response = $client->put(
            $this->baseDataUrl . 'foo',
            array(
                'body' => 'Hello World',
                'headers' => array (
                    'Content-Type' => 'text/plain'
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());
        $this->assertEquals(0, $response->getHeader("Expires"));
        $this->assertTrue(is_string($response->getHeader("ETag")) && strlen($response->getHeader("ETag")) > 0);
        $GLOBALS['TESTS_FOO_VERSION'] = explode('"', $response->getHeader("ETag"))[1];
    }

    public function testPutExistingDocument()
    {
        // we try to put the same document as before, but as we specify a
        // wrong version so this MUST fail
        try {
            $client = new Client();

            $response = $client->put(
                $this->baseDataUrl . 'foo',
                array(
                    'body' => 'Hello New World',
                    'headers' => array (
                        'Content-Type' => 'text/plain',
                        'If-Match' => '"wr0ngv3rs10n"'
                    )
                )
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            $this->assertEquals(412, $e->getResponse()->getStatusCode());
            $this->assertEquals("Precondition Failed", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testPutExistingDocumentIfNonMatchStar()
    {
        // we try to put the same document as before, but as we specify "*" in
        // If-None-Match it should fail
        try {
            $client = new Client();

            $response = $client->put(
                $this->baseDataUrl . 'foo',
                array(
                    'body' => 'Hello World',
                    'headers' => array (
                        'Content-Type' => 'text/plain',
                        'If-None-Match' => '"*"'
                    )
                )
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            $this->assertEquals(412, $e->getResponse()->getStatusCode());
            $this->assertEquals("Precondition Failed", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testGetExistingDocument()
    {
        $client = new Client();
        $response = $client->get(
            $this->baseDataUrl . 'foo'
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());
        $this->assertEquals("Hello World", $response->getBody()->__toString());
        $this->assertRegexp("|^text/plain.*|", $response->getHeader("Content-Type"));
        $this->assertEquals(0, $response->getHeader("Expires"));
        $this->assertTrue(is_string($response->getHeader("ETag")) && strlen($response->getHeader("ETag")) > 0);
    }

    public function testGetNonExistingDocument()
    {
        try {
            $client = new Client();
            $response = $client->get(
                $this->baseDataUrl . 'bar'
            );
        } catch (ClientException $e) {
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
            $this->assertEquals("Not Found", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testGetExistingDocumentConditional()
    {
        try {
            $client = new Client();

            $response = $client->get(
                $this->baseDataUrl . 'foo',
                array(
                    'headers' => array (
                        'If-None-Match' => sprintf('"%s"', $GLOBALS['TESTS_FOO_VERSION'])
                    )
                )
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            $this->assertEquals(412, $e->getResponse()->getStatusCode());
            $this->assertEquals("Precondition Failed", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testGetExistingFolder()
    {
        $client = new Client();
        $response = $client->get($this->baseDataUrl);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());
        $this->assertEquals("application/ld+json", $response->getHeader("Content-Type"));
        $this->assertEquals(0, $response->getHeader("Expires"));

        $this->assertEquals(
            array(
                '@context' => 'http://remotestorage.io/spec/folder-description',
                'items' => array(
                    "foo" => array(
                        "Content-Length" => 11,
                        "Content-Type" => 'text/plain',
                        "ETag" => $GLOBALS['TESTS_FOO_VERSION']
                    )
                )
            ),
            $response->json()
        );
        // make better test, maybe also !
        $this->assertTrue(is_string($response->getHeader("ETag")) && strlen($response->getHeader("ETag")) > 0);
        $GLOBALS['TESTS_TEST_FOLDER_VERSION'] = explode('"', $response->getHeader("ETag"))[1];
    }

    public function testGetExistingFolderConditional()
    {
        try {
            $client = new Client();

            $response = $client->get(
                $this->baseDataUrl,
                array(
                    'headers' => array (
                        'If-None-Match' => sprintf('"%s"', $GLOBALS['TESTS_TEST_FOLDER_VERSION'])
                    )
                )
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            $this->assertEquals(412, $e->getResponse()->getStatusCode());
            $this->assertEquals("Precondition Failed", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testDeleteDocumentWrongConditional()
    {
        try {
            $client = new Client();

            $response = $client->delete(
                $this->baseDataUrl . 'foo',
                array(
                    'headers' => array (
                        'If-Match' => '"definitely-wrong-version"'
                    )
                )
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            $this->assertEquals(412, $e->getResponse()->getStatusCode());
            $this->assertEquals("Precondition Failed", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testDeleteDocumentConditional()
    {
        $client = new Client();

        $response = $client->delete(
            $this->baseDataUrl . 'foo',
            array(
                'headers' => array (
                    'If-Match' => sprintf('"%s"', $GLOBALS['TESTS_FOO_VERSION'])
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());
    }

    public function testDeleteDocument()
    {

    }

    public function testDocumentAndFolderVersions()
    {

    }

    private static function randomString()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
}
