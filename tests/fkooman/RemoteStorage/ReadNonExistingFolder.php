<?php

namespace fkooman\RemoteStorage;

class ReadNonExistingFolder extends BaseTest
{
    public function testNonExistingFolder()
    {
        // FIXME in draft-dejong-remotestorage-02 it is also allowed to return
        // a 404 if the folder does not exists...
        $response = $this->client->get(
            sprintf("%s/foo/%s/", $this->storageUri, self::$randomString)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, $response->getHeader("Expires"));
        $this->assertInternalType('string', $response->getHeader("ETag"));

        $this->validateCrossOriginHeaders($response);

        if ("draft-dejong-remotestorage-02" === $this->storageVersion) {
            $this->assertStringStartsWith("application/json", $response->getHeader("Content-Type"));
        } else {
            // draft-dejong-remotestorage-03
            $this->assertEquals("application/ld+json", $response->getHeader("Content-Type"));
        }

        $this->validateFolder($response);
    }
}
