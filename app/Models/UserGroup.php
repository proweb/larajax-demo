<?php

namespace App\Models;

use Database\Factories\UserGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * User group model.
 *
 * Groups a user can belong to, used to demonstrate the relation field types on
 * the user form and as a second resource with its own list and form. A stock
 * Eloquent model, so its logo and documents use the file upload fields.
 */
#[Fillable(['name', 'code', 'description'])]
class UserGroup extends Model
{
    /** @use HasFactory<UserGroupFactory> */
    use HasFactory;

    /**
     * users are the members holding this group as one of their secondary groups.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_user_group');
    }

    /**
     * logo is the group's single image upload.
     */
    public function logo(): MorphOne
    {
        return $this->morphOne(File::class, 'attachment')->withAttributes(['field' => 'logo']);
    }

    /**
     * documents are the group's file uploads, in their manual order.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(File::class, 'attachment')->withAttributes(['field' => 'documents'])->orderBy('sort_order');
    }

    /**
     * Exclude the built-in "guest" group. Used by the relation fields so a user
     * cannot be assigned to the guest pseudo-group.
     */
    public function scopeWithoutGuest($query)
    {
        return $query->where('code', '<>', 'guest');
    }
}
