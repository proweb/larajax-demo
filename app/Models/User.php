<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use October\Contracts\Database\SortableInterface;

/**
 * User model.
 *
 * A stock Illuminate Eloquent model driving the Amber Form, List, Filter and
 * ListStructure widgets. The SortableInterface contract lets the reorderable
 * list persist a manual ordering.
 */
#[Fillable([
    'name',
    'email',
    'email_verified_at',
    'password',
    'notes',
    'links',
    'contacts',
    'is_mail_blocked',
    'is_two_factor_enabled',
    'primary_group_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements SortableInterface
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'links' => 'array',
            'contacts' => 'array',
            'is_mail_blocked' => 'boolean',
            'is_two_factor_enabled' => 'boolean',
        ];
    }

    /**
     * The primary group this user belongs to (belongs-to relation, rendered by
     * the "Primary Group" relation field on the form).
     */
    public function primary_group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'primary_group_id');
    }

    /**
     * The secondary groups this user belongs to (many-to-many relation,
     * rendered by the "Secondary Groups" relation field on the form).
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'user_user_group');
    }

    /**
     * Filter users by email domain. Used by the "Email Domain" filter scope
     * (see resources/ui/user/scopes.yaml).
     */
    public function scopeApplyDomain($query, $scope)
    {
        return $query->where('email', 'like', '%@'.$scope->value);
    }

    /**
     * Options for the "Name" group filter scope.
     *
     * @return array<string, string>
     */
    public static function getNameGroupOptions(): array
    {
        return static::query()->orderBy('name')->pluck('name', 'name')->all();
    }

    /**
     * Options for the AJAX dropdown columns of the "Contacts" data table, where
     * the cities depend on the country picked in the same row.
     *
     * @param  array<string, mixed>  $rowData
     * @return array<string, string>
     */
    public function getDataTableOptions(string $fieldName, ?string $column, array $rowData = []): array
    {
        if ($fieldName !== 'contacts' || $column !== 'city') {
            return [];
        }

        $cities = [
            'au' => ['sydney' => 'Sydney', 'melbourne' => 'Melbourne', 'brisbane' => 'Brisbane'],
            'nz' => ['auckland' => 'Auckland', 'wellington' => 'Wellington'],
            'us' => ['new-york' => 'New York', 'chicago' => 'Chicago', 'seattle' => 'Seattle'],
        ];

        return $cities[$rowData['country'] ?? ''] ?? [];
    }

    /**
     * Persist a new record ordering. Called by the ListStructure widget when
     * rows are dragged into a new order.
     *
     * @param  array<int, int|string>  $itemIds
     * @param  array<int, int>|null  $itemOrders
     */
    public function setSortableOrder($itemIds, $itemOrders = null): void
    {
        if (! is_array($itemIds)) {
            return;
        }

        foreach (array_values($itemIds) as $index => $id) {
            static::withoutEvents(function () use ($id, $index) {
                static::query()->whereKey($id)->update(['sort_order' => $index + 1]);
            });
        }
    }
}
