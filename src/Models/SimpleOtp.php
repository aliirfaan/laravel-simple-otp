<?php

namespace aliirfaan\LaravelSimpleOtp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

/**
 * OTP model
 */
class SimpleOtp extends Model
{
    use HasUuids, Prunable;

    /**
     * Get the prunable model query.
     * 
     * Check if otp_retention_days > 0, if so, prune the OTP records.
     * Prunes OTP records that are:
     * - Expired (past their expiry date), OR
     * - Already verified
     * 
     * If otp_retention_days is 0, no pruning will be done.
     * Run via: php artisan model:prune --model="aliirfaan\LaravelSimpleOtp\Models\SimpleOtp"
     * Or schedule in your application's console kernel.
     */
    public function prunable(): Builder
    {
        $retentionDays = (int) config('laravel-simple-otp.otp_retention_days', 0);

        if ($retentionDays > 0) {
            return static::where('otp_expired_at', '<', Carbon::now()->subDays($retentionDays))
                ->orWhere(function (Builder $q) use ($retentionDays) {
                    $q->whereNotNull('otp_verified_at')
                        ->where('otp_verified_at', '<', Carbon::now()->subDays($retentionDays));
                });
        }

        return static::whereRaw('0 = 1'); // Return empty query to avoid pruning
    }

    protected $table = 'lso_otps';

    protected $fillable = [
        'actor_id', 
        'actor_type', 
        'device_id', 
        'otp_intent', 
        'otp_code_hash', 
        'otp_generated_at', 
        'otp_verified_at', 
        'otp_expired_at', 
        'correlation_id', 
        'otp_meta'
    ];

    protected $casts = [
        'otp_meta' => 'array',
        'otp_generated_at' => 'datetime',
        'otp_verified_at' => 'datetime',
        'otp_expired_at' => 'datetime',
    ];
    
    /**
     * Get first OTP row by actor_id, actor_type, otp_intent, device_id
     *
     * @param  string $actorId id of actor
     * @param  string $actorType name of actor
     * @param  string $otpIntent why was the OTP sent - a model maybe sent multiple OTPs
     * @param  string $deviceId id of device
     * 
     * @return  self|null Row if found or null if not found
     */
    public function getLatestOtp(?string $actorId, ?string $actorType, ?string $otpIntent, ?string $deviceId): ?self
    {
        return $this->where('actor_id', $actorId)
            ->where('actor_type', $actorType)
            ->where('otp_intent', $otpIntent)
            ->where('device_id', $deviceId)
            ->orderByDesc('otp_generated_at')
            ->first();
    }
    
    /**
     * Mark OTP as verified
     *
     * @param string $id id of row
     * 
     * @return int number of rows updated
     */
    public function markAsVerified(string $id): int
    {
        return $this->where('id', $id)
            ->update(['otp_verified_at' => Carbon::now()]);
    }
}
