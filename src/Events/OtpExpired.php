<?php

namespace aliirfaan\LaravelSimpleOtp\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Event name.
     *
     * @var string
     */
    public $name;

    /**
     * The user the attempter was trying to authenticate as.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public $user;

    /**
     * The credentials provided by the attempter.
     *
     * @var array
     */
    public $credentials;

    /**
     * Otp model
     *
     * @var aliirfaan\LaravelSimpleOtp\Models\SimpleOtp|null
     */
    public $otpObj;

    /**
     * Create a new event instance.
     * 
     * @param  array|null  $credentials
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param  aliirfaan\LaravelSimpleOtp\Models\SimpleOtp|null $otpObj
     * @param  string  $name
     * @return void
     */
    public function __construct($credentials = null, $user = null, $otpObj = null, $name = 'otp.expired')
    {
        $this->user = $user;
        $this->credentials = $credentials;
        $this->name = $name;
        $this->otpObj = $otpObj;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
