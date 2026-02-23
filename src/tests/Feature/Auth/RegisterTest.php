<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $override);
    }

    /** @test */
    public function ID1_01_名前未入力の場合はバリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload(['name' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください']);
    }

    /** @test */
    public function ID1_02_メールアドレス未入力の場合はバリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload(['email' => '']));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください']);
    }

    /** @test */
    public function ID1_03_パスワードが8文字未満の場合はバリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください']);
    }

    /** @test */
    public function ID1_04_パスワード確認が一致しない場合はバリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'password_confirmation' => 'different-password',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません']);
    }

    /** @test */
    public function ID1_05_パスワード未入力の場合はバリデーションメッセージが表示される(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload([
            'password' => '',
            'password_confirmation' => '',
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください']);
    }

    /** @test */
    public function ID1_06_フォームに内容が入力されていた場合はデータが正常に保存される(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'テスト太郎',
        ]);
    }
}
