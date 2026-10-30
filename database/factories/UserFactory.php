<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The shared hashed password, computed once for speed.
     */
    protected static ?string $password = null;

    /**
     * A curated set of email domains so the "Email Domain" filter scope has
     * meaningful matches to filter on.
     *
     * @var array<int, string>
     */
    protected static array $domains = ['example.com', 'example.org', 'example.net'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();
        $domain = fake()->randomElement(static::$domains);
        $handle = Str::of($name)->slug('.')->append((string) fake()->numberBetween(1, 999));

        return [
            'name' => $name,
            'email' => $handle.'@'.$domain,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'is_mail_blocked' => fake()->boolean(10),
            'is_two_factor_enabled' => fake()->boolean(25),
            'remember_token' => Str::random(10),
        ];
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
