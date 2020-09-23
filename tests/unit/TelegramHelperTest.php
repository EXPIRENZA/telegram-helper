<?php
require './src/TelegramHelper.php';

use Expirenza\src\TelegramHelper;

class TelegramHelperTest extends PHPUnit\Framework\TestCase
{
    public function testApiKeyNotSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $telegramHelper = new TelegramHelper(null);
    }

    public function testApiKeySet(): void
    {
        $this->assertInstanceOf(
            TelegramHelper::class,
            new TelegramHelper('test key')
        );
    }

    public function testGetHashByPhone(): void
    {
        $telegramHelper = new TelegramHelper('test key');
        $this->assertFalse($telegramHelper->getHashByPhone('+10000000000'));
    }

    public function testSendInDebug(): void
    {
        $telegramHelper = new TelegramHelper('test key');
        $telegramHelper->addMessage('some length text', '+10000000000');

        $this->assertEquals($telegramHelper->send(), [
            'success' => false
        ]);
    }
}
