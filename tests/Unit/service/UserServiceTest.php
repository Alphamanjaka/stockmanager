<?php

namespace Tests\Feature\service;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService();
    }

    /** @test */
    public function it_can_get_all_users_paginated()
    {
        User::factory()->count(20)->create();

        $result = $this->service->getAllUsers();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items());
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'back_office'
        ];

        $user = $this->service->create($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /** @test */
    public function it_can_get_a_user_by_id()
    {
        $createdUser = User::factory()->create();

        $foundUser = $this->service->getUserById($createdUser->id);

        $this->assertEquals($createdUser->id, $foundUser->id);
    }

    /** @test */
    public function it_throws_exception_for_non_existent_user()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->getUserById(999);
    }

    /** @test */
    public function it_can_update_a_user()
    {
        $user = User::factory()->create();
        $newData = ['name' => 'Updated Name', 'email' => 'updated@example.com'];

        $this->service->update($user, $newData);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function it_can_update_a_user_password()
    {
        $user = User::factory()->create();
        $newData = ['password' => 'new-secret-password'];

        $updatedUser = $this->service->update($user, $newData);

        $this->assertTrue(Hash::check('new-secret-password', $updatedUser->password));
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();

        $this->service->delete($user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
