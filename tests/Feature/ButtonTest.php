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
}
