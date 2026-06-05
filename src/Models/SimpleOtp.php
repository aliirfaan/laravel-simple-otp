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
     * Uses otp_retention_days from each profile (fallback: default_profile).
     * Prunes OTP records that are expired or verified past their profile retention window.
     * Rows with null profile use the default_profile retention settings.
     * 
     * If all profile retention values are 0, no pruning is done.
     * Run via: php artisan model:prune --model="aliirfaan\LaravelSimpleOtp\Models\SimpleOtp"
     */
    public function prunable(): Builder
    {
        $profiles = config('laravel-simple-otp.otp_profiles', []);
        $defaultProfileName = (string) config('laravel-simple-otp.default_profile', 'default');
        $defaultProfile = $profiles[$defaultProfileName] ?? [];

        $query = static::query()->whereRaw('0 = 1');

        foreach ($profiles as $profileName => $profileConfig) {
            $retentionDays = (int) ($profileConfig['otp_retention_days'] ?? $defaultProfile['otp_retention_days'] ?? 0);

            if ($retentionDays <= 0) {
                continue;
            }

            $cutoff = Carbon::now()->subDays($retentionDays);

            $query->orWhere(function (Builder $q) use ($profileName, $defaultProfileName, $cutoff) {
                if ($profileName === $defaultProfileName) {
                    $q->where(function (Builder $profileQuery) use ($profileName) {
                        $profileQuery->where('profile', $profileName)
                            ->orWhereNull('profile');
                    });
                } else {
                    $q->where('profile', $profileName);
                }

                $q->where(function (Builder $inner) use ($cutoff) {
                    $inner->where('otp_expired_at', '<', $cutoff)
                        ->orWhere(function (Builder $verified) use ($cutoff) {
                            $verified->whereNotNull('otp_verified_at')
                                ->where('otp_verified_at', '<', $cutoff);
                        });
                });
            });
        }

        return $query;
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
        'otp_meta',
        'recipient',
        'profile',
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
     * @param  string $recipient recipient of the OTP
     * 
     * @return  self|null Row if found or null if not found
     */
    public function getLatestOtp(?string $actorId, ?string $actorType, ?string $otpIntent, ?string $deviceId, ?string $recipient): ?self
    {
        return $this->where('actor_id', $actorId)
            ->where('actor_type', $actorType)
            ->where('otp_intent', $otpIntent)
            ->where('device_id', $deviceId)
            ->where('recipient', $recipient)
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
