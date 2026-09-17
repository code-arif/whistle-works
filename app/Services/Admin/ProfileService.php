<?php

namespace App\Services\Admin;

use App\Helpers\Helper;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    /**
     * Get formatted profile information for the authenticated user.
     */
    public function getProfileData(User $user): array
    {
        $avatarUrl = null;
        if ($user->avatar) {
            $avatarUrl = str_starts_with($user->avatar, 'http') ? $user->avatar : asset($user->avatar);
        }

        return [
            'id'          => $user->id,
            'first_name'  => $user->first_name ?? '',
            'last_name'   => $user->last_name ?? '',
            'full_name'   => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Administrator'),
            'email'       => $user->email ?? '',
            'phone'       => $user->phone ?? '',
            'address'     => $user->address ?? '',
            'biography'   => $user->biography ?? '',
            'avatar'      => $avatarUrl,
            'role'        => $user->roles->pluck('name')->first() ?? 'Super Admin',
            'created_at'  => $user->created_at ? $user->created_at->format('M d, Y') : null,
            'last_active' => $user->last_activity_at ? $user->last_activity_at->diffForHumans() : 'Active recently',
        ];
    }

    /**
     * Update user profile personal details.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name'  => $data['last_name'] ?? $user->last_name,
            'email'      => $data['email'] ?? $user->email,
            'phone'      => $data['phone'] ?? $user->phone,
            'address'    => $data['address'] ?? $user->address,
            'biography'  => $data['biography'] ?? $user->biography,
        ]);

        return $user;
    }

    /**
     * Update user password with current password verification.
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return true;
    }

    /**
     * Update user avatar and remove old avatar asset.
     */
    public function updateAvatar(User $user, UploadedFile $file): string
    {
        // Delete previous avatar file if exists
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            $fullOldPath = public_path($user->avatar);
            Helper::fileDelete($fullOldPath);
        }

        $uploadedPath = Helper::fileUpload($file, 'profile');
        if (!$uploadedPath) {
            throw new Exception('Failed to upload profile picture.');
        }

        $user->update([
            'avatar' => $uploadedPath,
        ]);

        return asset($uploadedPath);
    }
}
