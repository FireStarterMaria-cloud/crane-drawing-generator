<?php

namespace App\Services;

// Builds the drawing as an SVG string.
// It is schematic, not a manufacturing drawing. Dimensions come from the inputs
// and CraneCalculator. Trolley, hook, girder width and beams are only symbols.
class CraneSvgRenderer
{
    private array $parts = [];

    public function render(array $in, array $calc, string $title = 'Untitled design'): string
    {
        $this->parts = [];
        $d = $calc['derived'];

        $span = (int) $in['span_mm'];
        $runway = (int) $in['runway_height_mm'];
        $lift = (int) $in['lifting_height_mm'];
        $ec = (int) $in['end_clearance_mm'];
        $wheelD = (int) $in['wheel_diameter_mm'];
        $total = $d['overall_length_mm']['value'];
        $wheelbase = $d['wheelbase_mm']['value'];
        $endLen = $d['end_carriage_length_mm']['value'];
        $depth = $d['girder_depth_mm']['value'];

        // one scale for the whole drawing: mm -> px
        $s = min(800 / $total, 260 / ($runway + $depth + $wheelD));

        // x positions (px)
        $x0 = 150.0;                       // left building wall
        $xC = $x0 + $ec * $s;              // crane left end
        $xEnd = $xC + $total * $s;         // crane right end
        $xWallR = $xEnd + $ec * $s;        // right building wall
        $ext = ($total - $span) / 2;       // overhang beyond rail centre
        $railL = $xC + $ext * $s;
        $railR = $railL + $span * $s;
        $xm = ($railL + $railR) / 2;

        // y positions (px)
        $floorY = 110 + ($runway + $depth + $wheelD) * $s;
        $railY = $floorY - $runway * $s;   // top of rail
        $r = $wheelD / 2 * $s;             // wheel radius
        $gBot = $railY - 2 * $r;           // girder sits on top of the wheels
        $gTop = $gBot - $depth * $s;
        $hookY = $floorY - $lift * $s;     // hook at its highest position

        // ---------- FRONT ELEVATION ----------
        $this->text($x0, $floorY + 38, 'FRONT ELEVATION', 13, 'start', 'fill="#111" font-weight="bold"');

        $this->line($x0 - 20, $floorY, $xWallR + 20, $floorY);
        $this->text($x0 + 4, $floorY + 14, 'Floor +/-0', 10, 'start');
        $this->line($x0, $floorY, $x0, $gTop - 15, 'stroke="#777" stroke-width="2"');
        $this->line($xWallR, $floorY, $xWallR, $gTop - 15, 'stroke="#777" stroke-width="2"');

        // runway beams (symbol)
        $bw = max(16, $wheelD * $s * 2.5);
        foreach ([$railL, $railR] as $rx) {
            $this->rect($rx - $bw / 2, $railY, $bw, 8, 'fill="#ccc" stroke="#222"');
        }

        // girder, wheels
        $this->rect($xC, $gTop, $total * $s, $depth * $s, 'fill="#fff6d6" stroke="#222" stroke-width="1.5"');
        foreach ([$railL, $railR] as $rx) {
            $this->add(sprintf('<circle cx="%s" cy="%s" r="%s" fill="#fff" stroke="#222" stroke-width="1.5"/>',
                $this->n($rx), $this->n($railY - $r), $this->n($r)));
        }

        // trolley, rope and hook (symbols)
        $tw = max(28, 0.08 * $span * $s);
        $th = max(10, 350 * $s);
        $this->rect($xm - $tw / 2, $gBot, $tw, $th, 'fill="#ddd" stroke="#222"');
        $this->line($xm, $gBot + $th, $xm, $hookY, 'stroke="#222" stroke-width="1"');
        $this->add(sprintf('<circle cx="%s" cy="%s" r="%s" fill="#fff" stroke="#222" stroke-width="1.5"/>',
            $this->n($xm), $this->n($hookY), $this->n(max(4, 150 * $s))));

        // front dimensions
        $yo = $gTop - 60;
        $this->ext($xC, $gTop, $xC, $yo - 6);
        $this->ext($xEnd, $gTop, $xEnd, $yo - 6);
        $this->hdim($xC, $xEnd, $yo, 'Overall length ' . $total . $this->est($d['overall_length_mm']));

        $ys = $gTop - 30;
        $this->ext($railL, $railY, $railL, $ys - 6);
        $this->ext($railR, $railY, $railR, $ys - 6);
        $this->hdim($railL, $railR, $ys, 'Span ' . $span);

        $ym = ($gTop + $gBot) / 2;
        $this->hdim($x0, $xC, $ym, 'min ' . $ec, 'end', $x0 - 4);
        $this->hdim($xEnd, $xWallR, $ym, 'min ' . $ec, 'start', $xWallR + 4);

        $this->ext(56, $railY, $x0, $railY);
        $this->vdim($railY, $floorY, 60, 'Runway height ' . $runway);
        $this->ext(96, $hookY, $xm - 6, $hookY);
        $this->vdim($hookY, $floorY, 100, 'Lifting height ' . $lift);

        // ---------- PLAN VIEW ----------
        $planTop = $floorY + 100;
        $endH = $endLen * $s;
        $planBottom = $planTop + $endH;
        $ew = max(14, 2 * $ext * $s);      // end carriage width (px)
        $yMid = $planTop + $endH / 2;
        $gw = max(8, 400 * $s);            // girder width, symbol only

        $this->text($x0, $planTop - 30, 'PLAN VIEW', 13, 'start', 'fill="#111" font-weight="bold"');
        $this->line($x0, $planTop - 20, $x0, $planBottom + 10, 'stroke="#777" stroke-width="2"');
        $this->line($xWallR, $planTop - 20, $xWallR, $planBottom + 10, 'stroke="#777" stroke-width="2"');

        // rail centre lines
        foreach ([$railL, $railR] as $rx) {
            $this->ext($rx, $planTop - 20, $rx, $planBottom + 74);
        }

        // end carriages, girder, trolley
        $this->rect($xC, $planTop, $ew, $endH, 'fill="#eee" stroke="#222" stroke-width="1.5"');
        $this->rect($xEnd - $ew, $planTop, $ew, $endH, 'fill="#eee" stroke="#222" stroke-width="1.5"');
        $this->rect($xC + $ew, $yMid - $gw / 2, $total * $s - 2 * $ew, $gw, 'fill="#fff6d6" stroke="#222" stroke-width="1.5"');
        $trH = max(14, $endH * 0.5);
        $this->rect($xm - $tw / 2, $yMid - $trH / 2, $tw, $trH, 'fill="#ddd" stroke="#222"');

        // wheels (2 per end carriage)
        $wh = max(6, $wheelD * $s);
        $wy1 = $planTop + ($endLen - $wheelbase) / 2 * $s;
        $wy2 = $wy1 + $wheelbase * $s;
        foreach ([$xC, $xEnd - $ew] as $wx) {
            foreach ([$wy1, $wy2] as $wy) {
                $this->rect($wx, $wy - $wh / 2, $ew, $wh, 'fill="#333"');
            }
        }
        $wl = $d['max_wheel_load_kn'];
        $this->text($xC + $ew + 8, $wy1 - 6, 'Max static wheel load ' . $wl['value'] . ' kN' . $this->est($wl), 10, 'start');

        // plan dimensions
        $this->ext($x0 - 34, $wy1, $xC, $wy1);
        $this->ext($x0 - 34, $wy2, $xC, $wy2);
        $this->vdim($wy1, $wy2, $x0 - 30, 'Wheelbase ' . $wheelbase . $this->est($d['wheelbase_mm']));
        $this->ext($x0 - 74, $planTop, $xC, $planTop);
        $this->ext($x0 - 74, $planBottom, $xC, $planBottom);
        $this->vdim($planTop, $planBottom, $x0 - 70, 'End carriage ' . $endLen . $this->est($d['end_carriage_length_mm']));

        $this->ext($xC, $planBottom, $xC, $planBottom + 44);
        $this->ext($xEnd, $planBottom, $xEnd, $planBottom + 44);
        $this->hdim($xC, $xEnd, $planBottom + 40, 'Overall length ' . $total . $this->est($d['overall_length_mm']));
        $this->hdim($railL, $railR, $planBottom + 70, 'Span ' . $span);

        // ---------- TECHNICAL DATA PANEL ----------
        $rows = [
            ['Load', $this->n((float) $in['capacity_t']) . ' t'],
            ['Span', $span . ' mm'],
            ['Lifting height', $lift . ' mm'],
            ['Runway height (floor to rail top)', $runway . ' mm'],
            ['End clearance (min)', $ec . ' mm'],
            ['Crane group (FEM)', $in['duty_group']],
            ['Wheel diameter', $wheelD . ' mm'],
            ['Rail (drawing Detail Z)', config('crane.rail.width_mm') . 'x' . config('crane.rail.height_mm')],
        ];
        foreach ([['hoist_speed_mpm', 'Hoisting speed'], ['trolley_speed_mpm', 'Trolley speed'], ['travel_speed_mpm', 'Travel speed']] as [$k, $label]) {
            if (!empty($in[$k])) {
                $rows[] = [$label, $in[$k] . ' m/min'];
            }
        }
        foreach (['overall_length_mm', 'wheelbase_mm', 'end_carriage_length_mm', 'girder_depth_mm', 'hook_approach_mm',
                  'trolley_weight_kg', 'crane_weight_kg', 'max_wheel_load_kn'] as $k) {
            $i = $d[$k];
            $rows[] = [$i['label'] . $this->est($i), $this->n((float) $i['value']) . ' ' . $i['unit']];
        }

        $notes = [
            '(Estimate) = not given in the reference drawing.',
            'Sources are in config/crane.php.',
            'Schematic only: trolley, hook, girder width',
            'and runway beams are symbols, not to scale.',
        ];

        $px = 1090;
        $pw = 295;
        $y = 40;
        $this->text($px + 8, $y + 22, 'TECHNICAL DATA', 13, 'start', 'fill="#111" font-weight="bold"');
        $this->text($px + 8, $y + 38, $title, 11, 'start', 'fill="#444"');
        $y += 38;
        foreach ($rows as [$label, $value]) {
            $y += 17;
            $this->text($px + 8, $y, $label, 11, 'start');
            $this->text($px + $pw - 8, $y, (string) $value, 11, 'end', 'fill="#111" font-weight="bold"');
        }
        $y += 12;
        foreach ($notes as $note) {
            $y += 14;
            $this->text($px + 8, $y, $note, 9.5, 'start', 'fill="#555"');
        }
        foreach ($calc['warnings'] as $w) {
            foreach (str_split($w, 48) as $chunk) {
                $y += 14;
                $this->text($px + 8, $y, $chunk, 9.5, 'start', 'fill="#b00020"');
            }
        }
        $y += 22;
        $this->text($px + 8, $y, 'PRELIMINARY DRAWING, FOR OFFER ONLY', 10, 'start', 'fill="#111" font-weight="bold"');
        $panelBottom = $y + 10;
        $this->add(sprintf('<rect x="%s" y="40" width="%s" height="%s" fill="none" stroke="#222"/>',
            $px, $pw, $this->n($panelBottom - 40)));

        // ---------- WRAP ----------
        $h = (int) ceil(max($planBottom + 110, $panelBottom + 20));
        $head = sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1400 %d" width="1400" height="%d" font-family="Arial, sans-serif">', $h, $h)
            . '<defs><marker id="a" viewBox="0 0 10 10" refX="10" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">'
            . '<path d="M0,0 L10,5 L0,10 z" fill="#0a6"/></marker></defs>'
            . '<rect width="1400" height="' . $h . '" fill="#fff"/>'
            . '<rect x="6" y="6" width="1388" height="' . ($h - 12) . '" fill="none" stroke="#222"/>';

        return $head . implode('', $this->parts) . '</svg>';
    }

    // ---------- small helpers ----------

    private function add(string $s): void
    {
        $this->parts[] = $s;
    }

    // short number: 10.0 -> "10", 72.6 -> "72.6"
    private function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    // all text goes through here so names like "<b>" can not break the SVG
    private function e($s): string
    {
        return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function est(array $item): string
    {
        return $item['estimate'] ? ' (Estimate)' : '';
    }

    private function line(float $x1, float $y1, float $x2, float $y2, string $attr = 'stroke="#222" stroke-width="1.2"'): void
    {
        $this->add(sprintf('<line x1="%s" y1="%s" x2="%s" y2="%s" %s/>',
            $this->n($x1), $this->n($y1), $this->n($x2), $this->n($y2), $attr));
    }

    private function rect(float $x, float $y, float $w, float $h, string $attr): void
    {
        $this->add(sprintf('<rect x="%s" y="%s" width="%s" height="%s" %s/>',
            $this->n($x), $this->n($y), $this->n($w), $this->n($h), $attr));
    }

    private function text(float $x, float $y, string $t, float $size = 11, string $anchor = 'middle', string $attr = 'fill="#111"'): void
    {
        $this->add(sprintf('<text x="%s" y="%s" font-size="%s" text-anchor="%s" %s>%s</text>',
            $this->n($x), $this->n($y), $this->n($size), $anchor, $attr, $this->e($t)));
    }

    // thin dashed extension line
    private function ext(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->line($x1, $y1, $x2, $y2, 'stroke="#888" stroke-width="0.6" stroke-dasharray="4 3"');
    }

    private function hdim(float $x1, float $x2, float $y, string $label, string $anchor = 'middle', ?float $tx = null): void
    {
        $this->line($x1, $y, $x2, $y, 'stroke="#0a6" stroke-width="1" marker-start="url(#a)" marker-end="url(#a)"');
        $this->text($tx ?? ($x1 + $x2) / 2, $y - 5, $label, 11, $anchor, 'fill="#064"');
    }

    private function vdim(float $y1, float $y2, float $x, string $label): void
    {
        $this->line($x, $y1, $x, $y2, 'stroke="#0a6" stroke-width="1" marker-start="url(#a)" marker-end="url(#a)"');
        $tx = $x - 6;
        $ty = ($y1 + $y2) / 2;
        $this->add(sprintf('<text x="%s" y="%s" font-size="11" text-anchor="middle" fill="#064" transform="rotate(-90 %s %s)">%s</text>',
            $this->n($tx), $this->n($ty), $this->n($tx), $this->n($ty), $this->e($label)));
    }
}