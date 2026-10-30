<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * A stable set of groups so the relation fields and filters have
     * predictable, meaningful data to work with. The "guest" group is excluded
     * from the relation pickers by UserGroup::scopeWithoutGuest().
     *
     * @var array<int, array{name: string, code: string, description: string}>
     */
    protected array $groups = [
        ['name' => 'Administrators', 'code' => 'admin', 'description' => 'Full access to every area of the application.'],
        ['name' => 'Editors', 'code' => 'editors', 'description' => 'Can create and manage content.'],
        ['name' => 'Support', 'code' => 'support', 'description' => 'Handles customer support tickets.'],
        ['name' => 'Subscribers', 'code' => 'subscribers', 'description' => 'Standard registered members.'],
        ['name' => 'Guest', 'code' => 'guest', 'description' => 'The anonymous pseudo-group. Hidden from relation pickers.'],
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $groups = collect($this->groups)->mapWithKeys(
            fn (array $group) => [$group['code'] => UserGroup::create($group)]
        );

        // Groups eligible to be assigned to users (everyone except "guest").
        $assignable = $groups->except('guest')->values();

        // A known demo account so reviewers always have a stable record to open.
        $this->createUser(
            User::factory()->make([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
            ]),
            $assignable
        );

        User::factory()
            ->count(49)
            ->make()
            ->each(fn (User $user) => $this->createUser($user, $assignable));

        // Assign a deterministic sort order matching insertion order.
        User::query()->orderBy('id')->get()->each(function (User $user, int $index) {
            $user->forceFill(['sort_order' => $index + 1])->saveQuietly();
        });
    }

    /**
     * Persist a user, then attach a primary group and one or two secondary
     * groups so the relation fields render populated.
     *
     * @param  \Illuminate\Support\Collection<int, UserGroup>  $assignable
     */
    protected function createUser(User $user, $assignable): void
    {
        $primary = $assignable->random();
        $user->primary_group_id = $primary->id;
        $user->save();

        $secondary = $assignable->random(rand(1, 2))->pluck('id');
        $user->groups()->sync($secondary);
    }
}
