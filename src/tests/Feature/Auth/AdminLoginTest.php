<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function ID3_01_メールアドレス未入力の場合はバリデーションメッセージが表示される(): void
    {
        $this->createAdmin();

        $response = $this->post(route('login.store'), [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** @test */
    public function ID3_02_パスワード未入力の場合はバリデーションメッセージが表示される(): void
    {
        $this->createAdmin();

        $response = $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** @test */
    public function ID3_03_登録内容と一致しない場合はバリデーションメッセージが表示される(): void
    {
        $this->createAdmin();

        $response = $this->from(route('admin.login'))->post(route('login.store'), [
            'email' => 'wrong-admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }
}
