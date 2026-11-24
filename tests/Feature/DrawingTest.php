<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawingTest extends TestCase
{
    use RefreshDatabase;

    private function valid(array $override = []): array
    {
        return array_merge([
            'name' => 'Test crane 10t',
            'capacity_t' => 10, 'span_mm' => 25000, 'lifting_height_mm' => 7900,
            'runway_height_mm' => 8321, 'end_clearance_mm' => 235,
            'wheel_diameter_mm' => 250, 'duty_group' => 'A5',
        ], $override);
    }

    public function test_drawing_is_valid_svg_with_dimensions_and_estimate_labels(): void
    {
        $id = $this->postJson('/api/designs', $this->valid())->json('data.id');

        $res = $this->get("/api/designs/{$id}/drawing.svg")->assertOk();
        $svg = $res->getContent();

        $this->assertStringContainsString('image/svg+xml', $res->headers->get('Content-Type'));
        $this->assertNotFalse(simplexml_load_string($svg), 'SVG must be well-formed XML');
        $this->assertStringContainsString('25270', $svg);
        $this->assertStringContainsString('Wheelbase 3800', $svg);
        $this->assertStringContainsString('(Estimate)', $svg);
    }

    public function test_download_flag_sets_attachment(): void
    {
        $id = $this->postJson('/api/designs', $this->valid())->json('data.id');

        $this->get("/api/designs/{$id}/drawing.svg?download=1")
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="crane-design-' . $id . '.svg"');
    }

    public function test_design_name_is_escaped_in_svg(): void
    {
        $id = $this->postJson('/api/designs', $this->valid(['name' => '<b>x</b>']))->json('data.id');

        $svg = $this->get("/api/designs/{$id}/drawing.svg")->getContent();

        $this->assertStringNotContainsString('<b>x</b>', $svg);
        $this->assertStringContainsString('&lt;b&gt;', $svg);
    }

    public function test_preview_returns_svg(): void
    {
        $this->postJson('/api/designs/preview', $this->valid())
            ->assertOk()
            ->assertJsonStructure(['data' => ['svg', 'derived', 'warnings']]);
    }

    public function test_unknown_design_returns_json_404(): void
    {
        $this->getJson('/api/designs/999/drawing.svg')->assertNotFound();
    }
}