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

    private $userId;
    private $moduleName;
    private $testFolder;
    private $baseDataUrl;
    private $client;

    protected static $randomString;

    public static function setUpBeforeClass()
    {
        self::$randomString = self::randomString();
    }

    public function setUp()
    {
        $this->userId = $GLOBALS['RS_USER_ID'];
        $this->moduleName = explode(":", $GLOBALS['RS_SCOPE'])[0];
        $this->testFolder = self::$randomString;
        $this->baseDataUrl = sprintf('%s/%s/%s/%s/', $GLOBALS['RS_BASE_URL'], $this->userId, $this->moduleName, $this->testFolder);
        $this->client = new Client(
            array(
                'defaults' => array(
                    'headers' => array(
                        'Authorization' => sprintf("Bearer %s", $GLOBALS['RS_AUTH_TOKEN'])
                    )
                )
            )
        );
    }

    public function testNonExistingFolder()
    {
        $response = $this->client->get($this->baseDataUrl);

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
        $response = $this->client->put(
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
            $response = $this->client->put(
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
            $response = $this->client->put(
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
        $response = $this->client->get(
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
            $response = $this->client->get(
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
            $response = $this->client->get(
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
        $response = $this->client->get($this->baseDataUrl);

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
            $response = $this->client->get(
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
            $response = $this->client->delete(
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
        $response = $this->client->delete(
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

    public function testDeleteDocumentNotExisting()
    {
        try {
            $response = $this->client->delete(
                $this->baseDataUrl . 'foo'
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
            $this->assertEquals("Not Found", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testPutDeleteVersionUpdates()
    {
        // every put and delete should update the version of the folder
        // where the files are in (up to the root, try with some files)
        $response = $this->client->put(
            $this->baseDataUrl . 'file_stays_here',
            array(
                'body' => 'Hello World',
                'headers' => array (
                    'Content-Type' => 'text/plain'
                )
            )
        );

        $folderVersion = array();
        $folderVersion[] = $this->client->get($this->baseDataUrl)->getHeader("ETag");

        foreach (array("foo", "bar", "baz", "sub/foo", "sub/bar", "sub/baz", "level1/level2/level3/level4") as $f) {
            $response = $this->client->put(
                $this->baseDataUrl . $f,
                array(
                    'body' => 'Hello World',
                    'headers' => array (
                        'Content-Type' => 'text/plain'
                    )
                )
            );
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertNotContains($this->client->get($this->baseDataUrl)->getHeader("ETag"), $folderVersion);
            $folderVersion[] = $this->client->get($this->baseDataUrl)->getHeader("ETag");

            $response = $this->client->delete($this->baseDataUrl . $f);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertNotContains($this->client->get($this->baseDataUrl)->getHeader("ETag"), $folderVersion);
            $folderVersion[] = $this->client->get($this->baseDataUrl)->getHeader("ETag");
        }
    }

    private static function randomString()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
}
