<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crane Drawing Generator</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; font-size: 14px; color: #222; background: #f4f5f7; }
        header { background: #1f2d3d; color: #fff; padding: 12px 20px; }
        header h1 { margin: 0; font-size: 18px; }
        header small { color: #b8c4d0; }
        main { display: grid; grid-template-columns: 240px 340px 1fr; gap: 14px; padding: 14px; align-items: start; }
        .panel { background: #fff; border: 1px solid #d9dde3; border-radius: 6px; padding: 12px; }
        .panel h2 { margin: 0 0 10px; font-size: 15px; }
        .field { margin-bottom: 10px; }
        .field label { display: block; font-weight: bold; margin-bottom: 3px; }
        .field input, .field select { width: 100%; padding: 6px; border: 1px solid #aab; border-radius: 4px; }
        .hint { display: block; color: #667; font-size: 11px; margin-top: 2px; }
        .err { display: block; color: #b00020; font-size: 12px; margin-top: 2px; }
        button { padding: 7px 12px; border: 1px solid #456; background: #fff; border-radius: 4px; cursor: pointer; }
        button.primary { background: #1f6feb; color: #fff; border-color: #1f6feb; }
        button.danger { color: #b00020; border-color: #b00020; padding: 2px 6px; font-size: 12px; }
        button.link { border: 0; background: none; color: #1f6feb; padding: 0; text-align: left; font-weight: bold; }
        button:disabled { opacity: .5; cursor: default; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .item { border-bottom: 1px solid #eee; padding: 6px 0; display: grid; gap: 2px; }
        .item.active { background: #eef4ff; }
        .item small { color: #667; }
        #status { padding: 8px; margin-bottom: 10px; border-radius: 4px; display: none; }
        #status.ok { display: block; background: #e6f6ec; color: #115c2a; }
        #status.bad { display: block; background: #fdeaea; color: #b00020; }
        #warnings { color: #b00020; margin: 0 0 10px; padding-left: 18px; }
        #drawing { overflow: auto; border: 1px solid #d9dde3; background: #fff; }
        #drawing svg { width: 100%; height: auto; min-width: 900px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td { border-bottom: 1px solid #eee; padding: 5px; vertical-align: top; }
        td.src { color: #667; font-size: 12px; }
        .row { display: flex; gap: 6px; align-items: center; }
        @media (max-width: 1100px) { main { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header>
    <h1>Overhead Crane Drawing Generator</h1>
    <small>Single girder EOT crane. Preliminary schematic. Values marked (Estimate) are not from the reference drawing.</small>
</header>

<main>
    <aside class="panel">
        <h2>Saved designs</h2>
        <input id="search" type="search" placeholder="Search by name" style="width:100%;padding:6px;margin-bottom:8px">
        <div id="list"></div>
        <div class="row" style="margin-top:8px">
            <button id="prev" type="button">Prev</button>
            <small id="page-info"></small>
            <button id="next" type="button">Next</button>
        </div>
    </aside>

    <section class="panel">
        <h2 id="mode">New design (not saved)</h2>
        <form id="design-form" novalidate></form>
        <div class="actions">
            <button id="save" type="button" class="primary">Save</button>
            <button id="new" type="button">New</button>
            <button id="download" type="button">Download SVG</button>
        </div>
    </section>

    <section class="panel">
        <h2>Drawing</h2>
        <div id="status"></div>
        <ul id="warnings"></ul>
        <div id="drawing"></div>
        <table><tbody id="derived-body"></tbody></table>
    </section>
</main>

<script src="/js/app.js"></script>
</body>
</html>