<?php

use PHPUnit\Framework\TestCase;
use Models\User;

require_once __DIR__ . '/../src/models/User.php';

class UserModelTest extends TestCase
{
    public function testUserInstantiation()
    {
        $user = new User(1, 'test@example.com', 'hashed_pass', 'John Doe', 'Developer', 'ADMIN', true);

        $this->assertEquals(1, $user->getId());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('hashed_pass', $user->getPassword());
        $this->assertEquals('John Doe', $user->getFullName());
        $this->assertEquals('Developer', $user->getJobTitle());
        $this->assertEquals('ADMIN', $user->getRole());
        $this->assertTrue($user->isActive());
    }

    public function testUserJsonSerializationOmitsPassword()
    {
        $user = new User(2, 'json@example.com', 'secret123', 'Jane Doe', null, 'EMPLOYEE', false);
        $json = json_encode($user);
        $data = json_decode($json, true);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayNotHasKey('password', $data, 'Password should not be exposed in JSON');
    }
}
