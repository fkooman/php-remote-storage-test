<?php

use fkooman\WebFinger\WebFinger;

class WebFingerTest extends PHPUnit_Framework_TestCase
{
    public function testWebFingerFetch()
    {
        $wf = new WebFinger(
            array(
                "verify" => $GLOBALS['WEBFINGER_VERIFY_CERT'],
                "ignore_media_type" => $GLOBALS['WEBFINGER_IGNORE_MEDIA_TYPE']
            )
        );

        $webFingerData = $wf->finger($GLOBALS['WEBFINGER_ID']);

        $this->assertInternalType('string', $webFingerData->getHref('remotestorage'));
        // we want an auth URI
        $this->assertNotNull($webFingerData->getProperty('remotestorage', 'http://tools.ietf.org/html/rfc6749#section-4.2'));
        // we need a version
        $this->assertNotNull($webFingerData->getProperty('remotestorage', 'http://remotestorage.io/spec/version'));
    }
}
