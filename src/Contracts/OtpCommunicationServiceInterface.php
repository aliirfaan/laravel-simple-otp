<?php

namespace aliirfaan\LaravelSimpleOtp\Contracts;

/**
 * Interface for SMS communication provider
 *
 * Your implementation should implemented this interface
 */
interface OtpCommunicationServiceInterface
{
    /**
     * Send OTP code to a recipient phone number
     *
     * @param string $phoneNumber  Recipient phone numbet
     * @param string $otpCode : OTP code to send
     * @return bool
     */
    public function sendSms($phoneNumber, $otpCode);
}
