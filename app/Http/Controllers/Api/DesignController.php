<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DesignRequest;
use App\Models\Design;
use App\Services\CraneCalculator;
use App\Services\CraneSvgRenderer;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function __construct(
        private CraneCalculator $calculator,
        private CraneSvgRenderer $renderer,
    ) {
    }

    // GET /api/designs?search=abc
    public function index(Request $request)
    {
        return Design::query()
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderByDesc('updated_at')
            ->paginate(10);
    }

    // POST /api/designs
    public function store(DesignRequest $request)
    {
        $design = Design::create($this->data($request) + ['calc_version' => config('crane.calc_version')]);

        return response()->json(['data' => $this->payload($design->toArray())], 201);
    }

    // GET /api/designs/{id}
    public function show(Design $design)
    {
        return response()->json(['data' => $this->payload($design->toArray())]);
    }

    // PUT /api/designs/{id} (full replace)
    public function update(DesignRequest $request, Design $design)
    {
        $design->update($this->data($request) + ['calc_version' => config('crane.calc_version')]);

        return response()->json(['data' => $this->payload($design->fresh()->toArray())]);
    }

    // DELETE /api/designs/{id}
    public function destroy(Design $design)
    {
        $design->delete();

        return response()->noContent();
    }

    // POST /api/designs/preview (validates and calculates, does not save)
    public function preview(DesignRequest $request)
    {
        return response()->json(['data' => $this->payload($request->validated())]);
    }

    // GET /api/designs/{id}/drawing.svg (add ?download=1 to download the file)
    public function drawing(Request $request, Design $design)
    {
        $inputs = $design->toArray();
        $svg = $this->renderer->render($inputs, $this->calculator->calculate($inputs), $design->name);

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Content-Disposition' => $disposition . '; filename="crane-design-' . $design->id . '.svg"',
        ]);
    }

    // optional fields not sent become null, so PUT really replaces the design
    private function data(DesignRequest $request): array
    {
        $optional = ['wheelbase_mm', 'hoist_speed_mpm', 'trolley_speed_mpm',
            'travel_speed_mpm', 'trolley_weight_kg', 'crane_weight_kg'];

        return array_merge(array_fill_keys($optional, null), $request->validated());
    }

    // inputs + derived values + warnings + svg. Derived values are not stored in the DB
    private function payload(array $inputs): array
    {
        $calc = $this->calculator->calculate($inputs);

        return $inputs + [
            'derived' => $calc['derived'],
            'warnings' => $calc['warnings'],
            'svg' => $this->renderer->render($inputs, $calc, $inputs['name'] ?? 'Untitled design'),
        ];
    }
}