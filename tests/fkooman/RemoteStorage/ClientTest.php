<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use fkooman\WebFinger\WebFinger;

class ClientTest extends PHPUnit_Framework_TestCase
{

    // global variables used to store the versions of folders and documents
    // for use in later tests...
    protected $backupGlobalsBlacklist = array(
        'TESTS_FOO_VERSION',
        'TESTS_TEST_FOLDER_VERSION'
    );

    const SCOPE_RW = 'foo:rw';
    const SCOPE_R = 'bar:r';

    private $userId;
    private $moduleName;
    private $testFolder;
    private $baseDataUrl;
    private $baseDataUrlOtherUser;
    private $baseDataUrlOtherModule;
    private $clientRw;
    private $clientR;
    private $clientPublic;

    protected static $randomString;

    public static function setUpBeforeClass()
    {
        self::$randomString = self::randomString();
    }

    public function setUp()
    {
        $wf = new WebFinger(
            array(
                "verify" => $GLOBALS['WEBFINGER_VERIFY_CERT'],
                "ignore_media_type" => $GLOBALS['WEBFINGER_IGNORE_MEDIA_TYPE']
            )
        );

        $webFingerData = $wf->finger($GLOBALS['WEBFINGER_ID']);
        $baseUrl = $webFingerData->getHref('remotestorage');

        //$this->userId = $GLOBALS['RS_USER_ID'];
        $this->moduleNameRw = explode(":", self::SCOPE_RW)[0];
        $this->moduleNameR = explode(":", self::SCOPE_R)[0];
        $this->testFolder = self::$randomString;
        $this->baseDataUrlRw = sprintf('%s/%s/%s/', $baseUrl, $this->moduleNameRw, $this->testFolder);
        $this->baseDataUrlR = sprintf('%s/%s/%s/', $baseUrl, $this->moduleNameR, $this->testFolder);
        $this->baseDataUrlPublic = sprintf('%s/%s/%s/%s/', $baseUrl, "public", $this->moduleNameRw, $this->testFolder);
#        $this->baseDataUrlOtherUserRw = sprintf('%s/%s/%s/%s/', $GLOBALS['RS_BASE_URL'], 'wronguser', $this->moduleNameRw, $this->testFolder);
        $this->baseDataUrlOtherModuleRw = sprintf('%s/%s/%s/', $baseUrl, 'othermodule', $this->testFolder);

        $this->clientRw = new Client(
            array(
                'defaults' => array(
                    'headers' => array(
                        'Authorization' => sprintf("Bearer %s", $GLOBALS['RS_TOKEN']),
                        'Origin' => 'http://www.example.org/foo'
                    ),
                    'verify' => $GLOBALS['WEBFINGER_VERIFY_CERT']
                )
            )
        );
#        $this->clientR = new Client(
#            array(
#                'defaults' => array(
#                    'headers' => array(
#                        'Authorization' => sprintf("Bearer %s", $GLOBALS['RS_TOKEN']),
#                        'Origin' => 'http://www.example.org/foo'
#                    )
#                )
#            )
#        );
        $this->clientPublic = new Client(
            array(
                'defaults' => array(
                    'headers' => array(
                        'Origin' => 'http://www.example.org/foo'
                    ),
                    'verify' => $GLOBALS['WEBFINGER_VERIFY_CERT']
                )
            )
        );
    }

    public function testNonExistingFolder()
    {
        $response = $this->clientRw->get($this->baseDataUrlRw);

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
        $response = $this->clientRw->put(
            $this->baseDataUrlRw . 'foo',
            array(
                'body' => 'Hello World',
                'headers' => array (
                    'Content-Type' => 'text/plain'
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());
        $this->assertTrue(is_string($response->getHeader("ETag")) && strlen($response->getHeader("ETag")) > 0);
        $GLOBALS['TESTS_FOO_VERSION'] = explode('"', $response->getHeader("ETag"))[1];
    }

    public function testPutExistingDocument()
    {
        // we try to put the same document as before, but as we specify a
        // wrong version so this MUST fail
        try {
            $response = $this->clientRw->put(
                $this->baseDataUrlRw . 'foo',
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
            $response = $this->clientRw->put(
                $this->baseDataUrlRw . 'foo',
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
        $response = $this->clientRw->get(
            $this->baseDataUrlRw . 'foo'
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
            $response = $this->clientRw->get(
                $this->baseDataUrlRw . 'bar'
            );
        } catch (ClientException $e) {
            $this->assertEquals(404, $e->getResponse()->getStatusCode());
            $this->assertEquals("Not Found", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testGetExistingDocumentConditional()
    {
        $response = $this->clientRw->get(
            $this->baseDataUrlRw . 'foo',
            array(
                'headers' => array (
                    'If-None-Match' => sprintf('"%s"', $GLOBALS['TESTS_FOO_VERSION'])
                )
            )
        );
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals("Not Modified", $response->getReasonPhrase());
    }

    public function testGetExistingFolder()
    {
        $response = $this->clientRw->get($this->baseDataUrlRw);

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
        $response = $this->clientRw->get(
            $this->baseDataUrlRw,
            array(
                'headers' => array (
                    'If-None-Match' => sprintf('"%s"', $GLOBALS['TESTS_TEST_FOLDER_VERSION'])
                )
            )
        );
        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals("Not Modified", $response->getReasonPhrase());
    }

    public function testDeleteDocumentWrongConditional()
    {
        try {
            $response = $this->clientRw->delete(
                $this->baseDataUrlRw . 'foo',
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
        $response = $this->clientRw->delete(
            $this->baseDataUrlRw . 'foo',
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
            $response = $this->clientRw->delete(
                $this->baseDataUrlRw . 'foo',
                array(
                    'headers' => array(
                    )
                )
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
        $response = $this->clientRw->put(
            $this->baseDataUrlRw . 'file_stays_here',
            array(
                'body' => 'Hello World',
                'headers' => array (
                    'Content-Type' => 'text/plain'
                )
            )
        );

        $folderVersion = array();
        $folderVersion[] = $this->clientRw->get($this->baseDataUrlRw)->getHeader("ETag");

        foreach (array("foo", "bar", "baz", "sub/foo", "sub/bar", "sub/baz", "level1/level2/level3/level4") as $f) {
            $response = $this->clientRw->put(
                $this->baseDataUrlRw . $f,
                array(
                    'body' => 'Hello World',
                    'headers' => array (
                        'Content-Type' => 'text/plain'
                    )
                )
            );
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertNotContains($this->clientRw->get($this->baseDataUrlRw)->getHeader("ETag"), $folderVersion);
            $folderVersion[] = $this->clientRw->get($this->baseDataUrlRw)->getHeader("ETag");

            $response = $this->clientRw->delete(
                $this->baseDataUrlRw . $f,
                array(
                    'headers' => array(
                    )
                )
            );
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertNotContains($this->clientRw->get($this->baseDataUrlRw)->getHeader("ETag"), $folderVersion);
            $folderVersion[] = $this->clientRw->get($this->baseDataUrlRw)->getHeader("ETag");
        }
    }

#    public function testPutDocumentInOtherUserFolder()
#    {
#        try {
#            $response = $this->clientRw->put(
#                $this->baseDataUrlOtherUserRw . 'foo',
#                array(
#                    'body' => 'Hello World',
#                    'headers' => array (
#                        'Content-Type' => 'text/plain'
#                    )
#                )
#            );
#            $this->assertTrue(false);
#        } catch (ClientException $e) {
#            // FIXME: should this be 403?
#            $this->assertEquals(401, $e->getResponse()->getStatusCode());
#            $this->assertEquals("Unauthorized", $e->getResponse()->getReasonPhrase());
#        }
#    }

    public function testPutDocumentInOtherModuleFolder()
    {
        try {
            $response = $this->clientRw->put(
                $this->baseDataUrlOtherModuleRw . 'foo',
                array(
                    'body' => 'Hello World',
                    'headers' => array (
                        'Content-Type' => 'text/plain'
                    )
                )
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            // FIXME: should this be 403?
            $this->assertEquals(401, $e->getResponse()->getStatusCode());
            $this->assertEquals("Unauthorized", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testPutDocumentToFolderName()
    {
        $response = $this->clientRw->put(
            $this->baseDataUrlRw . 'foo/bar',
            array(
                'body' => 'Hello World',
                'headers' => array (
                    'Content-Type' => 'text/plain'
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());

        try {
            $response = $this->clientRw->put(
                $this->baseDataUrlRw . 'foo',
                array(
                    'body' => 'Hello World',
                    'headers' => array (
                        'Content-Type' => 'text/plain'
                    )
                )
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            $this->assertEquals(409, $e->getResponse()->getStatusCode());
            $this->assertEquals("Conflict", $e->getResponse()->getReasonPhrase());
        }
    }

    public function testPutFolderToDocumentName()
    {
        $response = $this->clientRw->put(
            $this->baseDataUrlRw . 'foo/bar',
            array(
                'body' => 'Hello World',
                'headers' => array (
                    'Content-Type' => 'text/plain'
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());

        try {
            $response = $this->clientRw->put(
                $this->baseDataUrlRw . 'foo/bar/baz',
                array(
                    'body' => 'Hello World',
                    'headers' => array (
                        'Content-Type' => 'text/plain'
                    )
                )
            );
            $this->assertTrue(false);
        } catch (ClientException $e) {
            $this->assertEquals(409, $e->getResponse()->getStatusCode());
            $this->assertEquals("Conflict", $e->getResponse()->getReasonPhrase());
        }
    }
    public function testCreateFolderWhereDocumentExists()
    {

    }

    public function testWritingWithOnlyReadScope()
    {
    }

    public function testHeadRequest()
    {

    }

    public function testOptionsRequest()
    {
        // preflight check
        $response = $this->clientPublic->options(
            $this->baseDataUrlOtherModuleRw . 'foo',
            array(
                'headers' => array (
                )
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());
        // FIXME: also accept * as respose Origin
        $this->assertEquals("http://www.example.org/foo", $response->getHeader("Access-Control-Allow-Origin"));
        // FIXME: also accept different order and maybe without HEAD and OPTIONS?
        $this->assertEquals("GET, PUT, DELETE, HEAD, OPTIONS", $response->getHeader("Access-Control-Allow-Methods"));
        // FIXME: also accept different order and maybe without some of the headers?
        $this->assertEquals("Authorization, Content-Length, Content-Type, Origin, X-Requested-With, If-Match, If-None-Match", $response->getHeader("Access-Control-Allow-Headers"));
    }

#    public function testDeletingWithOnlyReadScope()
#    {
#    }

#    public function testGetPublicFolderListingWithoutCredentials()
#    {
#        try {
#            $response = $this->clientPublic->get(
#                $this->baseDataUrlPublic,
#                array(
#                    'headers' => array (
#                    )
#                )
#            );
#            $this->assertTrue(false);
#        } catch (ClientException $e) {
#            // FIXME: should this be 403?
#            $this->assertEquals(401, $e->getResponse()->getStatusCode());
#            $this->assertEquals("Unauthorized", $e->getResponse()->getReasonPhrase());
#        }
#    }

    private static function randomString()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
}
