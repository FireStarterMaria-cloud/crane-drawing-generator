<?php

namespace Tests\Unit;

use App\Services\CraneCalculator;
use Tests\TestCase;

class CraneCalculatorTest extends TestCase
{
    // inputs that match the reference drawing
    private function referenceInputs(): array
    {
        return [
            'capacity_t' => 10, 'span_mm' => 25000, 'lifting_height_mm' => 7900,
            'runway_height_mm' => 8321, 'end_clearance_mm' => 235, 'wheel_diameter_mm' => 250,
        ];
    }

    public function test_reference_drawing_dimensions_are_reproduced(): void
    {
        $r = (new CraneCalculator())->calculate($this->referenceInputs())['derived'];

        $this->assertSame(25270, $r['overall_length_mm']['value']);
        $this->assertSame(3800, $r['wheelbase_mm']['value']);
        $this->assertSame(4310, $r['end_carriage_length_mm']['value']);
        $this->assertSame(421, $r['hook_approach_mm']['value']);
        $this->assertSame(8910, $r['crane_weight_kg']['value']);
    }

    public function test_wheel_load_is_close_to_reference_drawing(): void
    {
        $r = (new CraneCalculator())->calculate($this->referenceInputs())['derived'];
        $value = $r['max_wheel_load_kn']['value'];

        $this->assertEqualsWithDelta(72.6, $value, 0.1);
        // drawing says 74.8 kN, allow 5% difference
        $this->assertEqualsWithDelta(74.8, $value, 74.8 * 0.05);
        $this->assertTrue($r['max_wheel_load_kn']['estimate']);
    }

    public function test_user_wheelbase_is_not_marked_estimate(): void
    {
        $in = $this->referenceInputs() + ['wheelbase_mm' => 4000];
        $r = (new CraneCalculator())->calculate($in)['derived'];

        $this->assertSame(4000, $r['wheelbase_mm']['value']);
        $this->assertFalse($r['wheelbase_mm']['estimate']);
    }

    public function test_double_girder_warning(): void
    {
        $in = ['capacity_t' => 18, 'span_mm' => 25000] + $this->referenceInputs();
        $out = (new CraneCalculator())->calculate($in);

        $this->assertNotEmpty($out['warnings']);
    }
}