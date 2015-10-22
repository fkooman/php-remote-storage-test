<?php

namespace fkooman\RemoteStorage;

use fkooman\WebFinger\WebFinger;
use PHPUnit_Framework_TestCase;

class WebFingerTest extends PHPUnit_Framework_TestCase
{
    public function testWebFingerFetch()
    {
        $wf = new WebFinger();
        $wf->setOption('verify', $GLOBALS['WEBFINGER_VERIFY_CERT']);
        $wf->setOption('ignore_media_type', $GLOBALS['WEBFINGER_IGNORE_MEDIA_TYPE']);
        $webFingerData = $wf->finger($GLOBALS['WEBFINGER_ID']);

        $this->assertInternalType('string', $webFingerData->getHref('http://tools.ietf.org/id/draft-dejong-remotestorage'));
        // we want an auth URI
        $this->assertNotNull(
            $webFingerData->getProperty(
                'http://tools.ietf.org/id/draft-dejong-remotestorage',
                'http://tools.ietf.org/html/rfc6749#section-4.2'
            )
        );
        // we need a version
        $this->assertNotNull(
            $webFingerData->getProperty(
                'http://tools.ietf.org/id/draft-dejong-remotestorage',
                'http://remotestorage.io/spec/version'
            )
        );
    }
}
