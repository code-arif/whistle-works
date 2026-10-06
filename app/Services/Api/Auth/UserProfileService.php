<?php

namespace App\Services\Api\Auth;

use App\Helpers\Helper;
use App\Http\Resources\Api\Auth\UserProfileResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserProfileService
{
    /**
     * Get detailed user profile data.
     *
     * @param  User  $user
     * @return array
     */
    public function getUserDetails(User $user): array
    {
        return (new UserProfileResource($user, true))->resolve();
    }

    /**
     * Update user profile information.
     *
     * @param  User   $user
     * @param  array  $data
     * @return array
     */
    public function updateProfile(User $user, array $data): array
    {
        if (array_key_exists('is_phone_show', $data) && !is_null($data['is_phone_show'])) {
            $input = $data['is_phone_show'];
            $data['is_phone_show'] = is_bool($input)
                ? $input
                : in_array(strtolower(trim((string) $input)), ['true', '1', 'on', 'yes'], true);
        }

        if (array_key_exists('is_address_show', $data) && !is_null($data['is_address_show'])) {
            $input = $data['is_address_show'];
            $data['is_address_show'] = is_bool($input)
                ? $input
                : in_array(strtolower(trim((string) $input)), ['true', '1', 'on', 'yes'], true);
        }

        if (!$user->username) {
            $firstName = $data['first_name'] ?? 'user';
            $data['username'] = strtolower($firstName) . '_' . $this->randomAlphaNum(4);
        }

        $user->fill($data);
        $user->save();
        $user->refresh();

        return (new UserProfileResource($user, false))->resolve();
    }

    /**
     * Update user avatar image.
     *
     * @param  User   $user
     * @param  mixed  $avatarFile
     * @return array
     */
    public function updateAvatar(User $user, $avatarFile): array
    {
        if (!empty($user->avatar)) {
            Helper::fileDelete(public_path($user->getRawOriginal('avatar')));
        }

        $avatarPath = Helper::fileUpload($avatarFile, 'user/avatar');
        $user->update(['avatar' => $avatarPath]);

        return [
            'id'         => $user->id,
            'avatar'     => $user->avatar ? asset($user->avatar) : asset('default/profile.jpg'),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Delete user profile and avatar.
     *
     * @param  User  $user
     * @return bool
     */
    public function deleteProfile(User $user): bool
    {
        if (!empty($user->avatar) && file_exists(public_path($user->avatar))) {
            Helper::fileDelete(public_path($user->avatar));
        }

        Auth::logout('api');
        return (bool) $user->forceDelete();
    }

    /**
     * Change user password.
     *
     * @param  User    $user
     * @param  string  $oldPassword
     * @param  string  $newPassword
     * @return bool
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword): bool
    {
        if (!Hash::check($oldPassword, $user->password)) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        return $user->save();
    }

    /**
     * Helper: Generate Random Alphanumeric String
     */
    private function randomAlphaNum(int $length = 4): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }
}
