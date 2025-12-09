<?php

namespace aliirfaan\LaravelSimpleOtp\Services;

use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use aliirfaan\LaravelSimpleOtp\Exceptions\OtpExpiredException;
use aliirfaan\LaravelSimpleOtp\Exceptions\OtpNotFoundException;
use aliirfaan\LaravelSimpleOtp\Exceptions\OtpMismatchException;
use aliirfaan\LaravelSimpleOtp\Models\SimpleOtp;

/**
 * OTP helper service
 *
 * Helper class to generate and verify OTP codes
 */
class OtpHelperService
{
    /**
     * Minimum allowed OTP length.
     */
    private const MIN_OTP_LENGTH = 4;

    /**
     * Maximum allowed OTP length.
     */
    private const MAX_OTP_LENGTH = 12;

    /**
     * Character set for numeric OTPs (excludes 0 to avoid confusion).
     */
    private const NUMERIC_CHARSET = '123456789';

    /**
     * Character set for alphanumeric OTPs (excludes 0, O, o for readability).
     */
    private const ALPHANUMERIC_CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';

    /**
     * otpModel
     *
     * @var SimpleOtp
     */
    private $otpModel;

    public function __construct(?SimpleOtp $otpModel = null)
    {
        $this->otpModel = $otpModel ?? new SimpleOtp();
    }

    /**
     * Generate a cryptographically secure OTP code.
     *
     * Supports two OTP types configured via 'laravel-simple-otp.otp_type':
     * - 'numeric': Digits 1-9 only (no zero to avoid confusion)
     * - 'alphanumeric': Uppercase letters A-Z (excluding O) and digits 1-9
     *
     * Security considerations:
     * - Uses random_int() for cryptographic randomness
     * - Excludes ambiguous characters (0, O, o) to prevent user confusion
     * - Simulation mode available for testing (must be disabled in production)
     *
     * @param  int|null $otpCodeLength Length of OTP to generate (default from config)
     * @return string Generated OTP code
     *
     * @throws \InvalidArgumentException If length is outside allowed bounds
     * @throws \Exception If random_int() fails (insufficient entropy)
     */
    public function generateOtpCode(?int $otpCodeLength = null): string
    {
        $length = $this->resolveOtpLength($otpCodeLength);

        // Simulation mode for testing environments
        if ($this->isSimulationEnabled()) {
            return $this->getSimulatedOtpCode($length);
        }

        return $this->isNumericType()
            ? $this->generateSecureCode(self::NUMERIC_CHARSET, $length)
            : $this->generateSecureCode(self::ALPHANUMERIC_CHARSET, $length);
    }

    /**
     * Resolve and validate OTP length.
     *
     * @param  int|null $length Requested length or null for default
     * @return int Validated length
     *
     * @throws \InvalidArgumentException If length is outside bounds
     */
    private function resolveOtpLength(?int $length): int
    {
        $length ??= (int) config('laravel-simple-otp.otp_length', 6);

        if ($length < self::MIN_OTP_LENGTH || $length > self::MAX_OTP_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('OTP length must be between %d and %d.', self::MIN_OTP_LENGTH, self::MAX_OTP_LENGTH)
            );
        }

        return $length;
    }

    /**
     * Check if simulation mode is enabled.
     *
     * @return bool
     */
    private function isSimulationEnabled(): bool
    {
        return (bool) config('laravel-simple-otp.otp_should_simulate', false);
    }

    /**
     * Check if OTP type is numeric.
     *
     * @return bool
     */
    private function isNumericType(): bool
    {
        return config('laravel-simple-otp.otp_type', 'numeric') === 'numeric';
    }

    /**
     * Get simulated OTP code for testing.
     *
     * @param  int $length Desired OTP length
     * @return string Simulated OTP code
     */
    private function getSimulatedOtpCode(int $length): string
    {
        $simulatedCode = (string) config('laravel-simple-otp.otp_simulated_code', '');

        // Pad or truncate to match requested length
        if (strlen($simulatedCode) < $length) {
            return str_pad($simulatedCode, $length, '1', STR_PAD_RIGHT);
        }

        return substr($simulatedCode, 0, $length);
    }

    /**
     * Generate a cryptographically secure code from the given charset.
     *
     * Uses random_int() which is CSPRNG-backed and suitable for security-sensitive applications.
     *
     * @param  string $charset Character set to use
     * @param  int    $length  Desired code length
     * @return string Generated code
     *
     * @throws \Exception If random_int() fails
     */
    private function generateSecureCode(string $charset, int $length): string
    {
        $charsetLength = strlen($charset);
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $charset[random_int(0, $charsetLength - 1)];
        }

        return $code;
    }

    /**
     * Validate a submitted OTP code for an actor/intent flow.
     *
     * Verification flow:
     * 1. Look up latest OTP for actor/intent/device
     * 2. check if OTP is expired
     * 3. check if OTP is verified
     * 4. Verify the submitted code against the stored hash
     * 5. Mark OTP as verified on success
     *
     * @param  array $validateData {
     *     @type string $actor_id    Actor identifier (required)
     *     @type string $actor_type  Actor type (required)
     *     @type string $otp_intent  OTP intent/purpose (required)
     *     @type string $device_id   Device identifier (optional)
     *     @type string $otp_code    Submitted OTP code to validate (required)
     * }
     * @return bool
     *
     * @throws OtpNotFoundException  If no OTP exists for the given criteria
     * @throws OtpExpiredException   If the OTP exists but has expired
     * @throws OtpMismatchException  If the submitted code does not match
     */
    public function validateOtpCode(array $validateData): bool
    {
        $actorId = $validateData['actor_id'] ?? null;
        $actorType = $validateData['actor_type'] ?? null;
        $otpIntent = $validateData['otp_intent'] ?? null;
        $deviceId = $validateData['device_id'] ?? null;
        $otpCode = $validateData['otp_code'] ?? null;

        // Get latest OTP for actor/intent/device
        $latestOtp = $this->otpModel->getLatestOtp($actorId, $actorType, $otpIntent, $deviceId);
        $now = Carbon::now();

        if ($latestOtp === null) {
            throw new OtpNotFoundException('OTP not found');
        }

        // Check if already verified
        if ($latestOtp->otp_verified_at !== null) {
            throw new OtpNotFoundException('OTP not found');
        }

        // Check if expired
        if ($latestOtp->otp_expired_at !== null && $latestOtp->otp_expired_at <= $now) {
            throw new OtpExpiredException('OTP has expired');
        }

        // Verify submitted code against stored hash
        if (!Hash::check($otpCode, $latestOtp->otp_code_hash)) {
            throw new OtpMismatchException('OTP does not match');
        }

        // Mark as verified
        $this->otpModel->markAsVerified($latestOtp->id);

        return true;
    }

    /**
     * Persist OTP code in the database
     * 
     * Calculate otp_expired_at based on otp_timeout_seconds
     * Hash code
     * Return expired_at and otp length
     *
     * @param  string $otpCode OTP code
     * @param  array $otpData OTP data
     * 
     * @return array
     */
    public function persistOtpCode(string $otpCode, array $otpData): array
    {
        $actorId = $otpData['actor_id'];
        $actorType = $otpData['actor_type'] ?? null;
        $deviceId = $otpData['device_id'] ?? null;
        $otpIntent = $otpData['otp_intent'] ?? null;
        $correlationId = $otpData['correlation_id'] ?? null;
        $otpMeta = $otpData['otp_meta'] ?? null;

        $otpCodeHash = Hash::make($otpCode);
        $otpGeneratedAt = Carbon::now();
        $otpExpiredAt = $otpGeneratedAt->copy()->addSeconds((int) config('laravel-simple-otp.otp_timeout_seconds'));

        $otp = $this->otpModel->create([
            'actor_id' => $actorId,
            'actor_type' => $actorType,
            'device_id' => $deviceId,
            'otp_intent' => $otpIntent,
            'otp_code_hash' => $otpCodeHash,
            'otp_generated_at' => $otpGeneratedAt,
            'otp_expired_at' => $otpExpiredAt,
            'correlation_id' => $correlationId,
            'otp_meta' => $otpMeta,
        ]);

        return [
            'generated_at' => $otp->otp_generated_at,
            'expired_at' => $otp->otp_expired_at,
            'otp_length' => strlen($otpCode),
        ];
    }
}
