<?php

// All limits live here. estimate = true means show "(Estimate)" in the UI.
return [
    'calc_version' => 1,

    'limits' => [
        'capacity_t'        => ['min' => 1,    'max' => 20,    'default' => 10,    'estimate_range' => true,  'source' => 'Default from reference drawing (10 t). Range is my assumption'],
        'span_mm'           => ['min' => 5000, 'max' => 30000, 'default' => 25000, 'estimate_range' => true,  'source' => 'Default from reference drawing (25000). Range is my assumption'],
        'lifting_height_mm' => ['min' => 2000, 'max' => 15000, 'default' => 7900,  'estimate_range' => true,  'source' => 'Default from reference drawing (7900). Range is my assumption'],
        'runway_height_mm'  => ['min' => 3000, 'max' => 20000, 'default' => 8321,  'estimate_range' => false, 'source' => 'Reference drawing (8321, floor to rail top)'],
        'end_clearance_mm'  => ['min' => 235,  'max' => 1000,  'default' => 235,   'estimate_range' => false, 'source' => 'Reference drawing (Min.235)'],
        'wheel_diameter_mm' => ['allowed' => [160, 200, 250, 315, 400], 'default' => 250, 'estimate_range' => true, 'source' => '250 from drawing Detail Z. Other sizes are my assumption'],
    ],

    'duty_groups' => ['A3', 'A4', 'A5', 'A6', 'A7'], // A5 is from the drawing

    'rules' => [
        'min_hook_approach_mm'       => 400,   // Estimate. Drawing gives 8321 - 7900 = 421
        'wheelbase_ratio_default'    => 0.152, // Estimate. 3800 / 25000 from drawing
        'wheelbase_ratio_min'        => 0.10,  // Estimate
        'wheelbase_ratio_max'        => 0.25,  // Estimate
        'girder_depth_divisor'       => 20,    // Estimate. Span / 20
        'overall_length_extra_mm'    => 270,   // Drawing: 25270 - 25000
        'end_carriage_extra_mm'      => 510,   // Drawing: 4310 - 3800
        'double_girder_warn_t'       => 16,    // Estimate
        'double_girder_warn_span_mm' => 20000, // Estimate
        'gravity'                    => 9.81,
    ],

    'rail' => ['width_mm' => 50, 'height_mm' => 30], // Drawing Detail Z

    // Only one data point, so weights are weak estimates. User can override.
    'weights' => [
        'ref_crane_kg'      => 8910,
        'ref_trolley_kg'    => 680,
        'ref_span_mm'       => 25000,
        'ref_capacity_t'    => 10,
        'ref_wheel_load_kn' => 74.8, // Drawing value, for checking my formula
    ],
];