<?php

namespace MostafaFathi\UserAuth\Database\Seeds;

use Illuminate\Database\Seeder;
use MostafaFathi\UserAuth\Models\UserType;

class UserTypeSeeder extends Seeder
{
    public function run()
    {
        $userTypes = config('user-auth.user_types', []);
        
        foreach ($userTypes as $name => $config) {
            UserType::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $config['label'],
                    'permissions' => $config['permissions'],
                    'is_active' => true,
                ]
            );
        }
    }
}
