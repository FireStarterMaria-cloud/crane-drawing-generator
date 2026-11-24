<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class MetaController extends Controller
{
    // GET /api/meta/limits: the UI builds its form from this
    public function __invoke()
    {
        return response()->json([
            'limits' => config('crane.limits'),
            'duty_groups' => config('crane.duty_groups'),
            'rules' => config('crane.rules'),
            'rail' => config('crane.rail'),
            'calc_version' => config('crane.calc_version'),
        ]);
    }
}