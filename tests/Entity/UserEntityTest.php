<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserEntityTest extends TestCase
{
    #[Test]
    public function userGettersAndSetters(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('hashed_password');
        $user->setVerifiedAt(new DateTimeImmutable('2026-01-01'));
        $user->setLastLoginAt(new DateTimeImmutable('2026-01-02'));
        $user->setIsActive(true);

        $this->assertNull($user->getId());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertEquals('hashed_password', $user->getPassword());
        $this->assertInstanceOf(DateTimeImmutable::class, $user->getVerifiedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $user->getLastLoginAt());
        $this->assertTrue($user->isActive());
    }

    #[Test]
    public function userDefaultValues(): void
    {
        $user = new User();
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->getVerifiedAt());
        $this->assertNull($user->getLastLoginAt());
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertNull($user->getPassword());
    }

    #[Test]
    public function userNullableDates(): void
    {
        $user = new User();
        $user->setVerifiedAt(null);
        $user->setLastLoginAt(null);
        $this->assertNull($user->getVerifiedAt());
        $this->assertNull($user->getLastLoginAt());
    }
}
