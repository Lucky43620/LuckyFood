<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'fatsecret_region' => ['nullable', 'string', 'size:2'],
            'fatsecret_language' => ['nullable', 'string', 'max:10'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                ...$this->fatSecretLocaleAttributes($input),
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
            ...$this->fatSecretLocaleAttributes($input),
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    protected function fatSecretLocaleAttributes(array $input): array
    {
        return [
            'fatsecret_region' => strtoupper((string) ($input['fatsecret_region'] ?? 'FR')),
            'fatsecret_language' => strtolower((string) ($input['fatsecret_language'] ?? 'fr')),
        ];
    }
}
