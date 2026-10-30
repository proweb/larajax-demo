<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\Facades\Ui;
use Tests\TestCase;

class DropdownMenuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * renderDropdownButton captures a slot-based dropdown button holding one item and a divider.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function renderDropdownButton(array $arguments): string
    {
        ob_start();
        Ui::dropdownButton(...$arguments)->slot();
        echo Ui::dropdownItem(label: 'Edit', handler: 'onEdit');
        echo Ui::dropdownDivider();
        Ui::end();

        return ob_get_clean();
    }

    public function test_the_dropdown_button_renders_a_bootstrap_menu(): void
    {
        $html = $this->renderDropdownButton(['label' => 'Actions']);

        $this->assertStringContainsString('<div class="btn-group">', $html);
        $this->assertStringContainsString('data-bs-toggle="menu"', $html);
        $this->assertStringContainsString('data-bs-strategy="fixed"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('<div class="menu control-dropdown" role="menu">', $html);
        $this->assertStringContainsString('class="menu-item" role="menuitem"', $html);
        $this->assertStringContainsString('data-request="onEdit"', $html);
        $this->assertStringContainsString('<hr class="menu-divider">', $html);
        $this->assertStringContainsString(Ui::iconClass('dropdown.caret'), $html);
        $this->assertStringNotContainsString('dropdown-', $html);
        $this->assertStringNotContainsString('<li', $html);
    }

    public function test_the_dropdown_button_caret_can_be_turned_off(): void
    {
        $html = $this->renderDropdownButton(['label' => 'Actions', 'caret' => false]);

        $this->assertStringContainsString('data-bs-toggle="menu"', $html);
        $this->assertStringNotContainsString(Ui::iconClass('dropdown.caret'), $html);
    }

    public function test_the_dropdown_item_merges_extra_classes(): void
    {
        $html = (string) Ui::dropdownItem(label: 'Delete', class: 'text-danger');

        $this->assertStringContainsString('class="menu-item text-danger"', $html);
        $this->assertStringContainsString('href="javascript:;"', $html);
        $this->assertStringNotContainsString('data-request', $html);
    }

    public function test_the_filter_setup_menu_uses_bootstrap_menu_markup(): void
    {
        $html = $this->get('users')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="filter-group filter-setup">\s*<a href="javascript:;"\s+data-bs-toggle="menu"/', $html);
        $this->assertMatchesRegularExpression('/<div class="menu control-dropdown" role="menu">\s*<a\s+data-filter-clear\s+class="menu-item"/', $html);
        $this->assertStringNotContainsString('data-bs-toggle="dropdown"', $html);
        $this->assertStringNotContainsString('dropdown-menu', $html);
    }

    public function test_the_repeater_item_menu_uses_bootstrap_menu_markup(): void
    {
        $user = User::factory()->create([
            'links' => [['title' => 'Blog', 'url' => 'https://example.com/blog']],
        ]);

        $html = $this->get("/users/{$user->id}/edit")->assertOk()->getContent();

        $this->assertStringContainsString('class="repeater-item-menu" data-bs-toggle="menu" data-bs-placement="bottom-end"', $html);
        $this->assertStringContainsString('<div class="menu control-dropdown" role="menu"></div>', $html);
        $this->assertMatchesRegularExpression('/<a\s+data-repeater-remove\s+class="menu-item"/', $html);
        $this->assertStringContainsString('<hr class="menu-divider">', $html);
        $this->assertStringNotContainsString('dropdown-menu', $html);
        $this->assertStringNotContainsString('data-bs-toggle="dropdown"', $html);
    }
}
