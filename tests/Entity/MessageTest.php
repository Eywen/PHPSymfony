<?php

namespace App\Tests\Entity;

use App\Entity\Message;
use Exception;
use Faker\Factory as FakerFactoryAlias;
use Faker\Generator as FakerGeneratorAlias;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageTest
 *
 * @package App\Tests\Entity
 * @group   entities
 *
 * @coversDefaultClass \App\Entity\Message
 */
class MessageTest extends TestCase
{
    protected static Message $message;

    private static FakerGeneratorAlias $faker;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public static function setupBeforeClass(): void
    {
        self::$message = new Message();
        self::$faker = FakerFactoryAlias::create('es_ES');
    }

    /**
     * Implement testConstructor
     *
     * @return void
     */
    public function testConstructor(): void
    {
        $num = self::$faker->randomNumber();
        $text = self::$faker->word();
        $lmessage = new Message($num, $text);
        self::assertSame($num, $lmessage->getCode());
        self::assertSame($text, $lmessage->getMessage());
    }

    /**
     * Implement testGetSetCode().
     *
     * @throws Exception
     * @return void
     */
    public function testGetSetCode(): void
    {
        self::assertSame(200, self::$message->getCode());
        $code = self::$faker->numberBetween(0, 1000);
        self::$message->setCode($code);
        self::assertSame($code, self::$message->getCode());
    }

    /**
     * Implement testGetSetMessage().
     *
     * @throws Exception
     * @return void
     */
    public function testGetSetMessage(): void
    {
        self::assertEmpty(self::$message->getMessage());
        self::$message->setMessage(null);
        self::assertNull(self::$message->getMessage());
        $msg = self::$faker->slug();
        self::$message->setMessage($msg);
        self::assertSame($msg, self::$message->getMessage());
    }
}
