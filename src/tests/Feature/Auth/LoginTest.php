<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function ID2_01_メールアドレス未入力の場合はバリデーションメッセージが表示される(): void
    {
        $this->createUser();

        $response = $this->post(route('login.store'), [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** @test */
    public function ID2_02_パスワード未入力の場合はバリデーションメッセージが表示される(): void
    {
        $this->createUser();

        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** @test */
    public function ID2_03_登録内容と一致しない場合はバリデーションメッセージが表示される(): void
    {
        $this->createUser();

        // 直前ページをloginにしておく
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'ログイン情報が登録されていません']);
    }
}
