<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);
        $email = trim((string) $validated['email']);
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->first();

        if (!$user) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'email' => 'No account found for this email.',
                ]);
        }

        if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'email' => 'Email not verified. Please verify your email first before resetting your password.',
                ]);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            $status = Password::sendResetLink(
                ['email' => $email]
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset email.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput(['email' => $email])
                ->withErrors([
                    'email' => 'Unable to send password reset email right now. Please try again later.',
                ]);
        }

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', 'Password reset email sent.')
                    : back()->withInput(['email' => $email])
                        ->withErrors(['email' => __($status)]);
    }
}
