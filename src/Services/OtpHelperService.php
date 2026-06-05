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
     * Get the default profile config array.
     *
     * @return array
     */
    private function getDefaultProfileConfig(): array
    {
        $defaultProfileName = (string) config('laravel-simple-otp.default_profile', 'default');
        $profiles = config('laravel-simple-otp.otp_profiles', []);

        return $profiles[$defaultProfileName] ?? [];
    }

    /**
     * Resolve an OTP profile from config.
     *
     * @param  string|null $profileName Profile name or null for default_profile
     * @return array{
     *     name: string,
     *     otp_type: string,
     *     otp_length: int,
     *     otp_timeout_seconds: int,
     *     otp_should_simulate: bool,
     *     otp_simulated_code: string,
     *     otp_retention_days: int
     * }
     *
     * @throws \InvalidArgumentException If profile is not defined
     */
    private function resolveProfile(?string $profileName): array
    {
        $profileName ??= (string) config('laravel-simple-otp.default_profile', 'default');
        $profiles = config('laravel-simple-otp.otp_profiles', []);

        if (!isset($profiles[$profileName])) {
            throw new \InvalidArgumentException(sprintf('OTP profile [%s] is not defined.', $profileName));
        }

        $default = $this->getDefaultProfileConfig();
        $profile = $profiles[$profileName];

        return [
            'name' => $profileName,
            'otp_type' => $profile['otp_type'] ?? $default['otp_type'] ?? 'numeric',
            'otp_length' => (int) ($profile['otp_length'] ?? $default['otp_length'] ?? 6),
            'otp_timeout_seconds' => max(1, (int) ($profile['otp_timeout_seconds'] ?? $default['otp_timeout_seconds'] ?? 180)),
            'otp_should_simulate' => (bool) ($profile['otp_should_simulate'] ?? $default['otp_should_simulate'] ?? false),
            'otp_simulated_code' => (string) ($profile['otp_simulated_code'] ?? $default['otp_simulated_code'] ?? ''),
            'otp_retention_days' => (int) ($profile['otp_retention_days'] ?? $default['otp_retention_days'] ?? 0),
        ];
    }

    /**
     * Generate a cryptographically secure OTP code.
     *
     * Supports two OTP types per profile:
     * - 'numeric': Digits 1-9 only (no zero to avoid confusion)
     * - 'alphanumeric': Uppercase letters A-Z (excluding O) and digits 1-9
     *
     * Security considerations:
     * - Uses random_int() for cryptographic randomness
     * - Excludes ambiguous characters (0, O, o) to prevent user confusion
     * - Simulation mode available for testing (must be disabled in production)
     *
     * @param  string|null $profile Profile name or null for default_profile
     * @return string Generated OTP code
     *
     * @throws \InvalidArgumentException If profile is not defined or length is outside allowed bounds
     * @throws \Exception If random_int() fails (insufficient entropy)
     */
    public function generateOtpCode(?string $profile = null): string
    {
        $resolvedProfile = $this->resolveProfile($profile);
        $length = $this->validateOtpLength($resolvedProfile['otp_length']);

        if ($resolvedProfile['otp_should_simulate']) {
            return $this->getSimulatedOtpCode($length, $resolvedProfile['otp_simulated_code']);
        }

        return $this->isNumericType($resolvedProfile['otp_type'])
            ? $this->generateSecureCode(self::NUMERIC_CHARSET, $length)
            : $this->generateSecureCode(self::ALPHANUMERIC_CHARSET, $length);
    }

    /**
     * Validate OTP length.
     *
     * @param  int $length Requested length
     * @return int Validated length
     *
     * @throws \InvalidArgumentException If length is outside bounds
     */
    private function validateOtpLength(int $length): int
    {
        if ($length < self::MIN_OTP_LENGTH || $length > self::MAX_OTP_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('OTP length must be between %d and %d.', self::MIN_OTP_LENGTH, self::MAX_OTP_LENGTH)
            );
        }

        return $length;
    }

    /**
     * Check if OTP type is numeric.
     *
     * @param  string $otpType
     * @return bool
     */
    private function isNumericType(string $otpType): bool
    {
        return $otpType === 'numeric';
    }

    /**
     * Get simulated OTP code for testing.
     *
     * @param  int $length Desired OTP length
     * @return string Simulated OTP code
     */
    private function getSimulatedOtpCode(int $length, string $simulatedCode): string
    {

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
     *     @type string $actor_id    Actor identifier (optional)
     *     @type string $actor_type  Actor type (optional)
     *     @type string $otp_intent  OTP intent/purpose (required)
     *     @type string $device_id   Device identifier (optional)
     *     @type string $recipient   Recipient of the OTP (optional)
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
        $recipient = $validateData['recipient'] ?? null;
        $otpCode = $validateData['otp_code'] ?? null;

        // Get latest OTP for actor/intent/device
        $latestOtp = $this->otpModel->getLatestOtp($actorId, $actorType, $otpIntent, $deviceId, $recipient);
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
     * Set otp_expired_at from profile otp_timeout_seconds
     * Store resolved profile name on the row
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
        $actorId = $otpData['actor_id'] ?? null;
        $actorType = $otpData['actor_type'] ?? null;
        $deviceId = $otpData['device_id'] ?? null;
        $otpIntent = $otpData['otp_intent'] ?? null;
        $correlationId = $otpData['correlation_id'] ?? null;
        $recipient = $otpData['recipient'] ?? null;
        $otpMeta = $otpData['otp_meta'] ?? null;

        $resolvedProfile = $this->resolveProfile($otpData['profile'] ?? null);

        $otpCodeHash = Hash::make($otpCode);
        $otpGeneratedAt = Carbon::now();

        $otpExpiredAt = $otpGeneratedAt->copy()->addSeconds($resolvedProfile['otp_timeout_seconds']);

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
            'recipient' => $recipient,
            'profile' => $resolvedProfile['name'],
        ]);

        return [
            'generated_at' => $otp->otp_generated_at,
            'expired_at' => $otp->otp_expired_at,
            'otp_length' => strlen($otpCode),
        ];
    }
}
