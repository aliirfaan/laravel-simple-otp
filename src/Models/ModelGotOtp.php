<?php

namespace aliirfaan\LaravelSimpleOtp\Models;

use Illuminate\Database\Eloquent\Model;
use \Carbon\Carbon;

/**
 * OTP model
 */
class ModelGotOtp extends Model
{
    protected $fillable = ['model_id', 'model_type', 'otp_intent', 'otp_code', 'otp_was_validated', 'otp_generated_at'];
    
    /**
     * Add an OTP row to table
     *
     * @param  array $otpData
     * @param  bool $updateRow whether to update the row if row with model_id and model_type already exists
     * @return ModelGotOtp | bool Object if success or false if unsuccessful
     */
    public function createOtp($otpData, $updateRow = true)
    {
        $result = false;

        if ($updateRow == true) {
            $result = ModelGotOtp::updateOrCreate(
                [
                    'model_id' => $otpData['model_id'], 
                    'model_type' => $otpData['model_type']],
                [
                    'otp_intent' => $otpData['otp_intent'],
                    'otp_code' => $otpData['otp_code'],
                    'otp_was_validated' => null,
                    'otp_generated_at' => Carbon::now()->toDateTimeString()
                ]
            );
        } else {
            $result = ModelGotOtp::create([
                'model_id' => $otpData['model_id'],
                'model_type' => $otpData['model_type'],
                'otp_intent' => $otpData['otp_intent'],
                'otp_code' => $otpData['otp_code'],
                'otp_generated_at' => Carbon::now()->toDateTimeString()
            ]);
        }

        return $result;
    }
    
    /**
     * Get first OTP row by model_id and model_type
     *
     * @param  int $modelId key of model
     * @param  string $modelType name of model
     * @param  string $otpIntent why was the OTP sent - a model maybe sent multiple OTPs
     * @return  ModelGotOtp | null Object if success or null
     */
    public function getOtp($modelId, $modelType, $otpIntent = null)
    {
        return ModelGotOtp::where(function ($query) use ($modelId) {
            $query->where('model_id', '=', $modelId);
        })->where(function ($query) use ($modelType) {
            $query->where('model_type', '=', $modelType);
        })->where(function ($query) use ($otpIntent) {
            $query->where('otp_intent', '=', $otpIntent);
        })->where(function ($query) {
            $query->where('otp_was_validated', '!=', 1)
            ->orWhereNull('otp_was_validated');
        })
        ->orderBy('otp_generated_at', 'desc')
        ->first();
    }
    
    /**
     * Update OTP row
     *
     * Update the validated status of the otp
     *
     * @param  int $id id of row
     * @return bool
     */
    public function updateOtp($id)
    {
        return ModelGotOtp::where('id', $id)
                ->update(['otp_was_validated' => 1]);
    }
}
