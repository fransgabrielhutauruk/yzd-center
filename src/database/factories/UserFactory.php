<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'username'          => fake()->unique()->regexify('[a-z0-9_]{8,15}'),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'role'              => 'member',
            'phone'             => '0812' . fake()->numerify('########'),
            'nik'               => fake()->numerify('3201############'),
            'alamat'            => fake()->address(),
            'status'            => 'active',
            'is_active'         => true,
            'remember_token'    => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'   => 'administrator',
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the user is active / verified.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user is pending verification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => 'pending',
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user has a member role.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'member',
        ]);
    }

    /**
     * Backward-compatible helper: alias to member.
     */
    public function investor(): static
    {
        return $this->member();
    }

    /**
     * Indicate that the user is an affiliator (has referral code).
     */
    public function affiliator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role'          => 'member',
            'referral_code' => 'AFF' . fake()->numerify('####'),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
