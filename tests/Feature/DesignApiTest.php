<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignApiTest extends TestCase
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

    public function test_create_design(): void
    {
        $this->postJson('/api/designs', $this->valid())
            ->assertCreated()
            ->assertJsonPath('data.derived.wheelbase_mm.value', 3800);

        $this->assertDatabaseHas('designs', ['name' => 'Test crane 10t']);
    }

    public function test_rejects_out_of_range_capacity(): void
    {
        $this->postJson('/api/designs', $this->valid(['capacity_t' => 50]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('capacity_t');
    }

    public function test_rejects_runway_too_low_for_lifting_height(): void
    {
        $this->postJson('/api/designs', $this->valid(['runway_height_mm' => 8000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('runway_height_mm');
    }

    public function test_rejects_wheelbase_outside_span_ratio(): void
    {
        $this->postJson('/api/designs', $this->valid(['wheelbase_mm' => 1000]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('wheelbase_mm');
    }

    public function test_preview_does_not_save(): void
    {
        $this->postJson('/api/designs/preview', $this->valid())->assertOk();

        $this->assertDatabaseCount('designs', 0);
    }

    public function test_list_show_update_delete(): void
    {
        $id = $this->postJson('/api/designs', $this->valid())->json('data.id');

        $this->getJson('/api/designs')->assertOk()->assertJsonPath('total', 1);
        $this->getJson("/api/designs/{$id}")->assertOk();

        $this->putJson("/api/designs/{$id}", $this->valid(['capacity_t' => 12]))->assertOk();
        $this->assertDatabaseHas('designs', ['id' => $id, 'capacity_t' => 12]);

        $this->deleteJson("/api/designs/{$id}")->assertNoContent();
        $this->getJson("/api/designs/{$id}")->assertNotFound();
    }
}