<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\IdorScenario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function show(User $user): View
    {
        if (! IdorScenario::bypassProfileViewAuthorization()) {
            $this->authorize('view', $user);
        }

        return view('users.show', [
            'profileUser' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (! IdorScenario::bypassProfileUpdateAuthorization()) {
            $this->authorize('update', $user);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $redirectUser = $user;

        // In profile-update-only mode, update is intentionally vulnerable while
        // profile view remains protected. Redirect to actor profile to avoid
        // a misleading post-update 403.
        if (! IdorScenario::bypassProfileViewAuthorization() && $actor?->id !== $user->id) {
            $redirectUser = $actor;
        }

        return redirect()
            ->route('users.show', $redirectUser)
            ->with('status', 'Profile updated.');
    }
}
