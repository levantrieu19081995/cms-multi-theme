<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $usersJson = file_get_contents(base_path('database/data/users.json'));
            $usersData = json_decode($usersJson);

            $printTable = [];

            foreach ($usersData->users as $userData) {
                if (User::whereId($userData->id)->exists()) {
                    continue;
                }
                $user = new User;
                $user->id = $userData->id;
                $user->name = $userData->name;
                $user->email = $userData->email;
                $user->status = $userData->status;
                $user->type = $userData->type;
                $plainPassword = $userData->password;
                $user->password = Hash::make($plainPassword);
                $user->save();

                $user->assignRole();
                $roles = RolesEnum::cases();
                $rolesLabel = [];
                foreach ($roles as $role) {
                    $rolesLabel[] = $role->label();
                }
                $user->syncRoles($rolesLabel);

                $printTable[] = [
                    'email' => $user->email,
                    'password' => $plainPassword
                ];
            }
        });
    }
}
