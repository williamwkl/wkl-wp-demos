// Minimal GitHub-Pages-like static server: dir -> index.html, 301 to trailing slash, gzip.
// usage: node scripts/static-server.mjs <root> [port]
import http from 'node:http';
import { readFile, stat } from 'node:fs/promises';
import { gzipSync } from 'node:zlib';
import path from 'node:path';
const root = path.resolve(process.argv[2] || 'dist'); const port = +(process.argv[3] || 5055);
const TYPES = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.json': 'application/json', '.svg': 'image/svg+xml', '.webp': 'image/webp', '.png': 'image/png', '.jpg': 'image/jpeg', '.woff2': 'font/woff2', '.xml': 'application/xml', '.txt': 'text/plain', '.ico': 'image/x-icon' };
http.createServer(async (req, res) => {
  const u = new URL(req.url, 'http://x'); let p = path.join(root, decodeURIComponent(u.pathname));
  if (!p.startsWith(root)) { res.writeHead(403).end(); return; }
  try {
    let s = await stat(p);
    if (s.isDirectory()) { if (!u.pathname.endsWith('/')) { res.writeHead(301, { Location: u.pathname + '/' + u.search }).end(); return; } p = path.join(p, 'index.html'); }
    let body = await readFile(p); const type = TYPES[path.extname(p)] || 'application/octet-stream';
    const h = { 'Content-Type': type, 'Cache-Control': 'max-age=600' };
    if (/text|javascript|json|svg|xml/.test(type) && /gzip/.test(req.headers['accept-encoding'] || '')) { body = gzipSync(body); h['Content-Encoding'] = 'gzip'; }
    res.writeHead(200, h).end(body);
  } catch { res.writeHead(404, { 'Content-Type': 'text/html' }).end('<!doctype html><title>404</title><h1>Not found</h1>'); }
}).listen(port, () => console.log(`serving ${root} on http://127.0.0.1:${port}`));
