<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * makeTableForm builds a form with one data table field named contacts.
     */
    protected function makeTableForm(User $user, array $config = []): Form
    {
        $form = Form::createIn(new UsersController, [
            'model' => $user,
            'fields' => [
                'contacts' => $config + [
                    'label' => 'Contacts',
                    'type' => 'datatable',
                    'columns' => [
                        'name' => ['title' => 'Name'],
                        'country' => ['title' => 'Country', 'type' => 'dropdown', 'options' => ['au' => 'Australia', 'nz' => 'New Zealand']],
                        'city' => ['title' => 'City', 'type' => 'dropdown', 'dependsOn' => 'country'],
                    ],
                ],
            ],
        ]);

        $form->bindToController();

        return $form;
    }

    /**
     * postRequest swaps in a request carrying the given post data.
     */
    protected function postRequest(array $data): void
    {
        $this->app->instance('request', Request::create('/users', 'POST', $data));
        Facade::clearResolvedInstance('request');
    }

    /**
     * attributeJson decodes the JSON of a data attribute rendered on the field.
     */
    protected function attributeJson(string $html, string $attribute): mixed
    {
        $this->assertMatchesRegularExpression("/{$attribute}='([^']*)'/", $html);
        preg_match("/{$attribute}='([^']*)'/", $html, $match);

        return json_decode(html_entity_decode($match[1], ENT_QUOTES), true);
    }

    /**
     * ajax posts an AJAX handler to the demo user's edit page.
     */
    protected function ajax(User $user, string $handler, array $data = [])
    {
        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", $data + [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_the_demo_field_renders_the_grid_with_its_toolbar_and_saved_rows(): void
    {
        $user = User::factory()->create(['contacts' => [['name' => 'Ada', 'email' => 'ada@example.com', 'primary' => 1]]]);

        $html = $this->get("/users/{$user->id}/edit")->assertOk()->getContent();

        $this->assertStringContainsString('data-control="datatable"', $html);
        $this->assertStringContainsString('data-alias="formContacts"', $html);
        $this->assertStringContainsString('data-require-valid="true"', $html);
        $this->assertStringContainsString('<div data-hot-container></div>', $html);
        $this->assertMatchesRegularExpression('/data-table-add>\s*<i class="ph ph-list-plus" aria-hidden="true"><\/i>\s*<span class="button-label">Insert Row<\/span>/', $html);
        $this->assertStringContainsString('<span class="button-label">Delete Row</span>', $html);
        $this->assertStringContainsString('<span class="button-label">Export</span>', $html);
        $this->assertStringContainsString('<span class="button-label">Import</span>', $html);
        $this->assertStringContainsString('data-table-import-file', $html);
        $this->assertStringContainsString('placeholder="Search..." data-table-search', $html);
        $this->assertStringNotContainsString('handsontable', $html);

        $this->assertSame(['city'], $this->attributeJson($html, 'data-ajax-columns'));
        $this->assertSame(['city' => 'country'], $this->attributeJson($html, 'data-column-dependencies'));
        $this->assertSame('Remove row', $this->attributeJson($html, 'data-lang')['removeRow']);

        $columns = collect($this->attributeJson($html, 'data-columns'))->keyBy('data');
        $this->assertSame(['name', 'email', 'type', 'country', 'city', 'primary', 'since'], $columns->keys()->all());
        $this->assertSame('text', $columns['name']['type']);
        $this->assertSame(['required' => ['message' => 'Enter an email address.'], 'regex' => ['pattern' => '^[^@\s]+@[^@\s]+\.[^@\s]+$', 'message' => 'Enter a valid email address.']], $columns['email']['_validation']);
        $this->assertSame([['key' => 'work', 'value' => 'Work'], ['key' => 'personal', 'value' => 'Personal'], ['key' => 'billing', 'value' => 'Billing']], $columns['type']['source']);
        $this->assertTrue($columns['type']['strict']);
        $this->assertArrayNotHasKey('source', $columns['city']);
        $this->assertSame(['type' => 'checkbox', 'checkedTemplate' => 1, 'uncheckedTemplate' => 0], array_intersect_key($columns['primary'], array_flip(['type', 'checkedTemplate', 'uncheckedTemplate'])));
        $this->assertSame('YYYY-MM-DD', $columns['since']['dateFormat']);
        $this->assertSame(80, $columns['primary']['width']);

        $this->assertMatchesRegularExpression('/name="contacts" value="\[\{&quot;name&quot;:&quot;Ada&quot;,&quot;email&quot;:&quot;ada@example.com&quot;,&quot;primary&quot;:1\}\]"/', $html);
    }

    public function test_an_empty_value_renders_an_empty_list(): void
    {
        $html = (string) $this->makeTableForm(User::factory()->create())->render();

        $this->assertStringContainsString('name="contacts" value="[]"', $html);
        $this->assertSame(1, $this->attributeJson($html, 'data-hot-options')['minRows']);
    }

    public function test_options_render_as_grid_settings(): void
    {
        $html = (string) $this->makeTableForm(User::factory()->create(), [
            'placeholder' => 'No contacts yet',
            'height' => 300,
            'sorting' => true,
            'reorderRows' => true,
            'reorderColumns' => true,
            'multiSelect' => false,
        ])->render();

        $options = $this->attributeJson($html, 'data-hot-options');
        $this->assertSame(['message' => ['title' => 'No contacts yet']], $options['emptyDataState']);
        $this->assertArrayNotHasKey('minRows', $options);
        $this->assertSame(300, $options['height']);
        $this->assertTrue($options['columnSorting']);
        $this->assertTrue($options['rowHeaders']);
        $this->assertTrue($options['manualRowMove']);
        $this->assertTrue($options['manualColumnMove']);
        $this->assertTrue($options['manualColumnFreeze']);
        $this->assertSame('single', $options['selectionMode']);
    }

    public function test_the_toolbar_follows_the_adding_deleting_and_toolbar_options(): void
    {
        $user = User::factory()->create();

        $locked = (string) $this->makeTableForm($user, ['adding' => false, 'deleting' => false])->render();
        $this->assertStringContainsString('data-adding="false"', $locked);
        $this->assertStringContainsString('data-deleting="false"', $locked);
        $this->assertStringNotContainsString('data-table-add', $locked);
        $this->assertStringNotContainsString('data-table-delete', $locked);
        $this->assertStringNotContainsString('data-table-export', $locked);
        $this->assertStringNotContainsString('data-table-search', $locked);

        $bare = (string) $this->makeTableForm($user, ['toolbar' => false])->render();
        $this->assertStringNotContainsString('data-table-toolbar', $bare);
        $this->assertStringContainsString('data-adding="true"', $bare);
    }

    public function test_columns_map_types_widths_and_text_options(): void
    {
        $html = (string) $this->makeTableForm(User::factory()->create(), [
            'columns' => [
                'qty' => ['title' => 'Qty', 'type' => 'numeric', 'width' => '15%', 'readOnly' => true],
                'note' => ['title' => 'Note', 'wrap' => true, 'width' => '200px'],
                'tag' => ['title' => 'Tag', 'type' => 'autocomplete', 'options' => ['a' => 'Alpha']],
                'at' => ['title' => 'At', 'type' => 'time', 'timeFormat' => 'h:mm A'],
                'source' => ['type' => 'autocomplete', 'options' => 'getSources'],
            ],
        ])->render();

        $columns = collect($this->attributeJson($html, 'data-columns'))->keyBy('data');
        $this->assertSame(['data' => 'qty', 'title' => 'Qty', 'readOnly' => true, 'width' => '15%', 'type' => 'numeric'], $columns['qty']);
        $this->assertSame(200, $columns['note']['width']);
        $this->assertTrue($columns['note']['wordWrap']);
        $this->assertSame(['autocomplete', false, true], [$columns['tag']['type'], $columns['tag']['strict'], $columns['tag']['filter']]);
        $this->assertSame('h:mm A', $columns['at']['timeFormat']);
        $this->assertSame('source', $columns['source']['title']);
        $this->assertSame(['source'], $this->attributeJson($html, 'data-ajax-columns'));
    }

    public function test_the_rows_save_in_their_posted_shape(): void
    {
        $user = User::factory()->create();

        $rows = [
            ['name' => 'Ada', 'email' => 'ada@example.com', 'type' => 'work', 'country' => 'au', 'city' => 'sydney', 'primary' => 1, 'since' => '2026-01-15'],
            ['name' => 'Bob', 'email' => 'bob@example.com', 'type' => 'personal', 'country' => 'nz', 'city' => null, 'primary' => 0, 'since' => null],
        ];

        $this->ajax($user, 'onUpdate', ['contacts' => json_encode($rows)])->assertOk();

        $this->assertSame($rows, $user->refresh()->contacts);
        $this->assertSame($rows, json_decode($user->getRawOriginal('contacts'), true));
    }

    public function test_an_empty_or_invalid_post_saves_an_empty_list(): void
    {
        $widget = $this->makeTableForm(User::factory()->create())->getFormWidget('contacts');

        $this->assertSame([], $widget->getSaveValue('[]'));
        $this->assertSame([], $widget->getSaveValue(''));
        $this->assertSame([], $widget->getSaveValue('not json'));
    }

    public function test_dropdown_options_come_from_the_model_for_the_row(): void
    {
        $response = $this->ajax(User::factory()->create(), 'formContacts::onGetDropdownOptions', [
            'column' => 'city',
            'rowData' => ['name' => 'Ada', 'country' => 'nz', 'city' => ''],
        ]);

        $response->assertOk();
        $this->assertSame(['auckland' => 'Auckland', 'wellington' => 'Wellington'], $response->json('options'));
    }

    public function test_a_field_specific_options_method_wins_over_the_generic_one(): void
    {
        $user = new class extends User {
            protected $table = 'users';

            public function getContactsDataTableOptions($column, $rowData): array
            {
                return ['x' => $column.':'.($rowData['country'] ?? '')];
            }
        };

        $widget = $this->makeTableForm($user)->getFormWidget('contacts');

        $this->postRequest(['column' => 'city', 'rowData' => ['country' => 'au']]);
        $this->assertSame(['options' => ['x' => 'city:au']], $widget->onGetDropdownOptions());
    }

    public function test_a_model_without_options_methods_returns_no_options(): void
    {
        $user = new class extends \Illuminate\Database\Eloquent\Model {
            protected $table = 'users';
        };

        $form = Form::createIn(new UsersController, [
            'model' => $user,
            'fields' => ['contacts' => ['type' => 'datatable', 'columns' => ['city' => ['type' => 'dropdown']]]],
        ]);
        $form->bindToController();

        $this->postRequest(['column' => 'city', 'rowData' => []]);
        $this->assertSame(['options' => []], $form->getFormWidget('contacts')->onGetDropdownOptions());
    }

    public function test_saved_dropdown_values_render_with_their_options_resolved_once_per_dependency_value(): void
    {
        $user = new class extends User {
            protected $table = 'users';

            public array $lookups = [];

            public function getDataTableOptions(string $fieldName, ?string $column, array $rowData = []): array
            {
                $this->lookups[] = [$column, $rowData];

                return parent::getDataTableOptions($fieldName, $column, $rowData);
            }
        };

        $user->contacts = [
            ['name' => 'Ada', 'country' => 'us', 'city' => 'new-york'],
            ['name' => 'Bob', 'country' => 'au', 'city' => 'sydney'],
            ['name' => 'Cy', 'country' => 'us', 'city' => 'chicago'],
            ['name' => 'Di', 'country' => 'nz'],
            ['name' => 'Ed'],
        ];

        $html = (string) $this->makeTableForm($user)->render();

        $this->assertSame(['city' => [
            ['values' => ['country' => 'us'], 'options' => ['new-york' => 'New York', 'chicago' => 'Chicago', 'seattle' => 'Seattle']],
            ['values' => ['country' => 'au'], 'options' => ['sydney' => 'Sydney', 'melbourne' => 'Melbourne', 'brisbane' => 'Brisbane']],
            ['values' => ['country' => 'nz'], 'options' => ['auckland' => 'Auckland', 'wellington' => 'Wellington']],
        ]], $this->attributeJson($html, 'data-preloaded-options'));

        $this->assertSame([
            ['city', ['name' => 'Ada', 'country' => 'us', 'city' => 'new-york']],
            ['city', ['name' => 'Bob', 'country' => 'au', 'city' => 'sydney']],
            ['city', ['name' => 'Di', 'country' => 'nz', 'city' => null]],
        ], $user->lookups);
    }

    public function test_a_column_without_dependencies_preloads_its_options_once_and_empty_tables_preload_nothing(): void
    {
        $user = new class extends User {
            protected $table = 'users';

            public int $lookups = 0;

            public function getContactsDataTableOptions($column, $rowData): array
            {
                $this->lookups++;

                return ['web' => 'Website', 'event' => 'Event'];
            }
        };

        $columns = ['columns' => ['name' => ['title' => 'Name'], 'source' => ['title' => 'Source', 'type' => 'autocomplete']]];

        $html = (string) $this->makeTableForm($user, $columns)->render();
        $this->assertStringContainsString("data-preloaded-options='{}'", $html);
        $this->assertSame(0, $user->lookups);

        $user->contacts = [['name' => 'Ada', 'source' => 'web'], ['name' => 'Bob', 'source' => 'event'], ['name' => 'Cy']];

        $html = (string) $this->makeTableForm($user, $columns)->render();
        $this->assertSame(['source' => [['values' => [], 'options' => ['web' => 'Website', 'event' => 'Event']]]], $this->attributeJson($html, 'data-preloaded-options'));
        $this->assertSame(1, $user->lookups);
    }

    public function test_preview_read_only_and_disabled_render_a_read_only_grid_without_row_buttons(): void
    {
        $user = User::factory()->create();
        $config = ['searching' => true, 'csvExport' => true, 'csvImport' => true, 'requireValid' => true];

        $editable = (string) $this->makeTableForm($user, $config)->render();
        $this->assertStringContainsString('data-read-only="false"', $editable);
        $this->assertStringContainsString('data-table-add', $editable);
        $this->assertStringContainsString('data-table-import-file', $editable);
        $this->assertStringContainsString('data-require-valid="true"', $editable);
        $this->assertMatchesRegularExpression('/name="contacts" value="\[\]"\s+\/>/', $editable);

        $modes = [
            'preview' => [(string) $this->makeTableForm($user, $config)->render(['preview' => true]), true],
            'readOnly' => [(string) $this->makeTableForm($user, $config + ['readOnly' => true])->render(), false],
            'disabled' => [(string) $this->makeTableForm($user, $config + ['disabled' => true])->render(), true],
        ];

        foreach ($modes as $mode => [$html, $inputDisabled]) {
            $this->assertStringContainsString('data-read-only="true"', $html, $mode);
            $this->assertStringContainsString('data-adding="false"', $html, $mode);
            $this->assertStringContainsString('data-deleting="false"', $html, $mode);
            $this->assertStringContainsString('data-require-valid="false"', $html, $mode);
            $this->assertStringNotContainsString('data-table-add', $html, $mode);
            $this->assertStringNotContainsString('data-table-delete', $html, $mode);
            $this->assertStringNotContainsString('data-table-import', $html, $mode);
            $this->assertStringContainsString('data-table-export', $html, $mode);
            $this->assertStringContainsString('data-table-search', $html, $mode);
            $this->assertSame($inputDisabled, (bool) preg_match('/name="contacts" value="\[\]" disabled \/>/', $html), $mode);
        }

        $bare = (string) $this->makeTableForm($user, ['readOnly' => true])->render();
        $this->assertStringNotContainsString('data-table-toolbar', $bare);
    }

    public function test_the_validation_messages_pass_through_the_translator(): void
    {
        $html = (string) $this->makeTableForm(User::factory()->create())->render();
        $lang = $this->attributeJson($html, 'data-lang');

        $this->assertSame('The value should not be empty.', $lang['ruleRequired']);
        $this->assertSame('The value should not be more than :max', $lang['ruleMax']);
        $this->assertSame('Invalid value format.', $lang['ruleRegex']);
    }

    public function test_csv_export_downloads_the_posted_rows_under_the_column_titles(): void
    {
        $response = $this->ajax(User::factory()->create(), 'formContacts::onExportCsv', [
            'tableData' => json_encode([
                ['name' => 'Ada', 'email' => 'ada@example.com', 'type' => 'work', 'country' => 'au', 'city' => 'sydney', 'primary' => 1, 'since' => '2026-01-15'],
                ['name' => '=HYPERLINK("x")', 'email' => '-1', 'primary' => 0],
            ]),
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="contacts.csv"');

        $lines = explode("\n", trim($response->getContent()));
        $this->assertSame("\xEF\xBB\xBFName,Email,Type,Country,City,Primary,Since", $lines[0]);
        $this->assertSame('Ada,ada@example.com,work,au,sydney,1,2026-01-15', $lines[1]);
        $this->assertSame('"\'=HYPERLINK(""x"")",\'-1,,,,0,', $lines[2]);
    }

    public function test_csv_import_matches_header_titles_and_names_and_skips_empty_rows(): void
    {
        $csv = implode("\n", [
            'NAME;email;Primary;Unknown;since',
            'Ada;ada@example.com;1;ignored;2026-01-15',
            ';;;;',
            'Bob;bob@example.com;0;;',
        ]);

        $response = $this->ajax(User::factory()->create(), 'formContacts::onImportCsv', ['csvData' => $csv]);

        $response->assertOk();
        $this->assertSame([
            ['name' => 'Ada', 'email' => 'ada@example.com', 'primary' => '1', 'since' => '2026-01-15'],
            ['name' => 'Bob', 'email' => 'bob@example.com', 'primary' => '0', 'since' => ''],
        ], $response->json('rows'));
    }

    public function test_csv_import_without_a_header_uses_the_column_order(): void
    {
        $widget = $this->makeTableForm(User::factory()->create())->getFormWidget('contacts');

        $this->postRequest(['csvData' => "Ada\tau\tsydney\nBob\tnz"]);

        $this->assertSame(['rows' => [
            ['name' => 'Ada', 'country' => 'au', 'city' => 'sydney'],
            ['name' => 'Bob', 'country' => 'nz', 'city' => null],
        ]], $widget->onImportCsv());
    }
}
