<?php

namespace aliirfaan\LaravelSimpleOtp\Tests\Unit;

use aliirfaan\LaravelSimpleOtp\Tests\TestCase;
use aliirfaan\LaravelSimpleOtp\Services\OtpHelperService;
use aliirfaan\LaravelSimpleOtp\Models\SimpleOtp;
use aliirfaan\LaravelSimpleOtp\Exceptions\OtpNotFoundException;
use aliirfaan\LaravelSimpleOtp\Exceptions\OtpExpiredException;
use aliirfaan\LaravelSimpleOtp\Exceptions\OtpMismatchException;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Mockery;

class OtpHelperServiceTest extends TestCase
{
    private OtpHelperService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OtpHelperService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | generateOtpCode Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_generates_otp_with_default_length(): void
    {
        config(['laravel-simple-otp.otp_length' => 6]);
        
        $otp = $this->service->generateOtpCode();
        
        $this->assertEquals(6, strlen($otp));
    }

    /** @test */
    public function it_generates_otp_with_custom_length(): void
    {
        $otp = $this->service->generateOtpCode(8);
        
        $this->assertEquals(8, strlen($otp));
    }

    /** @test */
    public function it_generates_numeric_otp_without_zero(): void
    {
        config(['laravel-simple-otp.otp_type' => 'numeric']);
        
        // Generate multiple OTPs to increase confidence
        for ($i = 0; $i < 50; $i++) {
            $otp = $this->service->generateOtpCode(6);
            
            $this->assertMatchesRegularExpression('/^[1-9]+$/', $otp);
            $this->assertStringNotContainsString('0', $otp);
        }
    }

    /** @test */
    public function it_generates_alphanumeric_otp_without_ambiguous_chars(): void
    {
        config(['laravel-simple-otp.otp_type' => 'alphanumeric']);
        
        for ($i = 0; $i < 50; $i++) {
            $otp = $this->service->generateOtpCode(8);
            
            $this->assertStringNotContainsString('0', $otp);
            $this->assertStringNotContainsString('O', $otp);
            $this->assertStringNotContainsString('o', $otp);
            $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z1-9]+$/', $otp);
        }
    }

    /** @test */
    public function it_returns_simulated_otp_when_simulation_enabled(): void
    {
        config([
            'laravel-simple-otp.otp_should_simulate' => true,
            'laravel-simple-otp.otp_simulated_code' => '999999',
        ]);
        
        $otp = $this->service->generateOtpCode(6);
        
        $this->assertEquals('999999', $otp);
    }

    /** @test */
    public function it_pads_simulated_otp_if_shorter_than_requested_length(): void
    {
        config([
            'laravel-simple-otp.otp_should_simulate' => true,
            'laravel-simple-otp.otp_simulated_code' => '123',
        ]);
        
        $otp = $this->service->generateOtpCode(6);
        
        $this->assertEquals('123111', $otp);
        $this->assertEquals(6, strlen($otp));
    }

    /** @test */
    public function it_truncates_simulated_otp_if_longer_than_requested_length(): void
    {
        config([
            'laravel-simple-otp.otp_should_simulate' => true,
            'laravel-simple-otp.otp_simulated_code' => '123456789',
        ]);
        
        $otp = $this->service->generateOtpCode(6);
        
        $this->assertEquals('123456', $otp);
    }

    /** @test */
    public function it_throws_exception_for_length_below_minimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OTP length must be between 4 and 12');
        
        $this->service->generateOtpCode(3);
    }

    /** @test */
    public function it_throws_exception_for_length_above_maximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OTP length must be between 4 and 12');
        
        $this->service->generateOtpCode(13);
    }

    /** @test */
    public function it_accepts_minimum_length(): void
    {
        $otp = $this->service->generateOtpCode(4);
        
        $this->assertEquals(4, strlen($otp));
    }

    /** @test */
    public function it_accepts_maximum_length(): void
    {
        $otp = $this->service->generateOtpCode(12);
        
        $this->assertEquals(12, strlen($otp));
    }

    /*
    |--------------------------------------------------------------------------
    | validateOtpCode Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_throws_not_found_exception_when_otp_does_not_exist(): void
    {
        $this->expectException(OtpNotFoundException::class);
        $this->expectExceptionMessage('OTP not found');
        
        $this->service->validateOtpCode([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code' => '123456',
        ]);
    }

    /** @test */
    public function it_throws_not_found_exception_when_otp_already_verified(): void
    {
        // Create a verified OTP
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code_hash' => Hash::make('123456'),
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
            'otp_verified_at' => Carbon::now(), // Already verified
        ]);

        $this->expectException(OtpNotFoundException::class);
        
        $this->service->validateOtpCode([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code' => '123456',
        ]);
    }

    /** @test */
    public function it_throws_expired_exception_when_otp_has_expired(): void
    {
        // Create an expired OTP
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code_hash' => Hash::make('123456'),
            'otp_generated_at' => Carbon::now()->subMinutes(10),
            'otp_expired_at' => Carbon::now()->subMinutes(5), // Already expired
        ]);

        $this->expectException(OtpExpiredException::class);
        $this->expectExceptionMessage('OTP has expired');
        
        $this->service->validateOtpCode([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code' => '123456',
        ]);
    }

    /** @test */
    public function it_throws_mismatch_exception_when_otp_code_is_wrong(): void
    {
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code_hash' => Hash::make('123456'),
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $this->expectException(OtpMismatchException::class);
        $this->expectExceptionMessage('OTP does not match');
        
        $this->service->validateOtpCode([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code' => '999999', // Wrong code
        ]);
    }

    /** @test */
    public function it_validates_otp_successfully(): void
    {
        SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code_hash' => Hash::make('123456'),
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $result = $this->service->validateOtpCode([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code' => '123456',
        ]);
        
        $this->assertTrue($result);
    }

    /** @test */
    public function it_marks_otp_as_verified_after_successful_validation(): void
    {
        $otp = SimpleOtp::create([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code_hash' => Hash::make('123456'),
            'otp_generated_at' => Carbon::now(),
            'otp_expired_at' => Carbon::now()->addMinutes(5),
        ]);

        $this->assertNull($otp->otp_verified_at);

        $this->service->validateOtpCode([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
            'otp_code' => '123456',
        ]);

        $otp->refresh();
        $this->assertNotNull($otp->otp_verified_at);
    }

    /*
    |--------------------------------------------------------------------------
    | persistOtpCode Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_persists_otp_code_to_database(): void
    {
        config(['laravel-simple-otp.otp_timeout_seconds' => 180]);
        
        $result = $this->service->persistOtpCode('123456', [
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
        ]);

        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('expired_at', $result);
        $this->assertArrayHasKey('otp_length', $result);
        $this->assertEquals(6, $result['otp_length']);

        $this->assertDatabaseHas('lso_otps', [
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'email_verification',
            'device_id' => 'device-1',
        ]);
    }

    /** @test */
    public function it_hashes_otp_code_before_storing(): void
    {
        $this->service->persistOtpCode('123456', [
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
        ]);

        $otp = SimpleOtp::first();
        
        $this->assertNotEquals('123456', $otp->otp_code_hash);
        $this->assertTrue(Hash::check('123456', $otp->otp_code_hash));
    }

    /** @test */
    public function it_sets_correct_expiry_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-01-01 12:00:00'));
        config(['laravel-simple-otp.otp_timeout_seconds' => 300]); // 5 minutes
        
        $this->service->persistOtpCode('123456', [
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
        ]);

        $otp = SimpleOtp::first();
        
        $this->assertEquals('2025-01-01 12:00:00', $otp->otp_generated_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-01 12:05:00', $otp->otp_expired_at->format('Y-m-d H:i:s'));
        
        Carbon::setTestNow(); // Reset
    }

    /** @test */
    public function it_stores_optional_meta_data(): void
    {
        $this->service->persistOtpCode('123456', [
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_meta' => ['ip' => '192.168.1.1', 'user_agent' => 'Mozilla'],
        ]);

        $otp = SimpleOtp::first();
        
        $this->assertEquals(['ip' => '192.168.1.1', 'user_agent' => 'Mozilla'], $otp->otp_meta);
    }

    /** @test */
    public function it_stores_correlation_id(): void
    {
        $this->service->persistOtpCode('123456', [
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'correlation_id' => 'corr-abc-123',
        ]);

        $this->assertDatabaseHas('lso_otps', [
            'correlation_id' => 'corr-abc-123',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Dependency Injection Tests
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_accepts_injected_model_for_testing(): void
    {
        $mockModel = Mockery::mock(SimpleOtp::class);
        $mockModel->shouldReceive('getLatestOtp')
            ->once()
            ->andReturn(null);
        
        $service = new OtpHelperService($mockModel);
        
        $this->expectException(OtpNotFoundException::class);
        
        $service->validateOtpCode([
            'actor_id' => 'user-123',
            'actor_type' => 'App\Models\User',
            'otp_intent' => 'test',
            'otp_code' => '123456',
        ]);
    }
}

