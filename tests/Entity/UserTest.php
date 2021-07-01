<?php declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use DateTime;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        $user = new User();
        $user->setName("TestUser");

        $user->setUseICU(true);
        $now = new DateTime();
        $user->setLastNameChange($now);
        $user->setNameHistory(null);

        self::assertEquals("TestUser", $user->getName());
        $user->setDisplayName("Test User");
        self::assertEquals("TestUser", $user->getUsername());
        self::assertEquals("Test User", $user->getName());

        self::assertEquals(true, $user->getUseICU());
        self::assertEquals("Test User", $user->getDisplayName());
        self::assertEquals($now, $user->getLastNameChange());
        self::assertEquals(null, $user->getNameHistory());
    }
}