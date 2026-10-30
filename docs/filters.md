---
subtitle: Add scope-based filtering to a list.
---
# Filters

The Filter widget renders a row of filter scopes - checkboxes, switches and dropdowns - defined in a
`scopes.yaml` file. Scope values persist in the session and apply themselves as constraints to a
database query, typically the query behind a [List widget](lists.md).

## Rendering a filter

Describe the scopes in a YAML file:

```yaml
# resources/amber/user/scopes.yaml
scopes:
    verified:
        label: Verified
        type: checkbox
        conditions: email_verified_at is not null

    domain:
        label: Email Domain
        type: dropdown
        emptyOption: All Domains
        modelScope: applyDomain
        options:
            example.com: Example.com
            example.org: Example.org
```

Build the widget with `Filter::make(...)`. The `scopes` value may be a YAML file path or an inline
array:

```php
use Larajax\Ui\Widgets\Filter;

$filter = Filter::make(
    alias: 'filter',
    model: new User,
    scopes: '~/resources/amber/user/scopes.yaml',
);
```

Render it in the view, above the list it filters:

```blade
{{ $filter }}

{{ $widget }}
```

## Connecting a filter to a list

Two pieces of glue connect the widgets. First, constrain the list query with the filter's scopes:

```php
$widget->addFilter([$filter, 'applyAllScopesToQuery']);
```

Second, refresh the list when a scope changes. Subclass the filter and extend the update response with
the re-rendered list - changing a scope then updates both widgets in one AJAX round trip:

```php
namespace App\Widgets;

use Larajax\Ui\Widgets\Filter;

class UserFilter extends Filter
{
    public $parentList;

    public function init()
    {
        parent::init();

        // Inherit the base widget partials
        $this->addViewPathFrom(Filter::class);
    }

    protected function eventUpdate(array $result, array $params): array
    {
        if ($this->parentList) {
            $result = $result + $this->parentList->onFilter();
        }

        return $result;
    }
}
```

Wire it all together in the controller action:

```php
$filter = UserFilter::make(
    alias: 'filter',
    model: new User,
    scopes: '~/resources/amber/user/scopes.yaml',
);

$widget = Lists::make(
    alias: 'list',
    model: new User,
    columns: '~/resources/amber/user/columns.yaml',
    recordsPerPage: 10,
);

$widget->addFilter([$filter, 'applyAllScopesToQuery']);
$filter->parentList = $widget;
```

Applying a filter also resets the list's shared page parameter, so filtered results always start at
page one.

## Scope properties

Every scope supports these properties, where applicable:

Property | Description
------------- | -------------
**label** | a name when displaying the filter scope to the user.
**type** | defines how this scope should be displayed.
**conditions** | a raw where query statement to apply to the list query when the scope is active.
**modelClass** | class of the model to use as a data source and reference for local method calls.
**modelScope** | specifies a model query scope method to apply to the list query. The first argument contains the query object and the second argument contains the scope definition, including its populated value.
**options** | options to use if filtering by multiple items, supplied as an array.
**optionsMethod** | request options from a method name defined on the model or as a static method call, eg `Class::method`.
**emptyOption** | an optional label for an intentional empty selection.
**default** | supply a default value for the filter, as either array, string or integer depending on the filter value.
**permissions** | gate abilities the current user must pass for the scope to be shown. A string for a single ability, or an array where any one grants access. Checked with Laravel's `Gate`.
**dependsOn** | a string or an array of other scope names this scope depends on. When the other scopes are modified, this scope resets.
**nameFrom** | a model attribute name used for displaying the filter label. Default: `name`.
**valueFrom** | defines a model attribute to use for the source value. Default comes from the scope name.
**order** | a numerical weight when determining the display order, default value increments at 100 points per scope.
**after** | place this scope after another existing scope name using the display order (+1).
**before** | place this scope before another existing scope name using the display order (-1).

## Scope types

**checkbox** - a single on/off filter. Unchecked applies nothing; checked applies the scope's
constraint. Supply the constraint as raw SQL `conditions`, a `modelScope`, or omit both to compare the
column (`valueFrom` or the scope name) against the value:

```yaml
verified:
    label: Verified
    type: checkbox
    conditions: email_verified_at is not null
```

**switch** - a three-state toggle that cycles unchecked (no filter), indeterminate (off) and checked
(on). Supply `conditions` as an array of two raw SQL statements - the first applies in the off state,
the second in the on state. Without conditions, the column is compared against `false`/`true`:

```yaml
is_approved:
    label: Approved
    type: switch
    conditions:
        - is_approved <> true
        - is_approved = true
```

**dropdown** - a select of options, one selectable. Options come from an inline array, an
`optionsMethod` on the model, or the model options convention. The constraint is a raw SQL `conditions`
string with a `:value` placeholder, a `modelScope`, or a plain column comparison:

```yaml
status:
    label: Status
    type: dropdown
    emptyOption: All Statuses
    conditions: status = :value
    options:
        active: Active
        blocked: Blocked
```

### Popover scope types

The following types render as a clickable label that opens a popover with a small condition form.
Apply and Clear buttons submit the scope through AJAX.

**text** - filter a text column by an `equals` or `contains` condition:

```yaml
email:
    label: Email
    type: text
```

**number** - filter a numeric column by `equals`, `between`, `greater` or `lesser`:

```yaml
id:
    label: ID
    type: number
```

**date** - filter a date column by `equals`, `notEquals`, `between`, `before` or `after`, using a date
picker. Picked days are read in the user's timezone once a [timezone resolver](installation.md#timezones)
is set (`useTimezone: false` turns that off):

```yaml
created_at:
    label: Created
    type: date
```

For text, number and date, limit the conditions offered by listing them in `conditions` - a single
condition removes the selector entirely. A condition may also map to a raw SQL statement using the
placeholders `:value` (plus `:min`/`:max` for number, `:after`/`:before` for date):

```yaml
id:
    label: ID
    type: number
    conditions:
        between: true
```

**group** - filter by a set of selected options with include/exclude modes and a searchable option
list. Options come from a model relationship named by the scope (searchable via `nameFrom`), an
`options` array, or an `optionsMethod`:

```yaml
groups:
    label: Groups
    type: group
    nameFrom: name
```

Set `matchMode: toggle` to let the user switch between include and exclude modes.

## Applying model scopes

The `modelScope` property applies custom constraints through a
[model query scope](https://laravel.com/docs/eloquent#query-scopes). The scope method receives the
query and the scope definition, whose `value` property holds the selected value:

```yaml
domain:
    label: Email Domain
    type: dropdown
    modelScope: applyDomain
    options:
        example.com: Example.com
```

```php
public function scopeApplyDomain($query, $scope)
{
    $query->where('email', 'like', '%@'.$scope->value);
}
```

`modelScope` may also reference a static class method with `Class::method`:

```yaml
domain:
    modelScope: "App\Filters\UserFilters::applyDomain"
```

## Scope dependencies

The `dependsOn` property links scopes together: when a dependency changes, the dependent scope resets
and re-renders. Combine it with `optionsMethod` to narrow the dependent scope's options - the method
receives the full set of scope definitions with their current values:

```yaml
country:
    label: Country
    type: dropdown

state:
    label: State
    type: dropdown
    dependsOn: country
    optionsMethod: getStateOptionsForFilter
```

```php
public function getStateOptionsForFilter($scopes = null)
{
    if ($scopes->country && ($countryId = $scopes->country->value)) {
        return static::where('country_id', $countryId)->pluck('name', 'id');
    }

    return static::pluck('name', 'id');
}
```

## Extending the filter

Subclass the widget and override its extension points, declared in `HasFilterEvents`:

```php
use Larajax\Ui\Widgets\Filter;

class UserFilter extends Filter
{
    protected function eventExtendScopes(): void
    {
        $this->addScopes([
            'role' => ['label' => 'Role', 'type' => 'dropdown'],
        ]);
    }
}
```

Available overrides: `eventExtendScopesBefore`, `eventExtendScopes`, `eventOverrideHeaderValue`,
`eventUpdate`, `eventExtendQuery`.

Models can also filter their own scopes by defining a `filterScopes($scopes, $context)` method, called
whenever the filter renders.

Applied scope values persist in the session.

## Next steps

- [Lists](lists.md) - the record list this filter constrains
- [Forms](forms.md) - build the edit form the list links to
