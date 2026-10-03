<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'otp',
        'expires_at',
        'verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * The hashed OTP should never leak into a JSON response.
     *
     * @var list<string>
     */
    protected $hidden = [
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Whether this OTP record is still within its validity window.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Whether this OTP has already been used.
     */
    public function isVerified(): bool
    {
        return ! is_null($this->verified_at);
    }
}
