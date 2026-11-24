(() => {
    const API = '/api';
    const $ = (id) => document.getElementById(id);

    let meta = null;        // limits from /api/meta/limits
    let currentId = null;   // id of the design being edited, null = new
    let currentSvg = '';
    let timer = null;
    let page = 1;

    // the form is built from this list + the limits from the API
    const FIELDS = [
        { name: 'name', label: 'Design name', kind: 'text' },
        { name: 'capacity_t', label: 'Capacity (t)', limit: 'capacity_t', step: '0.5' },
        { name: 'span_mm', label: 'Span (mm)', limit: 'span_mm' },
        { name: 'lifting_height_mm', label: 'Lifting height (mm)', limit: 'lifting_height_mm' },
        { name: 'runway_height_mm', label: 'Runway height, floor to rail top (mm)', limit: 'runway_height_mm' },
        { name: 'end_clearance_mm', label: 'End clearance, wall to crane (mm)', limit: 'end_clearance_mm' },
        { name: 'wheel_diameter_mm', label: 'Wheel diameter (mm)', limit: 'wheel_diameter_mm', kind: 'select' },
        { name: 'duty_group', label: 'Duty group (FEM)', kind: 'duty' },
        { name: 'wheelbase_mm', label: 'Wheelbase (mm), empty = auto (Estimate)' },
        { name: 'hoist_speed_mpm', label: 'Hoisting speed (m/min), optional' },
        { name: 'trolley_speed_mpm', label: 'Trolley speed (m/min), optional' },
        { name: 'travel_speed_mpm', label: 'Travel speed (m/min), optional' },
        { name: 'trolley_weight_kg', label: 'Trolley weight (kg), empty = Estimate' },
        { name: 'crane_weight_kg', label: 'Crane weight (kg), empty = Estimate' },
    ];

    function el(tag, attrs = {}, text) {
        const n = document.createElement(tag);
        for (const [k, v] of Object.entries(attrs)) n.setAttribute(k, v);
        if (text !== undefined) n.textContent = text;
        return n;
    }

    async function api(method, url, body) {
        const res = await fetch(API + url, {
            method,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
        });
        let json = null;
        try { json = await res.json(); } catch (e) { /* 204 has no body */ }
        return { ok: res.ok, status: res.status, json };
    }

    function setStatus(text, kind) {
        const s = $('status');
        s.textContent = text;
        s.className = text ? kind : '';
    }

    function buildForm() {
        const form = $('design-form');
        form.innerHTML = '';
        for (const f of FIELDS) {
            const lim = f.limit ? meta.limits[f.limit] : null;
            const wrap = el('div', { class: 'field' });
            wrap.append(el('label', { for: f.name }, f.label));

            let input;
            if (f.kind === 'select') {
                input = el('select', { id: f.name, name: f.name });
                lim.allowed.forEach((v) => input.append(el('option', { value: v }, String(v))));
            } else if (f.kind === 'duty') {
                input = el('select', { id: f.name, name: f.name });
                meta.duty_groups.forEach((v) => input.append(el('option', { value: v }, v)));
            } else if (f.kind === 'text') {
                input = el('input', { id: f.name, name: f.name, type: 'text', maxlength: '100' });
            } else {
                input = el('input', { id: f.name, name: f.name, type: 'number', step: f.step || '1' });
            }
            wrap.append(input);

            if (lim) {
                const range = lim.allowed ? 'allowed: ' + lim.allowed.join(', ') : lim.min + ' to ' + lim.max;
                const est = lim.estimate_range ? ' (Estimate range)' : '';
                wrap.append(el('small', { class: 'hint' }, range + est + ' | ' + lim.source));
            }
            wrap.append(el('span', { class: 'err', id: 'err-' + f.name }));
            form.append(wrap);
        }
    }

    function setDefaults() {
        currentId = null;
        for (const f of FIELDS) {
            const input = $(f.name);
            if (f.name === 'name') input.value = 'New crane';
            else if (f.kind === 'duty') input.value = 'A5';
            else if (f.limit) input.value = meta.limits[f.limit].default;
            else input.value = '';
        }
        updateMode();
    }

    function updateMode() {
        $('mode').textContent = currentId ? 'Editing design #' + currentId : 'New design (not saved)';
    }

    // empty box = null, so optional fields work and the server decides
    function readForm() {
        const d = {};
        for (const f of FIELDS) {
            const v = $(f.name).value.trim();
            if (f.kind === 'text' || f.kind === 'duty') d[f.name] = v;
            else d[f.name] = v === '' ? null : Number(v);
        }
        return d;
    }

    function showErrors(errors = {}) {
        document.querySelectorAll('.err').forEach((e) => { e.textContent = ''; });
        for (const [k, msgs] of Object.entries(errors)) {
            const span = $('err-' + k);
            if (span) span.textContent = msgs.join(' ');
        }
    }

    function renderResult(data) {
        currentSvg = data.svg;
        $('drawing').innerHTML = data.svg; // svg comes from our server, text inside is escaped there

        const w = $('warnings');
        w.innerHTML = '';
        (data.warnings || []).forEach((t) => w.append(el('li', {}, t)));

        const body = $('derived-body');
        body.innerHTML = '';
        for (const d of Object.values(data.derived)) {
            const tr = el('tr');
            tr.append(
                el('td', {}, d.label + (d.estimate ? ' (Estimate)' : '')),
                el('td', {}, d.value + ' ' + d.unit),
                el('td', { class: 'src' }, d.source),
            );
            body.append(tr);
        }
    }

    // live preview: validates and draws, saves nothing
    async function preview() {
        const r = await api('POST', '/designs/preview', readForm());
        if (r.status === 422) {
            showErrors(r.json.errors);
            setStatus('Fix the errors to update the drawing.', 'bad');
            return;
        }
        if (!r.ok) {
            setStatus('Server error (' + r.status + ').', 'bad');
            return;
        }
        showErrors({});
        setStatus('', '');
        renderResult(r.json.data);
    }

    function schedulePreview() {
        clearTimeout(timer);
        timer = setTimeout(preview, 400);
    }

    async function save() {
        const body = readForm();
        const r = currentId
            ? await api('PUT', '/designs/' + currentId, body)
            : await api('POST', '/designs', body);

        if (r.status === 422) {
            showErrors(r.json.errors);
            setStatus('Not saved. Fix the errors.', 'bad');
            return;
        }
        if (!r.ok) {
            setStatus('Server error (' + r.status + ').', 'bad');
            return;
        }
        currentId = r.json.data.id;
        showErrors({});
        renderResult(r.json.data);
        updateMode();
        setStatus('Saved as design #' + currentId, 'ok');
        loadList();
    }

    async function openDesign(id) {
        const r = await api('GET', '/designs/' + id);
        if (!r.ok) {
            setStatus('Could not open design (' + r.status + ').', 'bad');
            return;
        }
        const data = r.json.data;
        for (const f of FIELDS) {
            $(f.name).value = data[f.name] ?? '';
        }
        currentId = id;
        showErrors({});
        renderResult(data);
        updateMode();
        setStatus('', '');
        loadList();
    }

    async function removeDesign(id, name) {
        if (!confirm('Delete "' + name + '"?')) return;
        const r = await api('DELETE', '/designs/' + id);
        if (!r.ok) {
            setStatus('Could not delete (' + r.status + ').', 'bad');
            return;
        }
        if (currentId === id) {
            setDefaults();
            preview();
        }
        loadList();
    }

    async function loadList() {
        const q = encodeURIComponent($('search').value.trim());
        const r = await api('GET', '/designs?page=' + page + '&search=' + q);
        const box = $('list');
        box.innerHTML = '';
        if (!r.ok) {
            box.append(el('p', { class: 'hint' }, 'Could not load the list.'));
            return;
        }
        const p = r.json;
        if (!p.data.length) box.append(el('p', { class: 'hint' }, 'No saved designs.'));

        for (const d of p.data) {
            const row = el('div', { class: 'item' + (d.id === currentId ? ' active' : '') });
            const open = el('button', { type: 'button', class: 'link' }, d.name);
            open.addEventListener('click', () => openDesign(d.id));
            const del = el('button', { type: 'button', class: 'danger' }, 'Delete');
            del.addEventListener('click', () => removeDesign(d.id, d.name));
            row.append(open, el('small', {}, d.capacity_t + ' t, span ' + d.span_mm / 1000 + ' m'), del);
            box.append(row);
        }
        $('page-info').textContent = 'Page ' + p.current_page + ' of ' + p.last_page;
        $('prev').disabled = p.current_page <= 1;
        $('next').disabled = p.current_page >= p.last_page;
    }

    // download what is on screen, works for saved and unsaved designs
    function downloadSvg() {
        if (!currentSvg) return;
        const url = URL.createObjectURL(new Blob([currentSvg], { type: 'image/svg+xml' }));
        const a = el('a', { href: url, download: 'crane-design' + (currentId ? '-' + currentId : '') + '.svg' });
        document.body.append(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    async function init() {
        const r = await api('GET', '/meta/limits');
        if (!r.ok) {
            setStatus('Could not load limits from the API.', 'bad');
            return;
        }
        meta = r.json;
        buildForm();
        setDefaults();

        $('design-form').addEventListener('input', schedulePreview);
        $('save').addEventListener('click', save);
        $('download').addEventListener('click', downloadSvg);
        $('new').addEventListener('click', () => { setDefaults(); showErrors({}); preview(); loadList(); });
        $('search').addEventListener('input', () => { page = 1; loadList(); });
        $('prev').addEventListener('click', () => { page--; loadList(); });
        $('next').addEventListener('click', () => { page++; loadList(); });

        preview();
        loadList();
    }

    init();
})();