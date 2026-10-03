<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OtpAuthController extends Controller
{
    public function __construct(protected OtpService $otpService) {}

    /**
     * Show the OTP login / sign-up screen. If an OTP was already sent
     * this session, the page re-renders on the "enter code" step so a
     * refresh mid-flow doesn't lose the user's place.
     */
    public function create(Request $request): Response
    {
        $pendingEmail = $request->session()->get('otp.email');

        return Inertia::render('Auth/Login', [
            'pendingEmail' => $pendingEmail,
            'pendingName' => $request->session()->get('otp.name'),
            // Demo-only: see config/otp.php. Lets the UI show the code
            // directly instead of requiring access to the mail log.
            'demoOtp' => $request->session()->get('demo_otp'),
            'resendWaitSeconds' => $pendingEmail
                ? $this->otpService->secondsUntilResendAllowed($pendingEmail)
                : 0,
            'otpLength' => config('otp.length'),
        ]);
    }

    /**
     * Validate name + email, send a fresh OTP, and move the UI to the
     * "enter code" step.
     */
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $wait = $this->otpService->secondsUntilResendAllowed($data['email']);

        if ($wait > 0) {
            return back()->with('error', "Please wait {$wait}s before requesting another code.");
        }

        $plainOtp = $this->otpService->generateAndSend($data['email']);

        $request->session()->put('otp.email', $data['email']);
        $request->session()->put('otp.name', $data['name']);

        return back()
            ->with('success', 'We emailed a verification code to '.$data['email'].'.')
            ->with('demo_otp', config('otp.debug_display') ? $plainOtp : null);
    }

    /**
     * Re-send a code to the email address already pending in this
     * session (used by the "Resend code" link).
     */
    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get('otp.email');

        if (! $email) {
            return redirect()->route('login')->with('error', 'Please enter your email again.');
        }

        $wait = $this->otpService->secondsUntilResendAllowed($email);

        if ($wait > 0) {
            return back()->with('error', "Please wait {$wait}s before requesting another code.");
        }

        $plainOtp = $this->otpService->generateAndSend($email);

        return back()
            ->with('success', 'We emailed a new verification code to '.$email.'.')
            ->with('demo_otp', config('otp.debug_display') ? $plainOtp : null);
    }

    /**
     * Verify the submitted code. On success, logs the user in --
     * creating the account first if this email hasn't signed in before.
     */
    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'otp' => ['required', 'string', 'digits:'.config('otp.length')],
        ]);

        $email = $request->session()->get('otp.email');
        $name = $request->session()->get('otp.name');

        if (! $email) {
            return redirect()->route('login')->with('error', 'Please enter your email again.');
        }

        if (! $this->otpService->verify($email, $data['otp'])) {
            return back()->with('error', 'That code is invalid or has expired.');
        }

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $name ?: 'ShopEase Customer';
        }

        $user->email_verified_at = now();
        $user->save();

        Auth::login($user, remember: true);
        $request->session()->forget(['otp.email', 'otp.name', 'demo_otp']);
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('success', 'Welcome, '.$user->name.'!');
    }

    /**
     * Abandon the current OTP flow and return to the email step (the
     * "Use a different email" link).
     */
    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['otp.email', 'otp.name', 'demo_otp']);

        return redirect()->route('login');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }
}
