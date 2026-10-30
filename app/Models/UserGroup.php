<?php

namespace App\Models;

use Database\Factories\UserGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use October\Rain\Database\Model;

/**
 * User group model.
 *
 * Groups a user can belong to, used to demonstrate the relation field types on
 * the user form and as a second resource with its own list and form. An October
 * Rain model, so its logo and documents can use the file upload fields.
 */
#[Fillable(['name', 'code', 'description'])]
class UserGroup extends Model
{
    /** @use HasFactory<UserGroupFactory> */
    use HasFactory;

    /**
     * @var array belongsToMany relations, the users belonging to this group (secondary members)
     */
    public $belongsToMany = [
        'users' => [User::class, 'table' => 'user_user_group'],
    ];

    /**
     * @var array attachOne relations
     */
    public $attachOne = [
        'logo' => File::class,
    ];

    /**
     * @var array attachMany relations
     */
    public $attachMany = [
        'documents' => File::class,
    ];

    /**
     * Exclude the built-in "guest" group. Used by the relation fields so a user
     * cannot be assigned to the guest pseudo-group.
     */
    public function scopeWithoutGuest($query)
    {
        return $query->where('code', '<>', 'guest');
    }
}
