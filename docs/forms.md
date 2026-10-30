---
subtitle: Build data-editing forms from YAML.
---
# Forms

The Form widget builds a working form from a `fields.yaml` definition bound to a model. Field values
populate from the model, tabs and layout come from configuration, and AJAX features (validation,
uploads, partial refresh) are wired through [Larajax](https://larajax.org) automatically.

## Rendering a form

Describe the fields in a YAML file:

```yaml
# resources/amber/user/fields.yaml
fields:
    name:
        label: Name
        type: text
        span: left

    email:
        label: Email Address
        type: email
        span: right

    is_activated:
        label: Activated
        type: checkbox
```

Build the widget in a controller action with `Form::make(...)`, bind it to a model, and pass it to the
view. The `fields` value may be a YAML file path or an inline array of definitions:

```php
namespace App\Controllers;

use App\Models\User;
use Larajax\Ui\Widgets\Form;

class UserController extends ControllerBase
{
    public function edit($id)
    {
        $widget = Form::make(
            alias: 'form',
            model: User::findOrFail($id),
            fields: '~/resources/amber/user/fields.yaml',
        );

        return view('users.edit', ['widget' => $widget]);
    }
}
```

Render it in the view, inside a `<form>` element with your own submit control:

```blade
<form>
    {{ $widget }}

    {{ Ui::ajaxButton(label: 'Save', handler: 'onSave', primary: true) }}
</form>
```

## Saving submitted data

Handle the submit in an AJAX handler on the controller. `getSaveData()` returns the submitted values,
processed per the field definitions (disabled and hidden fields excluded, arrays normalized):

```php
public function onSave($id)
{
    $user = User::findOrFail($id);

    $widget = $this->makeUserForm($user);

    $user->fill($widget->getSaveData());
    $user->save();
}
```

Build the widget from a shared method so the page action and the AJAX handler configure it the same
way. Like all Larajax handlers, the route must accept POST requests.

## Widget configuration

Option | Description
------------- | -------------
**fields** | field definitions placed outside any tabs: a YAML file path or an array. A YAML file may also carry top-level `tabs` and `secondaryTabs` keys.
**tabs** | primary tab definitions and their fields.
**secondaryTabs** | secondary tab definitions and their fields.
**model** | the model instance the form binds to.
**data** | an optional data source for field values; the model is used when not supplied.
**context** | the active form context; fields declaring another context are not shown. Commonly `create` or `update`.
**arrayName** | wraps field input names in an array, e.g. `User[name]`.
**isNested** | flags this form as rendered inside another form.
**previewMode** | renders the form read-only.
**horizontalMode** | renders labels beside fields instead of above them.
**sessionKey** | a session key used to bind deferred bindings (e.g. file uploads on unsaved records).

## Field properties

Every field supports these common properties, where applicable:

Property | Description
------------- | -------------
**label** | a name when displaying the form field to the user.
**type** | defines how this field should be rendered. Default: `text`.
**span** | aligns the form field to one side. Options: `auto`, `left`, `right`, `row`, `full`, `adaptive`. Default: `full`.
**spanClass** | used with the span `row` property to display the form as a grid, for example, `spanClass: col-4`.
**size** | specifies a field size for fields that use it, for example, the textarea field. Options: `tiny`, `small`, `large`, `huge`, `giant`.
**placeholder** | if the field supports a placeholder value.
**comment** | places a descriptive comment below the field.
**commentAbove** | places a comment above the field.
**commentHtml** | allow HTML markup inside the comment. Options: `true`, `false`.
**default** | specify the default value for the field. For `dropdown`, `checkboxlist`, `radio` and `balloon-selector` fields, you may specify an option key here to have it selected by default.
**defaultFrom** | takes the default value from the value of another model attribute.
**tab** | assigns the field to a tab.
**cssClass** | assigns a CSS class to the field container.
**autoFocus** | flags the field to be focused when the form loads. Default: `false`.
**readOnly** | prevents the field from being modified. Options: `true`, `false`.
**disabled** | prevents the field from being modified and excludes it from the saved data. Options: `true`, `false`.
**hidden** | hides the field from the view and excludes it from the saved data. Options: `true`, `false`.
**stretch** | specifies if this field stretches to fit the parent height.
**context** | specifies what context should be used when displaying the field. Context can also be passed by using an `@` symbol in the field name, for example, `name@update`.
**dependsOn** | an array of other field names this field depends on; when the other fields are modified, this field will update.
**changeHandler** | the name of an AJAX handler to call when the field value is changed, optional.
**trigger** | specify conditions for this field using trigger events.
**required** | places an asterisk next to the field label to indicate it is required. Enforce it with validation on the model - the form does not enforce it.
**attributes** | specify custom HTML attributes to add to the form field element.
**containerAttributes** | specify custom HTML attributes to add to the form field container.
**order** | a numerical weight when determining the display order, default value increments at 100 points per field.

## Tabs

Assign fields to tabs by defining them under `tabs` (or `secondaryTabs`) and naming the tab on each
field:

```yaml
tabs:
    fields:
        username:
            type: text
            label: Username
            tab: Account

        password:
            type: password
            label: Password
            tab: Account

        bio:
            type: textarea
            label: Bio
            tab: Profile
```

Tab definitions also accept these properties:

Property | Description
------------- | -------------
**defaultTab** | the default tab to assign fields to. Default: Misc.
**activeTab** | selected tab when the form first loads, name or index. Default: `1`.
**icons** | assign icons to tabs using tab names as the key.
**lazy** | array of tabs to be loaded dynamically when clicked. Useful for tabs with heavy content.
**stretch** | specifies if this tab stretches to fit the parent height.

## Field types

All fields are identified by their **type** property; `text` is the default.

**text** - single line text box.

**number** - single line text box with number validation.

**password** - single line password box.

**email** - single line text box with email validation.

**textarea** - multiline text box. Sizeable via `size`.

**dropdown** - a select box. Supply the options inline, or omit them to source from the model
(see [field options](#field-options) below):

```yaml
status:
    label: Status
    type: dropdown
    options:
        draft: Draft
        published: Published
```

**radio** - a list of radio options, one selectable. Options may carry descriptions:

```yaml
security_level:
    label: Access
    type: radio
    options:
        all: [All, Guests and members]
        registered: [Registered only, Members only]
```

**checkbox** - a single checkbox.

**checkboxlist** - a list of checkboxes, multiple selectable.

**switch** - a switchable toggle.

**balloon-selector** - a group of pill buttons, one selectable.

### Form UI

Layout elements that render inside the form without binding data:

**section** - a heading and subheading:

```yaml
_section1:
    label: User details
    comment: The information for this person
    type: section
```

**hint** - like a section, rendered inside a dismissible callout. Set `mode` to `tip`, `info`,
`success`, `warning` or `danger`.

**ruler** - a horizontal divider:

```yaml
_ruler1:
    type: ruler
```

**partial** - renders a custom view; receives `$field`, `$formModel` and `$value`:

```yaml
content:
    type: partial
    path: ~/resources/views/users/_content_field.php
```

### Form widgets

Richer fields with their own assets and AJAX handlers. Amber currently ships thirteen:

**relation** - displays a dropdown (singular relations) or checkbox list (multiple relations) sourced
from a model relationship, with optional inline quick-create:

```yaml
groups:
    label: Groups
    type: relation
    nameFrom: name
```

Quick-create adds a "Create New" option to a singular relation dropdown. Picking it opens a popup
with a form for the related model; the new record is saved and selected. Set `quickCreate` to the
fields file for that form:

```yaml
primary_group:
    label: Primary Group
    type: relation
    quickCreate: ~/resources/ui/user_group/fields.yaml
```

Or pass an array to change the defaults: `fields` (the fields file), `title` (popup title,
default `New :name`), `optionText` (default `- Create New :name -`), `context` (form context,
default `quickcreate`) and `popupSize`. `:name` is the field label. Quick-create is not offered in
preview or read-only mode.

**fileupload** - file/image uploader bound to a file attachment:

```yaml
avatar:
    label: Avatar
    type: fileupload
    mode: image
    imageHeight: 260
    imageWidth: 260
```

**repeater** - repeats a nested set of form fields for building lists of structured data. Items can
be added, removed, duplicated, collapsed and drag-reordered:

```yaml
links:
    label: Links
    type: repeater
    prompt: Add Link
    titleFrom: title
    form:
        fields:
            title:
                label: Title
                span: auto
            url:
                label: URL
                span: auto
```

The value is stored as a JSON array on the attribute - cast it with `'array'` or `'json'` on a plain
Eloquent model (or list it in `$jsonable` on an October Rain model). When the field name matches a
model relationship the items are stored as related records instead, each item form bound to its own
model.

Supported properties: `form` (inline fields or a YAML path), `prompt`, `titleFrom` (field shown as
the collapsed item title), `minItems`, `maxItems`, `showReorder`, `showDuplicate`, `itemsExpanded`
(start collapsed when `false`), `useTabs` and `displayMode` (`accordion` or `builder` - builder shows
a selectable item sidebar).

Mixed item types are supported through `groups`, where each group defines its own name, icon,
description and fields; the group code is stored with each item under the `groupKeyFrom` key
(`_group` by default):

```yaml
content:
    label: Content
    type: repeater
    prompt: Add Block
    groups:
        text:
            name: Text
            description: A block of paragraph text
            fields:
                body:
                    type: textarea
        quote:
            name: Quote
            fields:
                quote_text:
                    type: textarea
                attribution: {}
```

**datepicker** - a date and time input with a calendar, which Bootstrap 6 styles as its Datepicker. In
`datetime` mode one input holds the date and the time, and the calendar has hour and minute controls
below the dates; `time` mode shows those hour and minute controls on their own. The value is stored as
`Y-m-d H:i:s`, or just the date (`Y-m-d`) or time (`H:i:s`) in those modes:

```yaml
published_at:
    label: Published
    type: datepicker
    mode: date
    format: d/m/Y
```

Supported properties: `mode` (`datetime`, `date` or `time`; default `datetime`), `format` (a PHP
date format for the date input, using day, month and year characters), `minDate`, `maxDate`,
`yearRange` (years offered either side of the shown year in the calendar's year view, or bounds like
`[1990, 2030]`), `disableDays` (weekday numbers where 0 is Sunday, `Y-m-d` dates, or a
`Class::method` that returns them), `firstDay`, `twelveHour`, `hoursOnly`, `showWeekNumber` and
`defaultTimeMidnight` (when a date is picked without a time, store 00:00 instead of the current
time). The page language (`<html lang>`) sets the default date format, the first day of the week and
the month and weekday names. Preview mode shows the value in the long locale
format, or in `format` when set. In `datetime` mode the value is shown in the user's timezone and
saved in the application timezone once a [timezone resolver](installation.md#timezones) is set;
`useTimezone: false` turns that off.

**taglist** - a tag input for picking several values, with typed-in tags when `customTags` is on:

```yaml
users:
    label: Members
    type: taglist
    nameFrom: name
```

The value is stored to match the attribute: a relation stores the related record keys, a JSON cast
attribute stores an array, and anything else stores a string joined by the separator. Set `mode` to
`relation`, `array` or `string` to choose explicitly.

Supported properties: `options` (fixed options, otherwise the related records), `nameFrom` (the
related column shown as the label, default `name`), `customTags` (allow typing new tags; in relation
mode new records are created), `separator` (`comma` or `space`, default `comma`), `useKey` (store
option keys rather than labels, default `true`, off with `customTags`), `placeholder` and `maxItems`.

**recordfinder** - shows the selected record and finds another in a popup with a searchable,
paginated list. Clicking a row picks it:

```yaml
primary_group:
    label: Primary Group
    type: recordfinder
    list: ~/resources/ui/user_group/columns.yaml
    nameFrom: name
    descriptionFrom: code
```

By default the field name is a relation on the model and the related key is stored. Set
`useRelation: false` with a `modelClass` to store the key of any model in a plain attribute.

Supported properties: `list` (the columns for the popup list), `nameFrom` (default `name`) and
`descriptionFrom` (columns shown for the selected record), `keyFrom` (the key column), `title`
(popup title, default `Find Record`), `recordsPerPage` (default 10), `scope` or `modelScope`
(a model scope that narrows the records), `conditions` (a raw SQL condition), `searchMode`
(`all`, `any` or `exact`), `searchScope` (a model scope that performs the search), `filter` (scopes
for a filter above the list), `defaultSort`, `showSetup`, `structure` (show a tree for tree models),
`inlineOptions` (buttons beside the record, default `true`) and `popupSize` (default `huge`).

**nestedform** - a form inside the form, for a JSON attribute or a related record:

```yaml
address:
    label: Address
    type: nestedform
    form:
        fields:
            street:
                label: Street
            city:
                label: City
                span: left
            postcode:
                label: Postcode
                span: right
```

On a JSON cast attribute the nested values are stored as an object. When the field name is a
relation, the related record is edited directly and saved with the form; `defaultCreate: true`
creates the related record if there is none. `showPanel: false` drops the panel styling. The
`form` property takes inline fields or a YAML file path.

**sensitive** - a masked value, such as an API key, that can be revealed on demand:

```yaml
api_key:
    label: API Key
    type: sensitive
    allowCopy: true
```

The stored value is never sent with the page. Revealing it fetches it through the widget's AJAX
handler, and saving the form without changes keeps it. Supported properties: `mode` (`text` or
`textarea`), `allowCopy` (adds a copy button), `hideOnTabChange` (hides a revealed value when the
browser tab is hidden, default `true`) and `hiddenPlaceholder`.

**colorpicker** - a color input that opens a picker ([Coloris](https://coloris.js.org)), with the
preset colors shown as swatches:

```yaml
brand_color:
    label: Brand Color
    type: colorpicker
    availableColors: ['#1abc9c', '#3498db', '#e74c3c']
```

Values are saved as hex, with a leading `#`. Supported properties: `availableColors` (the swatches,
or the name of a model method that returns them; a default set of 20 is used otherwise),
`allowCustom` (pick any color, default `true`; `false` limits the picker to the swatches),
`allowEmpty` (adds a clear button), `showAlpha` (adds an opacity slider and stores 8 digit hex),
`showInput` (no swatches), `readOnly` and `disabled`.

**richeditor** - a rich text editor that saves HTML, built on [TipTap](https://tiptap.dev):

```yaml
content:
    label: Content
    type: richeditor
    toolbarButtons: [paragraphFormat, bold, italic, '|', formatUL, insertLink, insertImage, '|', html]
```

`toolbarButtons` takes a list, or a string where `|` separates buttons and `||` adds a divider.
Without it the editor shows a default toolbar. Other properties: `size` (`tiny`, `small`,
`large`, `huge` or `giant` sets the editing height), `useLineBreaks` (Enter inserts a line break
instead of a new paragraph), `readOnly`, `disabled`, `safeMode` (cleans the HTML shown in preview
mode, default `true`) and `editorOptions` (extra editor settings, such as `paragraphStyles: { lead:
Lead }` or `imageResize: false`).

`uploadDisk` turns on uploads from the editor: images and file links are stored on that
filesystem disk with hashed names, and the editor inserts the disk's URL for them. `uploadPath`
sets the folder (default `uploads/richeditor`) and `uploadMaxSize` the largest file in megabytes
(default `10`). Images accept jpg, jpeg, png, gif and webp; file links also accept pdf, doc, docx,
xls, xlsx, ppt, pptx, txt, csv and zip. Both the file name and its content must match.

```yaml
content:
    label: Content
    type: richeditor
    uploadDisk: public
```

Media pickers and link pickers are left to the page, and so are uploads when a page prefers its
own endpoint. Connect them with hooks on the shared `richEditor` object before the editors load,
and the matching menu items appear. A page `uploadFile` hook replaces the `uploadDisk` upload, and
its second argument says whether an `image` or a `file` link is being uploaded:

```js
import { richEditor } from '/vendor/larajax/ui/index.js';

richEditor.hooks.uploadFile = async (file, { type }) => {
    const data = new FormData();
    data.append('file', file);
    const response = await fetch('/uploads', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: data
    });
    return (await response.json()).url;
};

richEditor.registerButton('insertSignature', {
    label: 'Insert Signature',
    icon: 'ph ph-signature',
    toolbar: 'end',
    onClick: (editor) => editor.insertHtml('<p>Kind regards</p>')
});
```

The available hooks are `uploadFile`, `browseMedia`, `browseLinks`, `resolveLink`,
`browseSnippets`, `mountSnippet`, `editSnippet`, `alert` and `tooltip`. Pages that display the
saved HTML can include `/vendor/larajax/ui/vendor/richarea/base-styles.css` and wrap the content
in an element with the `rich-editor-content` class. The toolbar and menu icons come from the
configured icon pack (the `richeditor.*` keys), and the editor's labels and messages pass through
`__()`, so they follow the app locale. Without a `mountSnippet` hook, snippets show as a labelled
placeholder with their snippet code and component class.

**codeeditor** - a code editor built on [Monaco](https://microsoft.github.io/monaco-editor/),
the editor behind VS Code:

```yaml
template:
    label: Template
    type: codeeditor
    language: html
    size: huge
```

Languages: `php` (the default), `html`, `css`, `less`, `scss`, `javascript`, `json`,
`markdown`, `twig`, `yaml` and `plain_text`. HTML, CSS and JSON also get completion and
validation, and Emmet abbreviations expand in the HTML and CSS languages. The toolbar has undo
and redo, find and replace, format (for languages with a formatter, such as HTML, CSS and JSON),
a word wrap toggle (`Alt+Z`) and fullscreen (`Ctrl+Shift+F`, `Esc` to leave), with the language
name on the right.

Other properties: `theme` (`vs`, `vs-dark`, `hc-black` or `hc-light`; without it the editor
follows the page's light or dark mode), `fontSize`, `tabSize`, `useSoftTabs`, `wordWrap` (`off`,
`fluid`, `40` or `80`), `showGutter`, `showInvisibles`, `highlightActiveLine`,
`displayIndentGuides`, `showPrintMargin`, `codeFolding` (`manual` shows fold controls on hover,
`markbegin` always), `autoClosing`, `autocompletion` (`live` suggests while typing, `manual` and
`basic` on `Ctrl+Space`), `enableSnippets`, `margin`, `readOnly` and `disabled`.

Monaco loads the first time a page shows a code field, so other pages don't pay for its size.
Monaco has one theme per page, so fields that name different themes share the last one applied.

**markdown** - a Markdown editor with a formatting toolbar and preview, built on
[EasyMDE](https://github.com/Ionaru/easy-markdown-editor):

```yaml
content:
    label: Content
    type: markdown
    sideBySide: true
```

The toolbar has bold, italic, strikethrough, headings 1 to 3, code blocks, quotes, lists, clean
block, links, images, tables and horizontal rules, then preview, side by side and fullscreen, with
icons from the configured icon pack. EasyMDE's keyboard shortcuts work as well, such as `Ctrl+B`,
`Ctrl+I`, `Ctrl+K` for a link, `Ctrl+P` for the preview, `F9` for side by side and `F11` for
fullscreen.

The preview is rendered in the browser with [marked](https://marked.js.org) and cleaned with
[DOMPurify](https://github.com/cure53/DOMPurify). Other properties: `sideBySide` (start side by
side), `mode` (`tab` or `split`, another way to make the same choice), `safeMode` (DOMPurify on the
preview, default `true`; `false` shows HTML in the Markdown as written), `size` (sets the editor
height), `readOnly` and `disabled` (both show the rendered Markdown in place of the editor).

EasyMDE loads the first time a page shows a Markdown field.

**datatable** - an editable grid of rows, like a small spreadsheet, built on Hotsheet (the
library's own data grid). Cells are edited in place, and the rows are saved as a JSON array of
objects keyed by column name:

```yaml
contacts:
    label: Contacts
    type: datatable
    columns:
        name:
            title: Name
        type:
            title: Type
            type: dropdown
            options:
                work: Work
                personal: Personal
        primary:
            title: Primary
            type: checkbox
        since:
            title: Since
            type: date
```

Cast the attribute with `'array'` or `'json'` on a plain Eloquent model (or list it in
`$jsonable` on an October Rain model). Rows with no values are left out when saving.

Column types: `string` (the default, plain text), `numeric`, `checkbox` (stores `1` or `0`),
`dropdown` (pick one of the options; the option key is stored and its label shown), `autocomplete`
(suggests the options as you type and accepts any text), `date` and `time`. Each column takes a
`title`, a `width` (pixels such as `100px`, or a percentage of the table such as `20%`; columns
without one share the remaining space), `readOnly`, `wrap` (wrap long text instead of cutting it
off), `dateFormat` (default `YYYY-MM-DD`) or `timeFormat` (default `HH:mm`), and `validation`.

A `dropdown` or `autocomplete` column without `options` asks the model for them over AJAX, calling
`get<FieldName>DataTableOptions($column, $rowData)` or, failing that,
`getDataTableOptions($fieldName, $column, $rowData)`. `$rowData` holds the row's current values, so
`dependsOn` can make one column's options follow another: changing the country clears the city and
loads the cities again. So saved values show their labels straight away, the field also calls the
method while rendering, once for each set of `dependsOn` values among the saved rows (once in all
for a column without `dependsOn`), and the grid only asks over AJAX for values it hasn't seen.

```yaml
columns:
    country:
        title: Country
        type: dropdown
        options:
            au: Australia
            nz: New Zealand
    city:
        title: City
        type: dropdown
        dependsOn: country
```

```php
public function getDataTableOptions($fieldName, $column, $rowData)
{
    if ($column === 'city') {
        return City::where('country', $rowData['country'] ?? null)->pluck('name', 'code')->all();
    }

    return [];
}
```

`validation` checks cells in the browser and flags the invalid ones. The rules are `required`,
`integer` and `float` (with `allowNegative`, `min` and `max`), `length` (`min`, `max`) and `regex`
(`pattern`, `modifiers`); each takes a `message`, and `requiredWith` applies a rule only when
another column of the row has a value. `min` and `max` take `{ value, message }`. With
`requireValid: true` the form does not save while a row with values has an invalid cell; the first
one is selected and its message flashed. A request counts as saving when its handler is the form's
own `data-request` (as in `<form data-request="onUpdate">`) or is named `onSave`:

```yaml
email:
    title: Email
    validation:
        required:
            message: Enter an email address.
        regex:
            pattern: '^[^@\s]+@[^@\s]+\.[^@\s]+$'
```

Field properties: `adding` and `deleting` (default `true`) allow adding and removing rows, `toolbar`
(default `true`) shows the toolbar with the Insert Row and Delete Row buttons, `searching` adds a
search box that highlights matching cells, `csvExport` and `csvImport` add Export and Import
buttons, `sorting` sorts by a column when its header is clicked (for viewing; the saved order stays),
`reorderRows` adds row handles for dragging rows into a new order, `reorderColumns` lets column
headers be dragged and frozen (for viewing), `height` sets a height in pixels so the grid scrolls
inside itself, `placeholder` shows a message while there are no rows (the table otherwise starts
with one empty row), `multiSelect` (default `true`) allows selecting a range of cells, and
`requireValid`. With `readOnly`, `disabled` or a form in preview mode, the rows can be selected,
copied, searched, sorted and exported but not edited, and the Insert Row, Delete Row and Import
buttons are left out.

Export downloads the rows as a CSV file with the column titles as its header row, and values a
spreadsheet would read as a formula are prefixed with an apostrophe. Import reads a CSV file
separated by commas, semicolons or tabs and appends its rows; a header row is matched to the column
titles or names, otherwise the values are read in column order.

The grid works like a spreadsheet from the keyboard: arrow keys, Tab and Enter move between cells,
typing replaces a cell, Enter or F2 edits it, Escape cancels an edit, list, menu or drag (with
nothing to cancel it closes a popup holding the grid), Delete clears the selection, and
`Ctrl+C`, `Ctrl+X`, `Ctrl+V`, `Ctrl+Z` and `Ctrl+Y` copy, cut, paste, undo and redo, also between
the grid and a spreadsheet. `Alt+A` adds a row below the selected one (`Alt+Shift+A` above it) and
`Alt+D` deletes it. A right click opens a menu for inserting and removing rows. Listen for the
`datatable:new-row` event to give new rows default values:

```js
addEventListener('datatable:new-row', (event) => {
    event.detail.row.type = 'work';
});
```

Relation fields work with both October Rain and plain Eloquent models - see
[Using Eloquent models](eloquent.md).

::: warning
File upload fields require an October Rain model, as they are built on Rain's file attachment
subsystem - see [Using Eloquent models](eloquent.md).
:::

## Field options

Option-based fields (`dropdown`, `radio`, `checkboxlist`, `balloon-selector`) resolve their options in
this order:

1. An inline `options` array in the YAML, as shown above.
2. An `options` method on the model. Omit the property entirely and Amber calls
   `get<Field>Options()`, falling back to `getDropdownOptions()`:

```php
public function getStatusOptions()
{
    return ['draft' => 'Draft', 'published' => 'Published'];
}
```

3. An explicit static or instance method named by the property:

```yaml
status:
    type: dropdown
    options: listStatuses
```

### Preset options

Amber ships preset option lists for common cases, referenced with the `preset:` prefix:

```yaml
timezone:
    label: Timezone
    type: dropdown
    options: preset:timezones
```

Available presets: `timezones`, `locales`, `flags`, `flags@short`, `icons`, `icons@phosphor`,
`icons@bootstrap`. Applications can register or extend presets through the preset manager:

```php
use Larajax\Ui\Classes\PresetManager;

PresetManager::instance()->registerPreset('priorities', function () {
    return ['low' => 'Low', 'high' => 'High'];
});
```

## Contexts

Fields can be limited to a context, so one YAML file serves both create and update forms:

```yaml
password:
    label: Password
    type: password
    context: create
```

Pass the active context when building the widget (`'context' => 'update'`), or append it to the field
name with `@` for per-context field variants (`password@create`).

## Extending the form

Subclass the widget and override its extension points, declared in `HasFormEvents`:

```php
use Larajax\Ui\Widgets\Form;
use October\Rain\Element\ElementHolder;

class UserForm extends Form
{
    protected function eventExtendFieldsBefore(): void
    {
        // Add a field before configuration is parsed
        $this->addFormField('birthday', 'Birthday')->displayAs('text');
    }

    protected function eventExtendFields(ElementHolder $fields): void
    {
        // Modify defined fields
        $fields->email->hidden = true;
    }
}
```

Available overrides: `eventExtendFieldsBefore`, `eventExtendFields`, `eventBeforeRefresh`,
`eventRefreshFields`, `eventRefresh`.

Models can also filter their own form fields by defining a `filterFields` method, called whenever the
form renders or refreshes:

```php
public function filterFields($fields, $context = null)
{
    $fields->email->hidden = !$this->is_registered;
}
```

## Next steps

- [Lists](lists.md) - the record list that links to this form
- [UI Elements](ui.md) - the `Ui::ajaxButton` used to submit the form
- [Using Eloquent models](eloquent.md) - driving Amber from plain `Illuminate` models
