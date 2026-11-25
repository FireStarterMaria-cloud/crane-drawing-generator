<?php

namespace App\Services;

// Builds the crane drawing as one SVG sheet: front elevation, plan view, end view,
// detail Z, technical data and title block.
// Main dimensions come from the inputs and CraneCalculator. Front and plan view use ONE scale.
// Trolley, hook, runway beam, wall bracket and girder width are symbols (the sheet says so).
class CraneSvgRenderer
{
    private const W = 1600;
    private const H = 1130;

    // line styles
    private const OBJ = 'fill="none" stroke="#111" stroke-width="1.4" stroke-linejoin="round"';
    private const THIN = 'fill="none" stroke="#111" stroke-width="0.8"';
    private const HIDDEN = 'fill="none" stroke="#111" stroke-width="0.7" stroke-dasharray="3 2"';
    private const DIM = 'fill="none" stroke="#111" stroke-width="0.7"';
    private const EXT = 'fill="none" stroke="#111" stroke-width="0.5"';
    private const CTR = 'fill="none" stroke="#111" stroke-width="0.6" stroke-dasharray="12 3 2 3"';

    private const LIGHT = '#f3f3f3';
    private const ARROWS = ' marker-start="url(#cd-arrow)" marker-end="url(#cd-arrow)"';

    private array $out = [];

    public function render(array $in, array $calc, string $title = 'Untitled design'): string
    {
        $this->out = [];
        $d = $calc['derived'];

        $span = (int) $in['span_mm'];
        $total = (int) $d['overall_length_mm']['value'];

        $g = [
            'span' => $span,
            'total' => $total,
            'ext' => ($total - $span) / 2,               // overhang beyond the rail centre
            'ec' => (int) $in['end_clearance_mm'],
            'runway' => (int) $in['runway_height_mm'],
            'lift' => (int) $in['lifting_height_mm'],
            'wheelD' => (int) $in['wheel_diameter_mm'],
            'wheelW' => (int) config('crane.rail.wheel_width_mm', 75),
            'railW' => (int) config('crane.rail.width_mm', 50),
            'railH' => (int) config('crane.rail.height_mm', 30),
            'wheelbase' => (int) $d['wheelbase_mm']['value'],
            'endLen' => (int) $d['end_carriage_length_mm']['value'],
            'depth' => (int) $d['girder_depth_mm']['value'],
        ];
        $g['gOff'] = 1.25 * $g['wheelD'];                // girder bottom above rail top (my assumption)
        $g['approach'] = $g['runway'] - $g['lift'];      // rail top to hook at its highest position

        // one scale for front and plan view (mm -> px)
        $width = $total + 2 * $g['ec'];
        $s = min(880 / $width, 330 / ($g['runway'] + $g['gOff'] + $g['depth']));
        $g['s'] = $s;
        $g['x0'] = 190 + (880 - $width * $s) / 2;        // left wall, inner face
        $g['xC'] = $g['x0'] + $g['ec'] * $s;             // crane left end
        $g['xEnd'] = $g['xC'] + $total * $s;             // crane right end
        $g['xW'] = $g['xEnd'] + $g['ec'] * $s;           // right wall, inner face
        $g['railL'] = $g['xC'] + $g['ext'] * $s;
        $g['railR'] = $g['railL'] + $span * $s;
        $g['xm'] = ($g['railL'] + $g['railR']) / 2;

        $this->frame();
        $floorY = $this->front($g);
        $planTop = max(560.0, $floorY + 135);
        $this->plan($g, $d, $planTop);
        $this->endView($g);
        $this->detailZ($g);
        $this->panel($in, $d, $calc['warnings'], $title);
        $this->titleBlock($title, (int) ($calc['calc_version'] ?? 1));
        $this->notes();

        $head = sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" font-family="Arial, Helvetica, sans-serif">',
            self::W, self::H, self::W, self::H);

        return $head . $this->defs() . implode('', $this->out) . '</svg>';
    }

    // ---------------------------------------------------------------- sheet

    private function defs(): string
    {
        return '<defs>'
            . '<marker id="cd-arrow" viewBox="0 0 10 6" refX="10" refY="3" markerWidth="9" markerHeight="5.4" markerUnits="userSpaceOnUse" orient="auto-start-reverse">'
            . '<path d="M0,0 L10,3 L0,6 z" fill="#111"/></marker>'
            . '<pattern id="cd-hatch" width="6" height="6" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">'
            . '<line x1="0" y1="0" x2="0" y2="6" stroke="#555" stroke-width="1"/></pattern>'
            . '</defs>'
            . '<rect width="' . self::W . '" height="' . self::H . '" fill="#fff"/>';
    }

    private function frame(): void
    {
        $this->rect(6, 6, self::W - 12, self::H - 12, 'fill="none" stroke="#111" stroke-width="0.8"');
        $this->rect(14, 14, self::W - 28, self::H - 28, 'fill="none" stroke="#111" stroke-width="2"');
    }

    // ---------------------------------------------------------------- front elevation

    private function front(array $g): float
    {
        $s = $g['s'];
        $x0 = $g['x0'];
        $xC = $g['xC'];
        $xEnd = $g['xEnd'];
        $xW = $g['xW'];
        $railL = $g['railL'];
        $railR = $g['railR'];

        $gTop = 190.0;
        $gBot = $gTop + $g['depth'] * $s;
        $railY = $gBot + $g['gOff'] * $s;
        $floorY = $railY + $g['runway'] * $s;
        $r = $g['wheelD'] / 2 * $s;
        $hookY = $railY + $g['approach'] * $s;           // hook at its highest position

        $this->text(40, 54, 'FRONT ELEVATION', 14, 'start', 'font-weight="bold"');
        $this->text(40, 69, 'Looking along the runway. Wheels are seen edge-on.', 10, 'start', '', '#555');

        // ground and walls
        $this->rect($x0 - 70, $floorY, ($xW + 22) - ($x0 - 70), 10, 'fill="url(#cd-hatch)" stroke="none"');
        $this->line($x0 - 70, $floorY, $xW + 22, $floorY, 'stroke="#111" stroke-width="2"');
        $this->text($x0 + 6, $floorY - 5, 'Floor +/-0', 10, 'start');
        $this->rect($x0 - 22, $gTop - 14, 22, $floorY - ($gTop - 14), 'fill="url(#cd-hatch)" stroke="#111" stroke-width="1.2"');
        $this->rect($xW, $gTop - 14, 22, $floorY - ($gTop - 14), 'fill="url(#cd-hatch)" stroke="#111" stroke-width="1.2"');

        // runway beams, brackets and rails (symbols)
        $beamH = max(10.0, 450 * $s);
        $beamW = max(16.0, 320 * $s);
        $railHpx = max(2.5, $g['railH'] * $s);
        $railWpx = max(3.0, $g['railW'] * $s);
        $beamTop = $railY + $railHpx;
        $beamBot = $beamTop + $beamH;
        $bw = min($beamW, 26.0);

        $bxL1 = $x0;
        $bxL2 = max($railL + 8, $x0 + $beamW);
        $bxR2 = $xW;
        $bxR1 = min($railR - 8, $xW - $beamW);
        $this->rect($bxL1, $beamTop, $bxL2 - $bxL1, $beamH, 'fill="#fff" stroke="#111" stroke-width="1.2"');
        $this->rect($bxR1, $beamTop, $bxR2 - $bxR1, $beamH, 'fill="#fff" stroke="#111" stroke-width="1.2"');
        $this->poly([[$x0, $beamBot], [$x0 + $bw, $beamBot], [$x0, $beamBot + $bw * 1.5]], 'fill="#fff" stroke="#111" stroke-width="1.2"');
        $this->poly([[$xW, $beamBot], [$xW - $bw, $beamBot], [$xW, $beamBot + $bw * 1.5]], 'fill="#fff" stroke="#111" stroke-width="1.2"');

        // wheels (edge-on), end carriages, rails
        $ww = max(4.0, $g['wheelW'] * $s);
        $ecW = max(8.0, 2 * $g['ext'] * $s);
        foreach ([$railL, $railR] as $rx) {
            $this->rect($rx - $ww / 2, $railY - 2 * $r, $ww, 2 * $r, 'fill="#fff" stroke="#111" stroke-width="1"');
            $this->rect($rx - $ecW / 2, $gBot, $ecW, $g['gOff'] * $s - 0.9 * $r, 'fill="#fff" stroke="#111" stroke-width="1.2"');
            $this->rect($rx - $railWpx / 2, $railY, $railWpx, $railHpx, 'fill="#111" stroke="none"');
        }

        // girder
        $this->rect($xC, $gTop, $g['total'] * $s, $gBot - $gTop, 'fill="' . self::LIGHT . '" stroke="#111" stroke-width="1.5"');
        $fl = min(6.0, ($gBot - $gTop) * 0.14);
        $this->line($xC, $gTop + $fl, $xEnd, $gTop + $fl, self::THIN);
        $this->line($xC, $gBot - $fl, $xEnd, $gBot - $fl, self::THIN);

        $this->hoist($g['xm'], $gBot, $hookY, $s, false);

        // rail centre lines up to the span dimension
        $ySpan = $gTop - 24;
        $yGap = $gTop - 50;
        $yAll = $gTop - 76;
        foreach ([$railL, $railR] as $rx) {
            $this->line($rx, $railY + 6, $rx, $ySpan - 5, self::CTR);
        }

        // dimensions along the top
        $this->dimH($railL, $railR, $ySpan, 'Span ' . $g['span']);
        $this->dimH($x0, $xC, $yGap, 'min ' . $g['ec'], $gTop - 14, $gTop, 'l');
        $this->dimH($xEnd, $xW, $yGap, 'min ' . $g['ec'], $gTop, $gTop - 14, 'r');
        $this->dimH($xC, $xEnd, $yAll, ['Overall length ' . $g['total'] . ' (Estimate)', $g['total'] . ' (Estimate)'], $gTop, $gTop);

        // heights
        $this->dimV($x0 - 62, $railY, $floorY, 'Runway height ' . $g['runway'], $x0, $x0);
        $this->dimV($g['xm'] + 70, $hookY, $floorY, 'Lifting height ' . $g['lift'], $g['xm'] + 10, null);

        return $floorY;
    }

    // trolley, rope, hook block and hook. Symbols, but the hook tip sits at the real lifting height.
    private function hoist(float $xm, float $gBot, float $hookY, float $s, bool $broken): void
    {
        $tw = max(26.0, 1800 * $s);
        $bw = max(9.0, 250 * $s);
        $bh = max(9.0, 300 * $s);
        $hl = max(7.0, 250 * $s);

        $avail = $hookY - $gBot;
        if ($avail < $bh + $hl + 8) {
            $f = max(0.35, ($avail - 8) / ($bh + $hl));
            $bh *= $f;
            $hl *= $f;
            $bw = max(6.0, $bw * $f);
        }

        $blockTop = $hookY - $hl - $bh;
        $trBot = max($gBot + 3, min($gBot + 650 * $s, $blockTop - 3));
        $th = $trBot - $gBot;

        $this->rect($xm - $tw / 2, $gBot, $tw, $th, 'fill="#fff" stroke="#111" stroke-width="1.2"');
        if ($th > 12) {
            $this->rect($xm - $tw * 0.3, $gBot + $th * 0.3, $tw * 0.6, $th * 0.4, self::THIN);
        }

        if ($blockTop > $trBot) {
            $this->line($xm - 2, $trBot, $xm - 2, $blockTop, self::THIN);
            $this->line($xm + 2, $trBot, $xm + 2, $blockTop, self::THIN);
            if ($broken) {
                $my = ($trBot + $blockTop) / 2;
                $this->line($xm - 7, $my + 1, $xm + 7, $my - 5, 'stroke="#111" stroke-width="1" fill="none"');
                $this->line($xm - 7, $my + 6, $xm + 7, $my, 'stroke="#111" stroke-width="1" fill="none"');
            }
        }

        $this->rect($xm - $bw / 2, $blockTop, $bw, $bh, 'fill="#fff" stroke="#111" stroke-width="1.2"');
        $ring = max(2.0, min(4.0, $hl * 0.4));
        $this->line($xm, $blockTop + $bh, $xm, $hookY - 2 * $ring, 'stroke="#111" stroke-width="1.2" fill="none"');
        $this->add(sprintf('<circle cx="%s" cy="%s" r="%s" fill="#fff" stroke="#111" stroke-width="1.2"/>',
            $this->n($xm), $this->n($hookY - $ring), $this->n($ring)));
    }

    // ---------------------------------------------------------------- plan view

    private function plan(array $g, array $d, float $planTop): void
    {
        $s = $g['s'];
        $x0 = $g['x0'];
        $xC = $g['xC'];
        $xEnd = $g['xEnd'];
        $xW = $g['xW'];
        $railL = $g['railL'];
        $railR = $g['railR'];
        $xm = $g['xm'];

        $endH = $g['endLen'] * $s;
        $planBot = $planTop + $endH;
        $yMid = $planTop + $endH / 2;

        $this->text(40, $planTop - 58, 'PLAN VIEW', 14, 'start', 'font-weight="bold"');
        $this->text(40, $planTop - 43, 'Looking down. Wheels are hidden under the end carriages.', 10, 'start', '', '#555');

        // walls
        $this->rect($x0 - 22, $planTop - 12, 22, $endH + 20, 'fill="url(#cd-hatch)" stroke="#111" stroke-width="1.2"');
        $this->rect($xW, $planTop - 12, 22, $endH + 20, 'fill="url(#cd-hatch)" stroke="#111" stroke-width="1.2"');

        // rail centre lines
        foreach ([$railL, $railR] as $rx) {
            $this->line($rx, $planTop - 14, $rx, $planBot + 44, self::CTR);
        }

        // end carriages and girder
        $ecW = max(8.0, 2 * $g['ext'] * $s);
        $gw = max(10.0, 450 * $s);
        $this->rect($railL + $ecW / 2, $yMid - $gw / 2, ($railR - $railL) - $ecW, $gw, 'fill="' . self::LIGHT . '" stroke="#111" stroke-width="1.4"');
        foreach ([$railL, $railR] as $rx) {
            $this->rect($rx - $ecW / 2, $planTop, $ecW, $endH, 'fill="' . self::LIGHT . '" stroke="#111" stroke-width="1.5"');
        }

        // trolley (symbol)
        $tw = max(26.0, 1800 * $s);
        $th = min($endH * 0.8, max(16.0, 1600 * $s));
        $this->rect($xm - $tw / 2, $yMid - $th / 2, $tw, $th, 'fill="#fff" stroke="#111" stroke-width="1.2"');
        $this->rect($xm - $tw * 0.35, $yMid - $th * 0.2, $tw * 0.7, $th * 0.4, self::THIN);

        // wheels, hidden lines
        $ww = max(4.0, $g['wheelW'] * $s);
        $wh = $g['wheelD'] * $s;
        $wy1 = $planTop + ($g['endLen'] - $g['wheelbase']) / 2 * $s;
        $wy2 = $wy1 + $g['wheelbase'] * $s;
        foreach ([$railL, $railR] as $rx) {
            foreach ([$wy1, $wy2] as $wy) {
                $this->rect($rx - $ww / 2, $wy - $wh / 2, $ww, $wh, self::HIDDEN);
            }
        }

        // wheel load note with a leader
        $wl = $d['max_wheel_load_kn'];
        $this->text($railL + $ecW / 2 + 28, $planTop - 3, 'Max static wheel load ' . $wl['value'] . ' kN' . $this->est($wl), 10.5, 'start');
        $this->line($railL + $ecW / 2 + 24, $planTop - 7, $railL + 2, $wy1, self::THIN . ' marker-end="url(#cd-arrow)"');

        // clearance dimensions above the plan
        $yGap = $planTop - 22;
        $this->dimH($x0, $xC, $yGap, 'min ' . $g['ec'], $planTop - 12, $planTop, 'l');
        $this->dimH($xEnd, $xW, $yGap, 'min ' . $g['ec'], $planTop, $planTop - 12, 'r');

        // left side: wheelbase and end carriage length
        $this->dimV($x0 - 62, $wy1, $wy2,
            ['Wheelbase ' . $g['wheelbase'] . $this->est($d['wheelbase_mm']), $g['wheelbase'] . $this->est($d['wheelbase_mm'])],
            $xC, $xC);
        $this->dimV($x0 - 104, $planTop, $planBot,
            ['End carriage ' . $g['endLen'] . $this->est($d['end_carriage_length_mm']), $g['endLen'] . $this->est($d['end_carriage_length_mm'])],
            $xC, $xC);

        // below: span, then overall length
        $this->dimH($railL, $railR, $planBot + 40, 'Span ' . $g['span']);
        $this->dimH($xC, $xEnd, $planBot + 68,
            ['Overall length ' . $g['total'] . $this->est($d['overall_length_mm']), $g['total'] . $this->est($d['overall_length_mm'])],
            $planBot, $planBot);
    }

    // ---------------------------------------------------------------- end view (looking along the span)

    private function endView(array $g): void
    {
        $cx = 1355.0;
        $this->text(1140, 54, 'END VIEW', 14, 'start', 'font-weight="bold"');
        $this->text(1140, 69, 'Looking along the span. Wheels are seen from the side.', 10, 'start', '', '#555');

        $endLen = $g['endLen'];
        $s2 = min(330 / $endLen, 135 / ($g['gOff'] + $g['depth']), 0.09);   // 0.09 cap keeps small cranes from blowing up
        $r2 = $g['wheelD'] / 2 * $s2;
        $railY = 245.0;
        $gBot = $railY - $g['gOff'] * $s2;
        $gTop = $gBot - $g['depth'] * $s2;
        $gw = max(20.0, 500 * $s2);                       // girder width, symbol
        $half = $endLen / 2 * $s2;
        $wbHalf = $g['wheelbase'] / 2 * $s2;

        // rail and runway beam (symbols), full width of the view
        $railHpx = max(2.0, $g['railH'] * $s2);
        $beamH = max(10.0, 450 * $s2);
        $beamBot = $railY + $railHpx + $beamH;
        $this->rect($cx - 190, $railY + $railHpx, 380, $beamH, 'fill="#fff" stroke="#111" stroke-width="1.2"');
        $this->rect($cx - 190, $railY, 380, $railHpx, 'fill="#111" stroke="none"');

        // end carriage box, then wheels in front
        $boxBot = $railY - 0.9 * $r2;
        $this->rect($cx - $half, $gBot, 2 * $half, $boxBot - $gBot, 'fill="#fff" stroke="#111" stroke-width="1.5"');
        foreach ([-1, 1] as $k) {
            $wx = $cx + $k * $wbHalf;
            $this->add(sprintf('<circle cx="%s" cy="%s" r="%s" fill="#fff" stroke="#111" stroke-width="1.5"/>',
                $this->n($wx), $this->n($railY - $r2), $this->n($r2)));
            $this->add(sprintf('<circle cx="%s" cy="%s" r="%s" fill="none" stroke="#111" stroke-width="0.8"/>',
                $this->n($wx), $this->n($railY - $r2), $this->n(max(1.5, $r2 * 0.25))));
        }

        // girder cross section
        $this->rect($cx - $gw / 2, $gTop, $gw, $gBot - $gTop, 'fill="' . self::LIGHT . '" stroke="#111" stroke-width="1.5"');
        $fl = min(5.0, ($gBot - $gTop) * 0.14);
        $this->line($cx - $gw / 2, $gTop + $fl, $cx + $gw / 2, $gTop + $fl, self::THIN);
        $this->line($cx - $gw / 2, $gBot - $fl, $cx + $gw / 2, $gBot - $fl, self::THIN);

        // trolley and hook. The rope is cut when the hook is far below.
        $drop = $g['approach'] * $s2;
        $maxDrop = $railHpx + $beamH - 6;               // keep the hook inside the beam strip, away from the dimensions
        $hookY = $railY + min($drop, $maxDrop);
        $this->hoist($cx, $gBot, $hookY, $s2, $drop > $maxDrop);

        // wheel centre lines down to the dimension
        $yWb = $beamBot + 38;
        $yEc = $yWb + 28;
        foreach ([-1, 1] as $k) {
            $this->line($cx + $k * $wbHalf, $railY - 2 * $r2 - 6, $cx + $k * $wbHalf, $yWb + 5, self::CTR);
        }

        $this->dimH($cx - $wbHalf, $cx + $wbHalf, $yWb,
            ['Wheelbase ' . $g['wheelbase'] . ' (Estimate)', $g['wheelbase'] . ' (Estimate)']);
        $this->dimH($cx - $half, $cx + $half, $yEc,
            ['End carriage ' . $endLen . ' (Estimate)', $endLen . ' (Estimate)'], $boxBot, $boxBot);
        $this->dimV($cx + $gw / 2 + 56, $gTop, $gBot,
            ['Girder depth ' . $g['depth'] . ' (Estimate)', $g['depth'] . ' (Estimate)'],
            $cx + $gw / 2, $cx + $gw / 2);
    }

    // ---------------------------------------------------------------- detail Z (rail and wheel)

    private function detailZ(array $g): void
    {
        $cx = 1235.0;
        $k = 1.5;                                         // local scale, px per mm
        $railTop = 455.0;
        $ww = $g['wheelW'] * $k;
        $rw = $g['railW'] * $k;
        $rh = $g['railH'] * $k;

        $this->text(1140, 370, 'DETAIL Z (rail and wheel)', 14, 'start', 'font-weight="bold"');

        // wheel (cut), rail, beam flange
        $this->rect($cx - $ww / 2, $railTop - 38, $ww, 38, 'fill="#fff" stroke="#111" stroke-width="1.5"');
        $this->line($cx - $ww / 2, $railTop - 38, $cx - $ww / 4, $railTop - 43, self::THIN);
        $this->line($cx - $ww / 4, $railTop - 43, $cx, $railTop - 35, self::THIN);
        $this->line($cx, $railTop - 35, $cx + $ww / 4, $railTop - 43, self::THIN);
        $this->line($cx + $ww / 4, $railTop - 43, $cx + $ww / 2, $railTop - 38, self::THIN);
        $this->rect($cx - $rw / 2, $railTop, $rw, $rh, 'fill="#ddd" stroke="#111" stroke-width="1.5"');
        $this->rect($cx - 75, $railTop + $rh, 150, 12, 'fill="url(#cd-hatch)" stroke="#111" stroke-width="1.2"');

        $this->dimH($cx - $ww / 2, $cx + $ww / 2, $railTop - 52, (string) $g['wheelW'], $railTop - 38, $railTop - 38);
        $this->dimV($cx + $rw / 2 + 42, $railTop, $railTop + $rh, (string) $g['railH'], $cx + $rw / 2, $cx + $rw / 2);
        $this->dimH($cx - $rw / 2, $cx + $rw / 2, $railTop + $rh + 38, (string) $g['railW'], $railTop + $rh, $railTop + $rh);

        $this->text(1340, 425, 'Rail ' . $g['railW'] . ' x ' . $g['railH'] . ' (reference Detail Z)', 10.5, 'start');
        $this->text(1340, 441, 'Wheel diameter ' . $g['wheelD'] . ' (input)', 10.5, 'start');
        $this->text(1340, 457, 'Wheel width ' . $g['wheelW'] . ' (reference Detail Z)', 10.5, 'start');
        $this->text(1340, 479, 'Drawn at a larger local scale.', 10, 'start', '', '#555');
    }

    // ---------------------------------------------------------------- technical data and title block

    private function panel(array $in, array $d, array $warnings, string $title): void
    {
        $px = 1140.0;
        $pw = 430.0;
        $y0 = 565.0;

        $rows = [
            ['Load', $this->n((float) $in['capacity_t']) . ' t'],
            ['Span', $in['span_mm'] . ' mm'],
            ['Lifting height', $in['lifting_height_mm'] . ' mm'],
            ['Runway height (floor to rail top)', $in['runway_height_mm'] . ' mm'],
            ['End clearance (min)', $in['end_clearance_mm'] . ' mm'],
            ['Crane group (FEM)', (string) $in['duty_group']],
            ['Wheel diameter', $in['wheel_diameter_mm'] . ' mm'],
            ['Rail (reference Detail Z)', config('crane.rail.width_mm') . ' x ' . config('crane.rail.height_mm')],
        ];
        foreach ([['hoist_speed_mpm', 'Hoisting speed'], ['trolley_speed_mpm', 'Trolley speed'], ['travel_speed_mpm', 'Travel speed']] as [$key, $label]) {
            if (!empty($in[$key])) {
                $rows[] = [$label, $in[$key] . ' m/min'];
            }
        }
        foreach (['overall_length_mm', 'wheelbase_mm', 'end_carriage_length_mm', 'girder_depth_mm', 'hook_approach_mm',
                  'trolley_weight_kg', 'crane_weight_kg', 'max_wheel_load_kn'] as $key) {
            $i = $d[$key];
            $rows[] = [$i['label'] . $this->est($i), $this->n((float) $i['value']) . ' ' . $i['unit']];
        }

        $this->text($px + 10, $y0 + 22, 'TECHNICAL DATA', 13, 'start', 'font-weight="bold"');
        $y = $y0 + 34;
        foreach ($rows as [$label, $value]) {
            $y += 16;
            $this->text($px + 10, $y, $label, 11, 'start');
            $this->text($px + $pw - 10, $y, (string) $value, 11, 'end', 'font-weight="bold"');
            $this->line($px + 6, $y + 4.5, $px + $pw - 6, $y + 4.5, 'stroke="#ccc" stroke-width="0.5"');
        }
        foreach ($warnings as $w) {
            foreach (explode("\n", wordwrap($w, 68, "\n", true)) as $chunk) {
                $y += 14;
                $this->text($px + 10, $y + 6, $chunk, 10, 'start', '', '#b00020');
            }
        }
        $this->rect($px, $y0, $pw, ($y + 14) - $y0, 'fill="none" stroke="#111" stroke-width="1.2"');
    }

    private function titleBlock(string $title, int $version): void
    {
        $x = 1140.0;
        $y = 1000.0;
        $w = 430.0;
        $name = mb_strimwidth($title, 0, 46, '...');

        $this->rect($x, $y, $w, 100, 'fill="none" stroke="#111" stroke-width="1.5"');
        foreach ([26, 52, 76] as $dy) {
            $this->line($x, $y + $dy, $x + $w, $y + $dy, 'stroke="#111" stroke-width="0.8"');
        }
        $this->text($x + 10, $y + 18, 'OVERHEAD (EOT) CRANE, SINGLE GIRDER', 12, 'start', 'font-weight="bold"');
        $this->text($x + 10, $y + 44, 'Design: ' . $name, 11, 'start');
        $this->text($x + 10, $y + 69, 'Units: mm   |   Not to scale   |   Calculation version ' . $version, 10.5, 'start');
        $this->text($x + 10, $y + 92, 'PRELIMINARY DRAWING, FOR OFFER ONLY', 11, 'start', 'font-weight="bold"');
    }

    private function notes(): void
    {
        $lines = [
            '(Estimate) = not given in the reference drawing. The source of every value is in config/crane.php and in the table under the drawing.',
            'Symbols only, not to scale: trolley, hook and rope, girder width, runway beam, wall bracket. Front and plan view use one scale for the main dimensions.',
            'Schematic drawing for offers. It is not a manufacturing drawing.',
        ];
        $y = 1062;
        foreach ($lines as $l) {
            $this->text(40, $y, $l, 10, 'start', '', '#444');
            $y += 15;
        }
    }

    // ---------------------------------------------------------------- helpers

    private function add(string $s): void
    {
        $this->out[] = $s;
    }

    // short number: 10.0 -> "10", 72.6 -> "72.6"
    private function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    // all text goes through here so a design name like "<b>" can not break the SVG
    private function e($s): string
    {
        return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function est(array $item): string
    {
        return $item['estimate'] ? ' (Estimate)' : '';
    }

    private function line(float $x1, float $y1, float $x2, float $y2, string $attr = self::DIM): void
    {
        $this->add(sprintf('<line x1="%s" y1="%s" x2="%s" y2="%s" %s/>',
            $this->n($x1), $this->n($y1), $this->n($x2), $this->n($y2), $attr));
    }

    private function rect(float $x, float $y, float $w, float $h, string $attr): void
    {
        $this->add(sprintf('<rect x="%s" y="%s" width="%s" height="%s" %s/>',
            $this->n($x), $this->n($y), $this->n($w), $this->n(max(0, $h)), $attr));
    }

    private function poly(array $pts, string $attr): void
    {
        $p = implode(' ', array_map(fn ($pt) => $this->n($pt[0]) . ',' . $this->n($pt[1]), $pts));
        $this->add('<polygon points="' . $p . '" ' . $attr . '/>');
    }

    // $halo draws a white outline behind the letters so lines never run through the text
    private function text(float $x, float $y, string $t, float $size = 11, string $anchor = 'middle',
                          string $extra = '', string $color = '#111', bool $halo = false): void
    {
        $h = $halo ? ' stroke="#fff" stroke-width="4" stroke-linejoin="round" paint-order="stroke"' : '';
        $this->add(sprintf('<text x="%s" y="%s" font-size="%s" text-anchor="%s" fill="%s" %s%s>%s</text>',
            $this->n($x), $this->n($y), $this->n($size), $anchor, $color, $extra, $h, $this->e($t)));
    }

    private function vtext(float $x, float $y, string $t, float $size = 11): void
    {
        $this->add(sprintf('<text x="%s" y="%s" font-size="%s" text-anchor="middle" fill="#111" stroke="#fff" stroke-width="4" stroke-linejoin="round" paint-order="stroke" transform="rotate(-90 %s %s)">%s</text>',
            $this->n($x), $this->n($y), $this->n($size), $this->n($x), $this->n($y), $this->e($t)));
    }

    // picks the first label that fits into the dimension, the last one is the shortest
    private function fit(array|string $label, float $len): string
    {
        $list = (array) $label;
        foreach ($list as $t) {
            if (strlen($t) * 5.8 <= $len - 8) {
                return $t;
            }
        }

        return (string) end($list);
    }

    // horizontal dimension. $ref1/$ref2 are the y of the features, extension lines start there.
    // Short dimensions get their arrows on the outside, $pos says on which side the text goes.
    private function dimH(float $x1, float $x2, float $y, array|string $label, ?float $ref1 = null, ?float $ref2 = null, string $pos = 'c'): void
    {
        if ($x2 < $x1) {
            [$x1, $x2] = [$x2, $x1];
            [$ref1, $ref2] = [$ref2, $ref1];
        }
        foreach ([[$x1, $ref1], [$x2, $ref2]] as [$x, $ref]) {
            if ($ref !== null) {
                $dir = $y < $ref ? -1 : 1;
                $this->line($x, $ref + 3 * $dir, $x, $y + 4 * $dir, self::EXT);
            }
        }

        $len = $x2 - $x1;
        $text = $this->fit($label, $len);
        if ($len < 30) {
            $this->line($x1 - 16, $y, $x1, $y, self::DIM . ' marker-end="url(#cd-arrow)"');
            $this->line($x2 + 16, $y, $x2, $y, self::DIM . ' marker-end="url(#cd-arrow)"');
            if ($pos === 'l') {
                $this->text($x1 - 20, $y + 4, $text, 11, 'end', '', '#111', true);
            } else {
                $this->text($x2 + 20, $y + 4, $text, 11, 'start', '', '#111', true);
            }

            return;
        }

        $this->line($x1, $y, $x2, $y, self::DIM . self::ARROWS);
        $this->text(($x1 + $x2) / 2, $y - 5, $text, 11, 'middle', '', '#111', true);
    }

    // vertical dimension. $ref1/$ref2 are the x of the features. The text sits left of the line.
    private function dimV(float $x, float $y1, float $y2, array|string $label, ?float $ref1 = null, ?float $ref2 = null): void
    {
        if ($y2 < $y1) {
            [$y1, $y2] = [$y2, $y1];
            [$ref1, $ref2] = [$ref2, $ref1];
        }
        foreach ([[$y1, $ref1], [$y2, $ref2]] as [$y, $ref]) {
            if ($ref !== null) {
                $dir = $x < $ref ? -1 : 1;
                $this->line($ref + 3 * $dir, $y, $x + 4 * $dir, $y, self::EXT);
            }
        }

        $len = $y2 - $y1;
        $text = $this->fit($label, $len);
        if ($len < 30) {
            $this->line($x, $y1 - 16, $x, $y1, self::DIM . ' marker-end="url(#cd-arrow)"');
            $this->line($x, $y2 + 16, $x, $y2, self::DIM . ' marker-end="url(#cd-arrow)"');
            $this->vtext($x - 5, $y1 - 22 - strlen($text) * 2.9, $text);

            return;
        }

        $this->line($x, $y1, $x, $y2, self::DIM . self::ARROWS);
        $this->vtext($x - 5, ($y1 + $y2) / 2, $text);
    }
}
