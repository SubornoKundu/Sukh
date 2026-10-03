<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * How many seconds the caller must wait before another OTP may be
     * requested for this email. Zero means a new OTP can be sent now.
     */
    public function secondsUntilResendAllowed(string $email): int
    {
        $latest = OtpVerification::where('email', $email)->latest('id')->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = $latest->created_at->diffInSeconds(now());
        $wait = config('otp.resend_throttle_seconds') - $elapsed;

        return max(0, (int) $wait);
    }

    /**
     * Generate a fresh OTP, persist its hash, and email it to the given
     * address. Returns the plain (un-hashed) code -- the only place the
     * plain code ever exists is this return value and the outgoing
     * email, so callers must not persist it anywhere themselves.
     */
    public function generateAndSend(string $email): string
    {
        $length = config('otp.length');
        $plainOtp = (string) random_int(
            (int) str_pad('1', $length, '0'),
            (int) str_pad('', $length, '9'),
        );

        OtpVerification::create([
            'email' => $email,
            'otp' => Hash::make($plainOtp),
            'expires_at' => now()->addMinutes(config('otp.expiry_minutes')),
        ]);

        Mail::to($email)->send(new OtpMail($plainOtp, config('otp.expiry_minutes')));

        return $plainOtp;
    }

    /**
     * Verify a submitted OTP against the most recent unused code issued
     * for this email. On success, marks that record as verified so it
     * cannot be replayed.
     */
    public function verify(string $email, string $otp): bool
    {
        $record = OtpVerification::where('email', $email)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $record || $record->isExpired()) {
            return false;
        }

        if (! Hash::check($otp, $record->otp)) {
            return false;
        }

        $record->update(['verified_at' => now()]);

        return true;
    }
}
