<?php

namespace aliirfaan\LaravelSimpleOtp\Tests\Unit;

use aliirfaan\LaravelSimpleOtp\Tests\TestCase;
use aliirfaan\LaravelSimpleOtp\Models\SimpleOtp;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class SimpleOtpTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | Model Configuration Tests
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $model = new SimpleOtp();
        
        $this->assertEquals('lso_otps', $model->getTable());
    }

    #[Test]
    public function it_has_correct_fillable_attributes(): void
    {
        $model = new SimpleOtp();
        
        $expected = [
            'actor_id',
            'actor_type',
            'device_id',
            'otp_intent',
            'otp_code_hash',
            'otp_generated_at',
            'otp_verified_at',
            'otp_expired_at',
            'correlation_id',
            'otp_meta',
        ];
        
        $this->assertEquals($expected, $model->getFillable());
    }

    #[Test]
    public function it_casts_datetime_fields_correctly(): void
    {
        $otp = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => '2025-01-01 12:00:00',
            'otp_expired_at' => '2025-01-01 12:05:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $otp->otp_generated_at);
        $this->assertInstanceOf(Carbon::class, $otp->otp_expired_at);
    }

    #[Test]
    public function it_casts_otp_meta_as_array(): void
    {
        $otp = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
            'otp_meta' => ['key' => 'value'],
        ]);

        $otp->refresh();
        
        $this->assertIsArray($otp->otp_meta);
        $this->assertEquals(['key' => 'value'], $otp->otp_meta);
    }

    /*
    |--------------------------------------------------------------------------
    | getLatestOtp Tests
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_returns_null_when_no_otp_exists(): void
    {
        $model = new SimpleOtp();
        
        $result = $model->getLatestOtp('user-123', 'App\Models\User', 'test', 'device-1');
        
        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_latest_otp_by_generated_at(): void
    {
        $older = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code_hash' => 'older_hash',
            'otp_generated_at' => Carbon::now()->subMinutes(10),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $newer = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code_hash' => 'newer_hash',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $model = new SimpleOtp();
        $result = $model->getLatestOtp('user-123', 'App\Models\User', 'email_verification', 'device-1');
        
        $this->assertEquals($newer->id, $result->id);
        $this->assertEquals('newer_hash', $result->otp_code_hash);
    }

    #[Test]
    public function it_filters_by_actor_id(): void
    {
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'test',
            'device_id' => 'device-1',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $model = new SimpleOtp();
        
        $result = $model->getLatestOtp('user-999', 'App\Models\User', 'test', 'device-1');
        
        $this->assertNull($result);
    }

    #[Test]
    public function it_filters_by_actor_type(): void
    {
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'test',
            'device_id' => 'device-1',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $model = new SimpleOtp();
        
        $result = $model->getLatestOtp('user-123', 'App\Models\Admin', 'test', 'device-1');
        
        $this->assertNull($result);
    }

    #[Test]
    public function it_filters_by_otp_intent(): void
    {
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $model = new SimpleOtp();
        
        $result = $model->getLatestOtp('user-123', 'App\Models\User', 'password_reset', 'device-1');
        
        $this->assertNull($result);
    }

    #[Test]
    public function it_filters_by_device_id(): void
    {
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'test',
            'device_id' => 'device-1',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $model = new SimpleOtp();
        
        $result = $model->getLatestOtp('user-123', 'App\Models\User', 'test', 'device-999');
        
        $this->assertNull($result);
    }

    /*
    |--------------------------------------------------------------------------
    | markAsVerified Tests
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_marks_otp_as_verified(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 12:00:00'));
        
        $otp = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $this->assertNull($otp->otp_verified_at);

        $model = new SimpleOtp();
        $result = $model->markAsVerified($otp->id);
        
        $this->assertEquals(1, $result);
        
        $otp->refresh();
        $this->assertNotNull($otp->otp_verified_at);
        $this->assertEquals('2025-01-01 12:00:00', $otp->otp_verified_at->format('Y-m-d H:i:s'));
        
        Carbon::setTestNow();
    }

    #[Test]
    public function it_returns_zero_when_marking_nonexistent_otp(): void
    {
        $model = new SimpleOtp();
        
        $result = $model->markAsVerified('nonexistent-id');
        
        $this->assertEquals(0, $result);
    }

    /*
    |--------------------------------------------------------------------------
    | UUID Tests
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_generates_uuid_for_id(): void
    {
        $otp = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $this->assertNotNull($otp->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $otp->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Prunable Tests
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function prunable_returns_empty_query_when_retention_days_is_zero(): void
    {
        config(['laravel-simple-otp.otp_retention_days' => 0]);

        // Create an old expired OTP
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'hash',
            'otp_generated_at' => Carbon::now()->subDays(60),
            'otp_expired_at' => Carbon::now()->subDays(60),
        ]);

        $model = new SimpleOtp();
        $prunable = $model->prunable();

        // Should match nothing when retention is 0
        $this->assertEquals(0, $prunable->count());
    }

    #[Test]
    public function prunable_includes_expired_otps_older_than_retention_period(): void
    {
        config(['laravel-simple-otp.otp_retention_days' => 30]);

        // Old expired OTP (should be pruned)
        $oldExpired = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'old_expired',
            'otp_generated_at' => Carbon::now()->subDays(45),
            'otp_expired_at' => Carbon::now()->subDays(45),
        ]);

        // Recent expired OTP (should NOT be pruned)
        $recentExpired = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'recent_expired',
            'otp_generated_at' => Carbon::now()->subDays(5),
            'otp_expired_at' => Carbon::now()->subDays(5),
        ]);

        $model = new SimpleOtp();
        $prunableIds = $model->prunable()->pluck('id')->toArray();

        $this->assertContains($oldExpired->id, $prunableIds);
        $this->assertNotContains($recentExpired->id, $prunableIds);
    }

    #[Test]
    public function prunable_includes_verified_otps_older_than_retention_period(): void
    {
        config(['laravel-simple-otp.otp_retention_days' => 30]);

        // Old verified OTP (should be pruned)
        $oldVerified = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'old_verified',
            'otp_generated_at' => Carbon::now()->subDays(45),
            'otp_expired_at' => Carbon::now()->subDays(40),
            'otp_verified_at' => Carbon::now()->subDays(45),
        ]);

        // Recent verified OTP (should NOT be pruned)
        $recentVerified = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'recent_verified',
            'otp_generated_at' => Carbon::now()->subDays(5),
            'otp_expired_at' => Carbon::now(),
            'otp_verified_at' => Carbon::now()->subDays(5),
        ]);

        $model = new SimpleOtp();
        $prunableIds = $model->prunable()->pluck('id')->toArray();

        $this->assertContains($oldVerified->id, $prunableIds);
        $this->assertNotContains($recentVerified->id, $prunableIds);
    }

    #[Test]
    public function prunable_does_not_include_active_unexpired_otps(): void
    {
        config(['laravel-simple-otp.otp_retention_days' => 30]);

        // Active OTP (not expired, not verified)
        $activeOtp = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_code_hash' => 'active',
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $model = new SimpleOtp();
        $prunableIds = $model->prunable()->pluck('id')->toArray();

        $this->assertNotContains($activeOtp->id, $prunableIds);
    }
}
