<?php

namespace Tests\Feature;

use Larajax\Ui\Facades\Ui;
use Tests\TestCase;

class ButtonTest extends TestCase
{
    public function test_button_flags_render_a_solid_variant_with_a_theme(): void
    {
        $this->assertStringContainsString('class="btn-solid theme-primary"', (string) Ui::button(label: 'Save', primary: true));
        $this->assertStringContainsString('class="btn-solid theme-secondary"', (string) Ui::button(label: 'Back', secondary: true));
        $this->assertStringContainsString('class="btn-solid theme-danger"', (string) Ui::button(label: 'Delete', danger: true));
    }

    public function test_button_without_a_flag_renders_the_base_class(): void
    {
        $this->assertStringContainsString('class="btn"', (string) Ui::button(label: 'Plain'));
        $this->assertStringContainsString('class="btn"', (string) Ui::ajaxButton(label: 'Plain', handler: 'onPlain'));
    }

    public function test_ajax_popup_and_dropdown_buttons_render_the_variant_classes(): void
    {
        $this->assertStringContainsString('class="btn-solid theme-danger"', (string) Ui::ajaxButton(label: 'Delete', handler: 'onDelete', danger: true));
        $this->assertStringContainsString('class="btn-solid theme-primary"', (string) Ui::popupButton(label: 'Open', handler: 'onOpen', primary: true));
        $this->assertStringContainsString('class="btn-solid theme-secondary"', (string) Ui::dropdownButton(label: 'Actions', secondary: true));
    }

    public function test_icon_button_renders_the_icon_link_class_not_the_square_icon_button(): void
    {
        $html = (string) Ui::iconButton(label: 'Remove', icon: 'icon-trash', danger: true);

        $this->assertMatchesRegularExpression('/class="btn-icon-link [^"]*danger"/', $html);
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\bbtn-icon\b(?!-)/', $html);
    }

    public function test_fluent_button_types_render_a_variant_with_a_theme(): void
    {
        $this->assertMatchesRegularExpression('/class="btn-solid theme-primary\s*"/', Ui::button('Save')->primary()->toHtml());
        $this->assertMatchesRegularExpression('/class="btn-outline theme-danger\s*"/', Ui::button('Delete')->danger()->outline(true)->toHtml());
        $this->assertMatchesRegularExpression('/class="btn-link p-0\s*"/', Ui::button('Back')->textLink()->toHtml());
    }

    public function test_fluent_default_type_renders_the_secondary_theme(): void
    {
        $this->assertMatchesRegularExpression('/class="btn-solid theme-secondary\s*"/', Ui::button('Cancel')->toHtml());
        $this->assertMatchesRegularExpression('/class="btn-outline theme-secondary\s*"/', Ui::button('Cancel')->outline(true)->toHtml());
    }

    public function test_form_delete_button_renders_the_icon_link_class(): void
    {
        $this->assertStringContainsString('btn-icon-link danger pull-right', Ui::ajaxButton('Delete', 'onDelete')->formDeleteButton()->toHtml());
    }
}
