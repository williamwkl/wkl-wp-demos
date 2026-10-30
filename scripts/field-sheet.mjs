// Builds a before/after contact sheet of focused fields (WebKit, 390 px) from two field-audit.mjs --shots JSON files.
// usage: node scripts/field-sheet.mjs <before.json> <after.json> <out.png> <title>
import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'node:fs';
const [bf, af, out, title] = process.argv.slice(2);
const B = JSON.parse(readFileSync(bf, 'utf8')), A = JSON.parse(readFileSync(af, 'utf8'));
const norm = l => l.replace(/_[0-9a-f]{10,}/g, '');
B.forEach(s => s.label = norm(s.label)); A.forEach(s => s.label = norm(s.label));
// Pair before/after shots by label, in order (several checkboxes share a field name).
const groups = new Map();
for (const s of A) { if (!groups.has(s.label)) groups.set(s.label, []); groups.get(s.label).push(s); }
const rows = B.map(b => [b, (groups.get(b.label) || []).shift()]);
for (const rest of groups.values()) rest.forEach(a => rows.push([null, a]));
const img = s => s ? `<img src="data:image/png;base64,${s.data}">` : '<span class="na">not shown</span>';
const html = `<!doctype html><meta charset=utf-8><style>body{margin:0;padding:16px;font:13px -apple-system,Arial;background:#fff;color:#111}h1{font-size:18px;margin:0 0 12px}table{border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px;vertical-align:top}th{background:#f3f3f3;text-align:left}img{display:block;max-width:400px}.l{max-width:180px;font-family:Menlo,monospace;font-size:11px}.na{color:#999}</style>
<h1>${title}: focused fields, WebKit (iPhone emulation) at 390 px</h1><table><tr><th>Field</th><th>Before</th><th>After</th></tr>${rows.map(([b, a]) => `<tr><td class=l>${(a || b).label}</td><td>${img(b)}</td><td>${img(a)}</td></tr>`).join('')}</table>`;
const br = await chromium.launch(); const p = await br.newPage({ viewport: { width: 1060, height: 800 } });
await p.setContent(html); await p.screenshot({ path: out, fullPage: true }); await br.close();
console.log(out, rows.length, 'rows');
