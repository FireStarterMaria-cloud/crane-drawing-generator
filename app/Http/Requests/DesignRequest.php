<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // ranges come from config/crane.php, so UI and validation use the same numbers
    public function rules(): array
    {
        $l = config('crane.limits');
        $between = fn ($k) => "between:{$l[$k]['min']},{$l[$k]['max']}";

        return [
            'name'              => ['required', 'string', 'min:3', 'max:100'],
            'capacity_t'        => ['required', 'numeric', $between('capacity_t')],
            'span_mm'           => ['required', 'integer', $between('span_mm')],
            'lifting_height_mm' => ['required', 'integer', $between('lifting_height_mm')],
            'runway_height_mm'  => ['required', 'integer', $between('runway_height_mm')],
            'end_clearance_mm'  => ['required', 'integer', $between('end_clearance_mm')],
            'wheel_diameter_mm' => ['required', 'integer', Rule::in($l['wheel_diameter_mm']['allowed'])],
            'wheelbase_mm'      => ['nullable', 'integer', 'min:500'],
            'duty_group'        => ['required', Rule::in(config('crane.duty_groups'))],
            'hoist_speed_mpm'   => ['nullable', 'integer', 'between:1,60'],
            'trolley_speed_mpm' => ['nullable', 'integer', 'between:1,60'],
            'travel_speed_mpm'  => ['nullable', 'integer', 'between:1,100'],
            'trolley_weight_kg' => ['nullable', 'integer', 'min:50'],
            'crane_weight_kg'   => ['nullable', 'integer', 'min:500'],
        ];
    }

    // cross-field rules, only checked when the basic rules passed
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $d = $v->getData();
            $r = config('crane.rules');

            // Estimate: hook approach (drawing: 8321 - 7900 = 421)
            if ((int) $d['runway_height_mm'] - (int) $d['lifting_height_mm'] < $r['min_hook_approach_mm']) {
                $v->errors()->add('runway_height_mm',
                    "Runway height must be at least lifting height + {$r['min_hook_approach_mm']} mm (Estimate).");
            }

            // Estimate: wheelbase between 10% and 25% of span
            if (!empty($d['wheelbase_mm'])) {
                $min = (int) round($d['span_mm'] * $r['wheelbase_ratio_min']);
                $max = (int) round($d['span_mm'] * $r['wheelbase_ratio_max']);
                if ($d['wheelbase_mm'] < $min || $d['wheelbase_mm'] > $max) {
                    $v->errors()->add('wheelbase_mm',
                        "Wheelbase should be between {$min} and {$max} mm for this span (Estimate: 10-25% of span).");
                }
            }

            // trolley can not weigh more than the whole crane
            if (!empty($d['trolley_weight_kg']) && !empty($d['crane_weight_kg'])
                && $d['trolley_weight_kg'] >= $d['crane_weight_kg']) {
                $v->errors()->add('trolley_weight_kg', 'Trolley weight must be less than crane weight.');
            }
        });
    }
}