<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_会員登録画面を表示できる(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_正しい情報で会員登録ができる(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'example@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('books.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'example@gmail.com',
        ]);
    }

        public function test_会員登録時_名前が空だとバリデーションエラーになる(): void
    {
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'example@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    public function test_会員登録時_emailが空だとバリデーションエラーになる(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_会員登録時_emailが不正な形式だとバリデーションエラーになる(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_会員登録時_passwordが空だとバリデーションエラーになる(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'example@gmail.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_会員登録時_passwordが8文字未満だとバリデーションエラーになる(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'example@gmail.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_会員登録時_passwordとpassword_confirmationが一致しないとバリデーションエラーになる(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'example@gmail.com',
            'password' => 'short',
            'password_confirmation' => 'wrong',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_ログイン画面を表示できる(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_正しい情報でログインできる(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('books.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_間違ったパスワードではログインできない(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_存在しないメールアドレスではログインできない(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_メールアドレスが空だとバリデーションエラーになる(): void
    {
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_パスワードが空だとバリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_ログアウトできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_認証済みユーザーはログインページにアクセスするとリダイレクトされる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))
            ->assertRedirect(route('books.index'));
    }
}
