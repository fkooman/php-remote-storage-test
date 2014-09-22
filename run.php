<?php

require_once 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

// -- change this
$baseUrl = 'http://localhost/php-remote-storage/api.php';
$userId = 'admin';
$scope = 'foo:rw';
$apiKey = '12345';
// -- end of change this

try {
    $t = new TestInstance($baseUrl, $userId, $scope);

    $t->testNonExistingFolder();
    $t->testPutDocument();
    $t->testPutExistingDocument();

} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}

class TestInstance
{
    private $moduleName;
    private $userId;
    private $baseUrl;
    private $testFolder;

    public function __construct($baseUrl, $userId, $scope)
    {
        $this->baseUrl = $baseUrl;
        $this->userId = $userId;
        $this->moduleName = explode(":", $scope)[0];
        $this->testFolder = $this->randomString();
    }

    public function testNonExistingFolder()
    {
        $baseDataUrl = $this->baseUrl . '/' . $this->userId . '/' . $this->moduleName . '/' . $this->testFolder . '/';
        //echo sprintf("GET %s", $baseDataUrl) . PHP_EOL;
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
        //echo sprintf("PUT %s", $baseDataUrl) . PHP_EOL;
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
        //echo sprintf("PUT %s", $baseDataUrl) . PHP_EOL;

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

    private function assertEquals($match, $source)
    {
        if ($match != $source) {
            throw new Exception(sprintf("[ERROR] '%s' does not match expected '%s'", $source, $match));
        }
        echo ".";
    }

    private function randomString()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
}
