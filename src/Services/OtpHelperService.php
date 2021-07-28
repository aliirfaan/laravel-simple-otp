<?php

namespace aliirfaan\LaravelSimpleOtp\Services;

use Illuminate\Support\Facades\Hash;
use \Carbon\Carbon;
use aliirfaan\LaravelSimpleOtp\Exceptions\ExpiredException;
use aliirfaan\LaravelSimpleOtp\Exceptions\NotFoundException;
use aliirfaan\LaravelSimpleOtp\Exceptions\NotMatchException;

/**
 * OTP helper service
 *
 * Helper class to generate and verify OTP codes
 */
class OtpHelperService
{
    /**
     * Generate a random OTP code based on length
     *
     * If hash configuration is set to true, use framework hashing function to hash code, else return code as is
     * 
     * @param  int $otpCodeLength The length of the OTP code to generate
     * @return array Random OTP code and hashed otp
     */
    public function generateOtpCode(int $otpCodeLength = null)
    {
        $otpCodeLength = intval($otpCodeLength);
        if ($otpCodeLength == 0) {
            $otpCodeLength = config('otp.otp_digit_length', 6);
        }

        $randomNumber = strval(rand(100000000, 999999999));
        $otpCode = substr($randomNumber, 0, $otpCodeLength);
        $otpHash = $otpCode;

        if (config('otp.otp_should_encode', false)) {
            $otpHash = Hash::make($otpCode);
        }

        return ['otp_code' => $otpCode, 'otp_hash' => $otpHash];
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
        if (config('otp.otp_does_expire', false)) {
            return $createdAt < Carbon::now()->subSeconds(config('otp.otp_timeout_seconds'));
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
        if (config('otp.otp_should_encode', false)) {
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
     * @param  ModelGotOtp $otpObj OTP model object
     * @param  string $otpCode OTP code
     * @return bool Whether valid or not
     * @throws NotFoundException If OTP code does not exist
     * @throws NotMatchException If OTP code does not match
     * @throws ExpiredException If OTP code has expired
     */
    public function otpCodeIsValid($otpObj, $otpCode)
    {
        if (is_null($otpObj)) {
            throw new NotFoundException('OTP was not found');
        } elseif ($this->otpCodeDidMatch($otpCode, $otpObj->otp_code) == false) {
            throw new NotMatchException('OTP did not match');
        } elseif ($this->otpCodeDidExpire($otpObj->otp_generated_at) == true) {
            throw new ExpiredException('Expired OTP');
        }

        return true;
    }
}