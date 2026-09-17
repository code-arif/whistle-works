<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ProfileService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $service
    ) {}

    /**
     * Display the executive profile settings page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $profile = $this->service->getProfileData($user);

        return Inertia::render('Profile/Index', [
            'profile' => $profile,
        ]);
    }

    /**
     * Update user personal details and contact information.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'      => ['nullable', 'string', 'max:30'],
            'address'    => ['nullable', 'string', 'max:255'],
            'biography'  => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->updateProfile($user, $validated);
            return redirect()->back()->with('t-success', 'Profile information updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Update user password securely.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $success = $this->service->updatePassword(
                $user,
                $validated['current_password'],
                $validated['password']
            );

            if (!$success) {
                return redirect()->back()->withErrors([
                    'current_password' => 'The provided current password does not match our records.'
                ])->with('t-error', 'Current password verification failed.');
            }

            return redirect()->back()->with('t-success', 'Account password updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update password: ' . $e->getMessage());
        }
    }

    /**
     * Update user avatar image.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        try {
            $this->service->updateAvatar($user, $request->file('avatar'));
            return redirect()->back()->with('t-success', 'Profile picture updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Failed to update profile picture: ' . $e->getMessage());
        }
    }
}
