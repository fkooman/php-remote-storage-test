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

#    private function validateLinkRelation($linkRelation)
#    {
#        // additional checks for remotestorage link relation
#        if ("remotestorage" === $linkRelation) {
#            // needs href
#            if (null === $this->getHref($linkRelation)) {
#                throw new WebFingerException("remotestorage needs 'href'");
#            }
#            // needs properties
#            $properties = $this->getProperties($linkRelation);
#            if (null === $properties) {
#                throw new WebFingerException("remotestorage needs 'properties'");
#            }

#            // needs authUri property
#            $this->requireStringKeyValue($properties, 'http://tools.ietf.org/html/rfc6749#section-4.2', true);
#            $this->requireUri($properties['http://tools.ietf.org/html/rfc6749#section-4.2']);

#            // needs version property
#            $this->requireStringKeyValue($properties, 'http://remotestorage.io/spec/version', true);

#            // optional properties
#            if (null !== $this->requireStringKeyValue($properties, 'https://tools.ietf.org/html/rfc2616#section-14.16')) {
#                if (!in_array($properties['https://tools.ietf.org/html/rfc2616#section-14.16'], array("true", "false"))) {
#                    throw new WebFingerException("'property needs to be 'true' or 'false' as string");
#                }
#            }
#            if (null !== $this->requireStringKeyValue($properties, 'http://tools.ietf.org/html/rfc6750#section-2.3')) {
#                if (!in_array($properties['http://tools.ietf.org/html/rfc6750#section-2.3'], array("true", "false"))) {
#                    throw new WebFingerException("'property needs to be 'true' or 'false' as string");
#                }
#            }
#        }
#    }
