<?php

use tempsmsru\api\auth\HashBuilder;

class HashGeneratorTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $appId = 1;
    protected $secret = '21b6acaced7292eb7d28ac20cf14049ef19318405c6a19cdb755f38fd9df9cf6';

    protected $apiHost = 'http://tempsms.ru/api';

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testIncorrectHash()
    {
        $expected_hash = 'incorrect_hash';
        $this->assertNotEquals($expected_hash, HashBuilder::build(['app_id' => $this->appId], $this->secret));
    }

    public function testCorrectHash()
    {
        $expected_hash = '181de8b0f26ee8866c0f8660c7427c74042abcc928e31f60ec7ead3f07c88db9';
        $this->assertEquals($expected_hash, HashBuilder::build(['app_id' => $this->appId], $this->secret));
    }

}