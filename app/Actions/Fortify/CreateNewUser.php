<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Models\Inbox;
use App\Models\InboxUserRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        // Assign default client role in the default inbox
        $inbox = Inbox::first();

        // Create a default inbox if none exists (ensuring slug uniqueness)
        if (!$inbox) {
            $baseSlug = Str::slug('General Support');
            $slug = $baseSlug;
            $suffix = 1;

            while (Inbox::where('slug', $slug)->exists()) {
                $suffix++;
                $slug = $baseSlug . '-' . $suffix;
            }

            $inbox = Inbox::create([
                'name' => 'General Support',
                'slug' => $slug,
                'description' => 'Default support inbox',
            ]);
        }

        // Assign client role to the user
        InboxUserRole::create([
            'inbox_id' => $inbox->id,
            'user_id' => $user->id,
            'role' => 'client',
        ]);

        return $user;
    }
}
