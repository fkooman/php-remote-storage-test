<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ClientTest extends PHPUnit_Framework_TestCase
{
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

    }

    public function testGetExistingDocumentConditional()
    {

    }

    public function testGetExistingFolder()
    {

    }

    public function testGetExistingFolderConditional()
    {

    }

    public function testDeleteDocument()
    {

    }

    public function testDeleteDocumentConditional()
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
