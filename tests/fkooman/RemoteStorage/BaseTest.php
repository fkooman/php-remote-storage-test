<?php

namespace fkooman\RemoteStorage;

use GuzzleHttp\Client;
use fkooman\WebFinger\WebFinger;
use PHPUnit_Framework_TestCase;

class BaseTest extends PHPUnit_Framework_TestCase
{
    /** @var string */
    protected static $randomString;

    /** @var GuzzleHttp\Client */
    protected $client;

    /** @var string */
    protected $storageUri;

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
        $this->storageUri = $webFingerData->getHref('remotestorage');
        $this->storageVersion = $webFingerData->getProperty('remotestorage', 'http://remotestorage.io/spec/version');

        $this->client = new Client(
            array(
                'defaults' => array(
                    'headers' => array(
                        'Authorization' => sprintf("Bearer %s", $GLOBALS['RS_TOKEN']),
                        'Origin' => 'https://app.example.org'
                    ),
                    'verify' => $GLOBALS['RS_VERIFY_CERT']
                )
            )
        );
    }

    public function validateFolder($response)
    {
        $folderData = $response->json(
            array(
                "object" => true
            )
        );

        // FIXME: @context and items are not required by spec, so '{}' is also
        // allowed according to spec, we read them as MUST though
        $this->assertInternalType('object', $folderData);
        $this->assertObjectHasAttribute('@context', $folderData);
        $this->assertEquals('http://remotestorage.io/spec/folder-description', $folderData->{'@context'});
        $this->assertObjectHasAttribute('items', $folderData);
        $this->assertInternalType('object', $folderData->items);
        foreach ($folderData->items as $k => $v) {
            $this->assertInternalType('string', $k);
            $this->assertObjectHasAttribute('ETag', $v);
            if (false === strpos($k, '/')) {
                // node is document
                $this->assertObjectHasAttribute('Content-Type', $v);
                $this->assertObjectHasAttribute('Content-Length', $v);
            }
        }
    }

    public function validateCrossOriginHeaders($response)
    {
        // the origin should be either * or 'https://app.example.org' for
        // GET requests, and MUST be 'https://app.example.org' for PUT and
        // DELETE requests
        $this->assertContains(
            $response->getHeader("Access-Control-Allow-Origin"),
            array("*", "https://app.example.org")
        );

        # Simple Response Headers
        #    Cache-Control
        #    Content-Language
        #    Content-Type
        #    Expires
        #    Last-Modified
        #    Pragma

        // so only ETag and Content-Length need to be "exposed"
        $this->assertContains("ETag", $response->getHeader("Access-Control-Expose-Headers"), '', true);
        $this->assertContains("Content-Length", $response->getHeader("Access-Control-Expose-Headers"), '', true);
    }

    protected static function randomString()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }

    public function testDummy()
    {
        // included to satisfy PHPUnit
        $this->assertTrue(true);
    }
}
