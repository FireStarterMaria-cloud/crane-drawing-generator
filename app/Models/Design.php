<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    protected $fillable = [
        'name', 'capacity_t', 'span_mm', 'lifting_height_mm', 'runway_height_mm',
        'end_clearance_mm', 'wheel_diameter_mm', 'wheelbase_mm', 'duty_group',
        'hoist_speed_mpm', 'trolley_speed_mpm', 'travel_speed_mpm',
        'trolley_weight_kg', 'crane_weight_kg', 'calc_version',
    ];

    protected $casts = [
        'capacity_t' => 'float',
    ];
}