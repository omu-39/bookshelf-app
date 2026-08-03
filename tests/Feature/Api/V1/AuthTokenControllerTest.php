<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_正しい情報でログインAPIにアクセスしてAPIトークンが返る(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $payload= [
            "email"=> $user->email,
            "password" => "password"
        ];

        $this->postJson("/api/v1/login", $payload)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name',
                    'token',
                ],
            ]);
    }

    public function test_ログイン時_emailが空だと422が返る(): void
    {
        User::factory()->create(['password' => bcrypt('password')]);
        $payload= [
            "email"=> "",
            "password" => "password"
        ];

        $this->postJson("/api/v1/login", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_ログイン時_passwordが空だと422が返る(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $payload= [
            "email"=> $user->email,
            "password" => ""
        ];

        $this->postJson("/api/v1/login", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_ログイン時_存在しないユーザーだと401が返る(): void
    {
        $payload= [
            "email"=> "wrong@example.com",
            "password" => "password"
        ];

        $this->postJson("/api/v1/login", $payload)
            ->assertStatus(401);
    }
}