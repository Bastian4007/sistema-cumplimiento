<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserInvitationController extends Controller
{
    public function show(string $token)
    {
        $user = User::with(['company', 'role'])
            ->where('invite_token', $token)
            ->firstOrFail();

        if (!$user->invite_expires_at || now()->greaterThan($user->invite_expires_at)) {
            abort(403, 'This invitation has expired.');
        }

        if ($user->invitation_accepted_at) {
            abort(403, 'This invitation has already been used.');
        }

        return view('users.accept-invitation', compact('user'));
    }

    public function store(Request $request, string $token)
    {
        $user = User::where('invite_token', $token)->firstOrFail();

        if (!$user->invite_expires_at || now()->greaterThan($user->invite_expires_at)) {
            abort(403, 'This invitation has expired.');
        }

        if ($user->invitation_accepted_at) {
            abort(403, 'This invitation has already been used.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
            'status' => 'active',
            'email_verified_at' => now(),
            'invitation_accepted_at' => now(),
            'invite_token' => null,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}