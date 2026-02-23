<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ID16_01_会員登録後_認証メールが送信される(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        Notification::fake();

        $payload = [
            'name' => '山田 太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $payload);

        // 登録処理後は遷移が302になる
        $response->assertStatus(302);

        $user = User::where('email', 'taro@example.com')->first();
        $this->assertNotNull($user);

        // 認証メール（通知）が送られている
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function ID16_02_メール認証誘導画面で_認証はこちらから_を押下するとメール認証サイトに遷移する(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $response = $this->get('/email/verify');
        $response->assertStatus(200);

        // 文言
        $response->assertSee('登録していただいたメールアドレスに認証メールを送付しました。', false);
        $response->assertSee('メール認証を完了してください。', false);

        // 「認証はこちらから」リンク
        $response->assertSee('認証はこちらから', false);
        $response->assertSee('href="http://localhost:8025"', false);
    }

    /** @test */
    public function ID16_03_メール認証を完了すると_勤怠登録画面に遷移する(): void
    {
        $this->setNow(2026, 2, 23, 9, 0, 0);

        $user = User::factory()->unverified()->create();

        // 署名付き認証URLを生成して踏む
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        // 認証後は勤怠登録画面が開けること
        $this->actingAs($user)
            ->get(route('attendance.index'))
            ->assertStatus(200);
    }
}
