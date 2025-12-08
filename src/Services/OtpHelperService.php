<?php

namespace aliirfaan\LaravelSimpleOtp\Services;

use Illuminate\Support\Facades\Hash;
use \Carbon\Carbon;
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
        $actorId = $otpData['actor_id'] ?? null;
        $actorType = $otpData['actor_type'] ?? null;
        $deviceId = $otpData['device_id'] ?? null;
        $otpIntent = $otpData['otp_intent'] ?? null;
        $correlationId = $otpData['correlation_id'] ?? null;
        
        $otpMeta = $otpData['otp_meta'] ?? null;
        $otpMeta = is_array($otpMeta) ? json_encode($otpMeta) : $otpMeta;

        $otpCodeHash = Hash::make($otpCode);
        $otpGeneratedAt = Carbon::now();
        $otpExpiredAt = $otpGeneratedAt->copy()->addSeconds((int) config('laravel-simple-otp.otp_timeout_seconds'));

        $otp = SimpleOtp::create([
            'actor_id' => $actorId,
            'actor_type' => $actorType,
            'device_id' => $deviceId,
            'otp_intent' => $otpIntent,
            'otp_code_hash' => $otpCodeHash,
            'otp_generated_at' => $otpGeneratedAt->toDateTimeString(),
            'otp_expired_at' => $otpExpiredAt->toDateTimeString(),
            'correlation_id' => $correlationId,
            'otp_meta' => $otpMeta,
        ]);

        return [
            'generated_at' => $otp->otp_generated_at,
            'expired_at' => $otp->otp_expired_at,
            'otp_length' => strlen($otpCode),
        ];
    }

    



    /**
     * Verify if OTP code has expired based on created date and timeout seconds
     *
     * Reads configuration value
     * If OTP expires is set to true then checks whether the code has expired
     *
     * @param  string $createdAt Date in Y-m-d H:i:s format
     * @return bool Whether the OTP expired or not
     */
    public function otpCodeDidExpire($createdAt)
    {
        if (config('laravel-simple-otp.otp_does_expire', false)) {
            return $createdAt < Carbon::now()->subSeconds(intval(config('laravel-simple-otp.otp_timeout_seconds')));
        }

        return false;
    }
    
    /**
     * Verify if OTP code matches with that stored in the database
     *
     * Reads configuration value
     * If OTP was hashed, make a hash check else make an equality check
     *
     * @param  string $otpCodeToMatch OTP code submitted
     * @param  string $originalOtpCode OTP code stores in the database
     * @return boll Whether OTP code matches or not
     */
    public function otpCodeDidMatch($otpCodeToMatch, $originalOtpCode)
    {
        if (config('laravel-simple-otp.otp_should_encode', false)) {
            return Hash::check($otpCodeToMatch, $originalOtpCode);
        } else {
            return $originalOtpCode == $otpCodeToMatch;
        }

        return false;
    }
    
    /**
     * Single function to validate OTP code by calling other validation methods
     *
     * Check if OTP code exists
     * Check if OTP code matches
     * Check if OTP code has expired
     *
     * @param  SimpleOtp $otpObj OTP model object
     * @param  string $otpCode OTP code
     * @return bool Whether valid or not
     * @throws OtpNotFoundException If OTP code does not exist
     * @throws OtpMismatchException If OTP code does not match
     * @throws OtpExpiredException If OTP code has expired
     */
    public function otpCodeIsValid($otpObj, $otpCode)
    {
        if (is_null($otpObj)) {
            throw new OtpNotFoundException('OTP was not found');
        } elseif ($this->otpCodeDidMatch($otpCode, $otpObj->otp_code) == false) {
            throw new OtpMismatchException('OTP did not match');
        } elseif ($this->otpCodeDidExpire($otpObj->otp_generated_at) == true) {
            throw new OtpExpiredException('Expired OTP');
        }

        return true;
    }
    
    /**
     * Get expiry date of otp code
     *
     * @param  SimpleOtp $otpObj OTP model object
     * @param  string $format date time format
     *
     * @return string|null
     */
    public function getOtpCodeExpiryDate($otpObj, $format = 'Y-m-d H:i:s')
    {
        return (Carbon::parse($otpObj->otp_generated_at)->addSeconds(intval(config('laravel-simple-otp.otp_timeout_seconds'))))->format($format);
    }
}
