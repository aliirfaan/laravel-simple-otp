<?php

namespace aliirfaan\LaravelSimpleOtp\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * OTP model
 */
class ModelGotOtp extends Model
{
    protected $fillable = ['model_id', 'model_type', 'otp_code', 'otp_was_validated'];
    
    /**
     * Add an OTP row to table
     *
     * @param  array $otpData
     * @return ModelGotOtp | bool Object if success or false if unsuccessful
     */
    public function createOtp($otpData)
    {
        return ModelGotOtp::create([
            'model_id' => $otpData['model_id'],
            'model_type' => $otpData['model_type'],
            'otp_code' => $otpData['otp_code'],
        ]);
    }

    /**
     * Adds or updates an OTP row to table
     * 
     * If a row with model_id and model_type already exists, update else insert new row
     *
     * @param  array $otpData
     * @return ModelGotOtp | bool Object if success or false if unsuccessful
     */
    public function createOrUpdateOtp($otpData)
    {
        return ModelGotOtp::updateOrCreate(
            [
                'model_id' => $otpData['model_id'], 
                'model_type' => $otpData['model_type']],
            [
                'model_id' => $otpData['model_id'],
                'model_type' => $otpData['model_type'],
                'otp_code' => $otpData['otp_code'],
                'otp_was_validated' => null,
            ]
        );
    }
    
    /**
     * Get first OTP row by model_id and model_type
     *
     * @param  int $modelId key of model
     * @param  string $modelType name of model
     * @return  ModelGotOtp | null Object if success or null
     */
    public function getOtp($modelId, $modelType)
    {
        return ModelGotOtp::where(function ($query) use ($modelId) {
            $query->where('model_id', '=', $modelId);
        })->where(function ($query) use ($modelType) {
            $query->where('model_type', '=', $modelType);
        })->where(function ($query) {
            $query->where('otp_was_validated', '!=', 1)
            ->orWhereNull('otp_was_validated');
        })
        ->orderBy('created_at', 'desc')
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
