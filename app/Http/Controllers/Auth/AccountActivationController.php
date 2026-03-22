<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    public function verify(Request $request, string $id, string $hash): View|RedirectResponse
    {
        $user = User::query()->find($id);
        if (!$user) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Invalid activation link.']);
        }

        if (!hash_equals((string) $hash, sha1((string) $user->getEmailForVerification()))) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Invalid activation link.']);
        }

        $email = (string) $user->email;
        $token = (string) $request->query('token', '');

        $hasValidToken = $token !== '' && Password::broker()->tokenExists($user, $token);

        if (!$hasValidToken) {
            if (!empty($user->email_verified_at)) {
                return view('auth.activation-used');
            }
            return view('auth.activation-expired', [
                'email' => $email,
                'activationUserId' => (int) $user->id,
                'activationHash' => sha1((string) $user->getEmailForVerification()),
            ]);
        }

        if (empty($user->email_verified_at)) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new Verified($user));
        }

        $setPasswordUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
            'setup' => 1,
        ]);

        return view('auth.activation-verified', [
            'setPasswordUrl' => $setPasswordUrl,
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'activation_user_id' => ['required', 'integer'],
            'activation_hash' => ['required', 'string'],
        ]);

        $userId = (int) $validated['activation_user_id'];
        $hash = trim((string) $validated['activation_hash']);
        $user = User::query()->find($userId);

        if (!$user || !hash_equals($hash, sha1((string) $user->getEmailForVerification()))) {
            return back()->withErrors([
                'activation' => 'Invalid activation request. Please use your latest activation link.',
            ])->withInput();
        }

        if (!empty($user->email_verified_at)) {
            return back()->with('status', 'Email is already verified. You can log in now.');
        }

        $token = Password::broker()->createToken($user);
        $user->notify(new AccountActivationNotification($token));

        return back()->with('status', 'A new activation email has been sent.');
    }
}
