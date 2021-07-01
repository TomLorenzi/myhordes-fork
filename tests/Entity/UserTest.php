<?php declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        $user = new User();
        $user->setName("TestUser");
        $user->setUseICU(true);
        $user->setDisplayName("Test User");

        self::assertEquals("TestUser", $user->getUsername());
        self::assertEquals(true, $user->getUseICU());
        self::assertEquals("Test User", $user->getDisplayName());
    }
}