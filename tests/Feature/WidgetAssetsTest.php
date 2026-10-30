<?php

namespace Tests\Feature;

use App\Classes\ControllerBase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Larajax\Ui\Classes\WidgetBase;
use Larajax\Ui\Facades\Ui;
use Tests\TestCase;

class WidgetAssetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function () {
            Route::match(['get', 'post'], '/widget-assets', [WidgetAssetsTestController::class, 'index']);
            Route::match(['get', 'post'], '/widget-assets/blank', [WidgetAssetsTestController::class, 'blank']);
        });
    }

    protected function ajax(string $handler, string $url): TestResponse
    {
        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post($url);
    }

    /**
     * responseAssets returns the assets an AJAX response asks the browser to load, as type => [url => attributes].
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function responseAssets(TestResponse $response): array
    {
        $assets = [];

        foreach ($response->json('__ajax.ops') as $op) {
            if ($op['op'] !== 'loadAssets') {
                continue;
            }

            foreach ($op['assets'] as $asset) {
                if (is_string($asset)) {
                    $assets[$op['type']][$asset] = [];
                }
                else {
                    $assets[$op['type']][$asset['url']] = $asset['attributes'];
                }
            }
        }

        return $assets;
    }

    public function test_the_page_prints_widget_assets_once_in_the_head(): void
    {
        $html = $this->get('/widget-assets')->assertOk()->getContent();
        $head = substr($html, 0, strpos($html, '</head>'));

        $css = '<link rel="stylesheet" href="'.asset('css/colorwheel.css').'" />';
        $js = '<script src="'.asset('js/colorwheel.js').'" type="module"></script>';

        $this->assertSame(1, substr_count($html, $css));
        $this->assertSame(1, substr_count($html, $js));
        $this->assertStringContainsString($css, $head);
        $this->assertStringContainsString($js, $head);
        $this->assertLessThan(strpos($head, $js), strpos($head, $css));

        // A widget created while the page content renders still reaches the head
        $this->assertStringContainsString('<link rel="stylesheet" href="'.asset('css/swatch.css').'" />', $head);
        $this->assertStringContainsString('data-control="colorwheel"', $html);
    }

    public function test_relative_names_resolve_against_the_asset_path(): void
    {
        $plain = ColorWheelTestWidget::createIn(new WidgetAssetsTestController);
        $packaged = PackagedColorWheelTestWidget::createIn(new WidgetAssetsTestController);

        $this->assertNull($plain->assetPath);

        $this->assertSame([
            'js' => [asset('js/colorwheel.js'), asset('js/shared.js'), 'https://cdn.example.com/wheel.js'],
            'css' => [asset('css/colorwheel.css')],
        ], $plain->getAssetPaths());

        $this->assertSame([
            'js' => [asset('vendor/acme/colorwheel/js/colorwheel.js'), asset('js/shared.js'), 'https://cdn.example.com/wheel.js'],
            'css' => [asset('vendor/acme/colorwheel/css/colorwheel.css')],
        ], $packaged->getAssetPaths());
    }

    public function test_a_guessed_public_asset_path_sits_next_to_the_widget_class(): void
    {
        $widget = ColorWheelTestWidget::createIn(new WidgetAssetsTestController);
        $widget->assetPath = $widget->guessViewPath('/assets', true);

        $this->assertSame('/tests/Feature/ColorWheelTestWidget/assets', $widget->assetPath);
        $this->assertSame('/tests/Feature/ColorWheelTestWidget/assets/js/colorwheel.js', $widget->getAssetPath('js/colorwheel.js'));
    }

    public function test_protocol_relative_urls_pass_through_unchanged(): void
    {
        $widget = ColorWheelTestWidget::createIn(new WidgetAssetsTestController);
        $widget->addJs('//cdn.example.com/relative.js');

        $this->assertContains('//cdn.example.com/relative.js', $widget->getAssetPaths()['js']);
        $this->assertStringContainsString('<script src="//cdn.example.com/relative.js"></script>', (string) Ui::renderAssets('js'));
    }

    public function test_asset_attributes_are_printed_without_the_build_reference(): void
    {
        ColorWheelTestWidget::createIn(new WidgetAssetsTestController);

        $html = (string) Ui::renderAssets();

        $this->assertStringContainsString('<script src="'.asset('js/colorwheel.js').'" type="module"></script>', $html);
        $this->assertStringContainsString('<script src="'.asset('js/shared.js').'" defer></script>', $html);
        $this->assertStringContainsString('<script src="https://cdn.example.com/wheel.js"></script>', $html);
        $this->assertStringNotContainsString('build=', $html);
        $this->assertStringNotContainsString('<script', (string) Ui::renderAssets('css'));
    }

    public function test_duplicate_assets_print_once_in_the_order_added(): void
    {
        $controller = new WidgetAssetsTestController;
        ColorWheelTestWidget::createIn($controller);
        ColorWheelTestWidget::createIn($controller, ['alias' => 'second']);
        PackagedColorWheelTestWidget::createIn($controller);

        $this->assertSame(implode(PHP_EOL, [
            '<link rel="stylesheet" href="'.asset('css/colorwheel.css').'" />',
            '<link rel="stylesheet" href="'.asset('vendor/acme/colorwheel/css/colorwheel.css').'" />',
            '<script src="'.asset('js/colorwheel.js').'" type="module"></script>',
            '<script src="'.asset('js/shared.js').'" defer></script>',
            '<script src="https://cdn.example.com/wheel.js"></script>',
            '<script src="'.asset('vendor/acme/colorwheel/js/colorwheel.js').'" type="module"></script>',
        ]).PHP_EOL, (string) Ui::renderAssets());
    }

    public function test_an_ajax_handler_returning_an_array_sends_the_widget_assets(): void
    {
        $response = $this->ajax('onLoadArray', '/widget-assets/blank')->assertOk();

        $this->assertStringContainsString('data-control="colorwheel"', $response->json('result'));

        // The global build reference is already on every page, so it stays out of AJAX responses
        $this->assertSame([
            'js' => [
                asset('js/colorwheel.js') => ['type' => 'module'],
                asset('js/shared.js') => ['defer' => true],
            ],
            'css' => [
                asset('css/colorwheel.css') => [],
                asset('css/swatch.css') => [],
            ],
        ], $this->responseAssets($response));
    }

    public function test_an_ajax_handler_returning_a_string_sends_the_widget_assets(): void
    {
        $response = $this->ajax('onLoadString', '/widget-assets/blank')->assertOk();

        $this->assertStringContainsString('data-control="colorwheel"', $response->json('result'));
        $this->assertArrayHasKey(asset('js/colorwheel.js'), $this->responseAssets($response)['js']);
        $this->assertArrayHasKey(asset('css/swatch.css'), $this->responseAssets($response)['css']);
    }

    public function test_an_ajax_handler_returning_an_ajax_response_sends_the_widget_assets(): void
    {
        $response = $this->ajax('onLoadResponse', '/widget-assets/blank')->assertOk();

        $this->assertArrayHasKey(asset('js/colorwheel.js'), $this->responseAssets($response)['js']);
    }

    public function test_an_ajax_response_without_widget_assets_has_no_load_operations(): void
    {
        $response = $this->ajax('onNothing', '/widget-assets/blank')->assertOk();

        $this->assertSame([], $this->responseAssets($response));
    }
}

/**
 * WidgetAssetsTestController serves the test widgets on a page and through AJAX handlers.
 */
class WidgetAssetsTestController extends ControllerBase
{
    /**
     * index renders two color wheels in the content section of the Amber layout.
     */
    public function index()
    {
        $first = ColorWheelTestWidget::make();
        $second = ColorWheelTestWidget::make(alias: 'second');

        return response(Blade::render(<<<'BLADE'
            @extends('layouts.app')
            @section('content')
                {{ $first }}
                {{ $second }}
            @endsection
            BLADE, ['first' => $first, 'second' => $second]));
    }

    /**
     * blank is a page without widgets.
     */
    public function blank(): string
    {
        return 'blank';
    }

    /**
     * onLoadArray creates a color wheel and returns it in an array.
     */
    public function onLoadArray(): array
    {
        return ['result' => ColorWheelTestWidget::make()->render()];
    }

    /**
     * onLoadString creates a color wheel and returns its markup.
     */
    public function onLoadString(): string
    {
        return ColorWheelTestWidget::make()->render();
    }

    /**
     * onLoadResponse creates a color wheel and returns an AJAX response.
     */
    public function onLoadResponse()
    {
        return ajax()->data(['result' => ColorWheelTestWidget::make()->render()]);
    }

    /**
     * onNothing returns without creating a widget.
     */
    public function onNothing(): array
    {
        return ['result' => 'ok'];
    }
}

/**
 * ColorWheelTestWidget is a custom widget that loads its own assets and renders a nested widget.
 */
class ColorWheelTestWidget extends WidgetBase
{
    /**
     * loadAssets adds a stylesheet, a module script, a root-relative script and a global CDN script.
     */
    protected function loadAssets()
    {
        $this->addCss('css/colorwheel.css');
        $this->addJs('js/colorwheel.js', ['type' => 'module']);
        $this->addJs('/js/shared.js', ['defer' => true]);
        $this->addJs('https://cdn.example.com/wheel.js', 'global');
    }

    /**
     * render the color wheel with a swatch widget created at render time.
     */
    public function render()
    {
        return '<div data-control="colorwheel"></div>'.$this->makeWidget(SwatchTestWidget::class)->render();
    }
}

/**
 * PackagedColorWheelTestWidget resolves its relative asset names inside a published package folder.
 */
class PackagedColorWheelTestWidget extends ColorWheelTestWidget
{
    /**
     * @var string|null assetPath for the published package assets.
     */
    public $assetPath = 'vendor/acme/colorwheel';
}

/**
 * SwatchTestWidget is a widget that is only created while its parent renders.
 */
class SwatchTestWidget extends WidgetBase
{
    /**
     * loadAssets adds the swatch stylesheet.
     */
    protected function loadAssets()
    {
        $this->addCss('css/swatch.css');
    }

    /**
     * render the swatch.
     */
    public function render()
    {
        return '<span class="swatch"></span>';
    }
}
