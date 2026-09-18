<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\RegistrationMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationMember>
 */
class RegistrationMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'team_member_id' => null,
            'name' => fake()->name(),
            'member_role' => fake()->optional()->randomElement(['Danton', 'Pasukan', 'Pembawa Baki']),
            'gender' => fake()->optional()->randomElement(['L', 'P']),
        ];
    }
}
