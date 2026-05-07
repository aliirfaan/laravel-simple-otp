<?php

namespace aliirfaan\LaravelSimpleOtp\Models;

use Illuminate\Database\Eloquent\Model;
use \Carbon\Carbon;

/**
 * OTP model
 */
class ModelGotOtp extends Model
{
    protected $fillable = ['model_id', 'model_type', 'recipient', 'otp_intent', 'otp_code', 'otp_was_validated', 'otp_generated_at'];
    
    /**
     * Add an OTP row to table
     *
     * @param  array $otpData
     * @param  bool $updateRow whether to update the row if row with model_id, model_type, otp_intent, recipient already exists
     * @return ModelGotOtp
     * @throws \Exception
     */
    public function createOtp($otpData, $updateRow = true)
    {
        if ($updateRow) {
            return ModelGotOtp::updateOrCreate(
                [
                    'model_id' => $otpData['model_id'],
                    'model_type' => $otpData['model_type'],
                    'otp_intent' => $otpData['otp_intent'],
                    'recipient' => $otpData['recipient'] ?? null,
                ],
                [
                    'otp_code' => $otpData['otp_code'],
                    'otp_was_validated' => null,
                    'otp_generated_at' => Carbon::now()->toDateTimeString(),
                ]
            );
        }

        return ModelGotOtp::create([
            'model_id' => $otpData['model_id'],
            'model_type' => $otpData['model_type'],
            'otp_intent' => $otpData['otp_intent'],
            'recipient' => $otpData['recipient'] ?? null,
            'otp_code' => $otpData['otp_code'],
            'otp_generated_at' => Carbon::now()->toDateTimeString(),
        ]);
    }
    
    /**
     * Get first OTP row by model_id, model_type, otp_intent, recipient
     *
     * @param int $modelId key of model
     * @param string $modelType name of model
     * @param string $otpIntent why was the OTP sent - a model maybe sent multiple OTPs
     * @param string $recipient recipient of the OTP
     * @return ModelGotOtp | null Object if success or null
     * @throws \Exception
     */
    public function getOtp($modelId, $modelType, $otpIntent = null, $recipient = null)
    {
        return ModelGotOtp::where(function ($query) use ($modelId, $modelType, $otpIntent, $recipient) {
            $query->where('model_id', '=', $modelId);
            $query->where('model_type', '=', $modelType);
            $query->where('otp_intent', '=', $otpIntent);
            $query->where('recipient', '=', $recipient);
            $query->where(function ($q) {
                $q->where('otp_was_validated', '!=', 1)
                    ->orWhereNull('otp_was_validated');
            });
        })
        ->orderBy('otp_generated_at', 'desc')
        ->first();
    }
    
    /**
     * Update OTP row
     *
     * Update the validated status of the otp
     *
     * @param int $id id of row
     * @return int
     */
    public function updateOtp($id)
    {
        return ModelGotOtp::where('id', $id)
                ->update(['otp_was_validated' => 1]);
    }
}
