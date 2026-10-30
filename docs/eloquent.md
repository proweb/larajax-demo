---
subtitle: Drive Amber from plain Eloquent models.
---
# Using Eloquent Models

Amber originated against October Rain's model class, which extends Eloquent with relation
definitions, tree structures and deferred bindings. Amber also works with **plain `Illuminate`
Eloquent models** - most widgets run on them without any changes to the model.

## What works out of the box

With a stock Eloquent model (like Laravel's default `User`):

- **Forms** - all basic field types, tabs, contexts, `getSaveData()`
- **Lists** - all column types (including relation columns), sorting, pagination and **searching**
  (Amber registers the `searchWhere` query methods as Eloquent builder macros)
- **Filters** - every scope type, including the text/number/date/group popover scopes
- **Toolbar** - buttons and search
- **Relation form fields** driven by your Eloquent relation methods
- Field and column **options methods**, model **query scopes** via `modelScope`, and the
  `filterFields` / `filterScopes` model hooks

## How model flavors are bridged

October Rain models declare relations in configuration arrays; plain Eloquent models declare them as
methods. Amber never asks the model directly - widgets probe models through the **model inspector**
service (`model.inspector`), which reads Rain's array definitions when present and derives the
same metadata from your relation methods otherwise.

This means a plain Eloquent model needs **no trait and no changes** - declare relations the normal
Laravel way and Amber understands them:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public function groups()
    {
        return $this->belongsToMany(UserGroup::class);
    }
}
```

The inspector exposes the metadata surface widgets rely on - `hasRelation()`, `getRelationType()`,
`makeRelation()`, `isRelationTypeSingular()`, `getRelationSimpleValue()`, `setRelationValue()` (a
`belongsTo` is associated, other relations hold the related record in memory), `isJsonable()`
(Rain's `$jsonable`, or an array, json, object or collection cast) - and can be used from your
own extensions:

```php
app('model.inspector')->hasRelation($model, 'groups');    // true
app('model.inspector')->getRelationType($model, 'groups'); // 'belongsToMany'
```

## Relation form fields

`type: relation` fields work against your Eloquent relation methods - singular relations
(`belongsTo`, `hasOne`) render as dropdowns, multi relations (`belongsToMany`, `hasMany`) as checkbox
lists, with the current selection applied and `scope` constraints supported:

```yaml
groups:
    label: Groups
    type: relation
    scope: withoutGuest
```

**Saving** relation values is where the flavors differ: October Rain models accept relation values as
attributes, while plain Eloquent needs the relation set explicitly in your save handler. The same
applies to `taglist` fields bound to a relation:

```php
$saveData = $widget->getSaveData();

$user->fill(Arr::except($saveData, ['groups']));
$user->save();

// An empty selection submits an empty string; a missing key means the field was not on the form
if (array_key_exists('groups', $saveData)) {
    $user->groups()->sync($saveData['groups'] ?: []);
}
```

## Reorderable lists

Flat reorderable lists work on any model. Persisting the order needs a sortable implementation -
on plain Eloquent, implement `October\Contracts\Database\SortableInterface` with a `sort_order`
column, as shown in [tree and reorderable lists](lists.md#tree-and-reorderable-lists).

## What still needs October Rain

These features depend on Rain model subsystems and are not supported on plain Eloquent:

- **File upload fields** (`type: fileupload`) - built on Rain's file attachment models and deferred
  binding.
- **Tree structures** in ListStructure - require a model implementing October's `TreeInterface`
  (nested-tree or simple-tree traits).

For these, use an October Rain model (`October\Rain\Database\Model`) as the base class - Rain installs
alongside Amber and works in a plain Laravel application.

## Next steps

- [Forms](forms.md) · [Lists](lists.md) · [Filters](filters.md)
