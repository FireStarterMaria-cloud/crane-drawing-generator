<?php

namespace App\Services;

// Takes validated inputs, returns derived values and warnings.
// Each value says where it came from and if it is an estimate.
class CraneCalculator
{
    public function calculate(array $in): array
    {
        $rules = config('crane.rules');
        $ref = config('crane.weights');
        $g = $rules['gravity'];

        $span = (int) $in['span_mm'];
        $capT = (float) $in['capacity_t'];
        $capKg = $capT * 1000;

        // wheelbase: use user value, else auto from span
        $wheelbaseAuto = empty($in['wheelbase_mm']);
        $wheelbase = $wheelbaseAuto
            ? (int) (round($span * $rules['wheelbase_ratio_default'] / 50) * 50)
            : (int) $in['wheelbase_mm'];

        // weights: use user value, else scale from the reference crane
        $trolleyAuto = empty($in['trolley_weight_kg']);
        $trolleyKg = $trolleyAuto
            ? (int) round($ref['ref_trolley_kg'] * ($capT / $ref['ref_capacity_t']))
            : (int) $in['trolley_weight_kg'];

        $craneAuto = empty($in['crane_weight_kg']);
        $craneKg = $craneAuto
            ? (int) round($ref['ref_crane_kg'] * ($span / $ref['ref_span_mm']) * (0.5 + 0.5 * $capT / $ref['ref_capacity_t']))
            : (int) $in['crane_weight_kg'];

        // worst case: trolley at one end, 2 wheels share that end. No dynamic factors.
        $bridgeKg = max(0, $craneKg - $trolleyKg);
        $wheelLoadKn = (($bridgeKg / 2 + $capKg + $trolleyKg) * $g / 2) / 1000;

        $item = fn ($label, $value, $unit, $estimate, $source) => [
            'label' => $label, 'value' => $value, 'unit' => $unit,
            'estimate' => $estimate, 'source' => $source,
        ];

        $derived = [
            'overall_length_mm' => $item('Overall length', $span + $rules['overall_length_extra_mm'], 'mm', true,
                'Span + 270. The 270 is from the drawing (25270 - 25000), I assume it stays the same'),
            'wheelbase_mm' => $item('Wheelbase', $wheelbase, 'mm', $wheelbaseAuto,
                $wheelbaseAuto ? 'Auto: span x 0.152 (from drawing 3800 / 25000)' : 'User input'),
            'end_carriage_length_mm' => $item('End carriage length', $wheelbase + $rules['end_carriage_extra_mm'], 'mm', true,
                'Wheelbase + 510. The 510 is from the drawing (4310 - 3800), I assume it stays the same'),
            'girder_depth_mm' => $item('Girder depth (drawn)', (int) round($span / $rules['girder_depth_divisor']), 'mm', true,
                'Span / 20 rule of thumb. Not in the reference drawing'),
            'hook_approach_mm' => $item('Runway height - lifting height', (int) $in['runway_height_mm'] - (int) $in['lifting_height_mm'], 'mm', false,
                'Simple subtraction (drawing: 8321 - 7900 = 421). Real meaning not confirmed'),
            'trolley_weight_kg' => $item('Trolley weight', $trolleyKg, 'kg', $trolleyAuto,
                $trolleyAuto ? 'Scaled from drawing trolley (680 kg at 10 t). Weak, one data point' : 'User input'),
            'crane_weight_kg' => $item('Crane weight', $craneKg, 'kg', $craneAuto,
                $craneAuto ? 'Scaled from drawing crane (8910 kg, 25 m, 10 t). Weak, one data point' : 'User input'),
            'max_wheel_load_kn' => $item('Max static wheel load', round($wheelLoadKn, 1), 'kN', true,
                'Trolley at the end, 2 wheels per end, no dynamic factors. Drawing shows 74.8 kN'),
        ];

        $warnings = [];
        if ($capT > $rules['double_girder_warn_t'] && $span > $rules['double_girder_warn_span_mm']) {
            $warnings[] = 'Heavy load on a long span for a single girder crane. Maybe double girder (Estimate limit).';
        }

        return [
            'derived' => $derived,
            'warnings' => $warnings,
            'calc_version' => config('crane.calc_version'),
        ];
    }
}