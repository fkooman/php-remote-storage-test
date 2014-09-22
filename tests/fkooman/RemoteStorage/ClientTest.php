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
    }

    public function testNonExistingFolder()
    {
        $baseDataUrl = $this->baseUrl . '/' . $this->userId . '/' . $this->moduleName . '/' . $this->testFolder . '/';
        $client = new Client();
        $response = $client->get($baseDataUrl);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());
        $jsonData = $response->json(array("object"=>true));
        $this->assertEquals("http://remotestorage.io/spec/folder-description", $jsonData->{'@context'});
        $this->assertEquals("object", gettype($jsonData->items));
        $this->assertEquals("application/ld+json", $response->getHeader("Content-Type"));
        $this->assertEquals(0, $response->getHeader("Expires"));

        // make better test, maybe also !
        $this->assertEquals(true, is_string($response->getHeader("ETag")) && strlen($response->getHeader("ETag")) > 0);
    }

    public function testPutDocument()
    {
        $baseDataUrl = $this->baseUrl . '/' . $this->userId . '/' . $this->moduleName . '/' . $this->testFolder . '/' . 'foo';
        $client = new Client();

        $response = $client->put(
            $baseDataUrl,
            array(
                'body' => 'Hello World',
                'headers' => array (
                    'Content-Type' => 'text/plain'
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());

        $this->assertEquals(true, is_string($response->getHeader("ETag")) && strlen($response->getHeader("ETag")) > 0);
    }

    public function testPutExistingDocument()
    {
        // we try to put the same document as before, but as we specify a
        // wrong version so this MUST fail
        $baseDataUrl = $this->baseUrl . '/' . $this->userId . '/' . $this->moduleName . '/' . $this->testFolder . '/' . 'foo';
        try {
            $client = new Client();

            $response = $client->put(
                $baseDataUrl,
                array(
                    'body' => 'Hello World',
                    'headers' => array (
                        'Content-Type' => 'text/plain',
                        'If-Match' => '"wr0ngv3rs10n"'
                    )
                )
            );
            $this->assertEquals(true, false);
        } catch (ClientException $e) {
            $this->assertEquals(412, $e->getResponse()->getStatusCode());
            $this->assertEquals("Precondition Failed", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testPutExistingDocumentIfNonMatchStar()
    {
        // we try to put the same document as before, but as we specify "*" in
        // If-None-Match it should fail
        $baseDataUrl = $this->baseUrl . '/' . $this->userId . '/' . $this->moduleName . '/' . $this->testFolder . '/' . 'foo';
        try {
            $client = new Client();

            $response = $client->put(
                $baseDataUrl,
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

    private static function randomString()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
}
