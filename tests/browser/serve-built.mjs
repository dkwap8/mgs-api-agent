// Frontend-only fixture server. Backend behavior is tested separately by PHPUnit.
import { createServer } from 'node:http';
import { readFileSync } from 'node:fs';
import { resolve, sep, extname } from 'node:path';
const root = resolve('public');
const manifest = JSON.parse(readFileSync(root + '/build/manifest.json'));
const css = [...new Set([manifest['resources/css/app.css'].file, ...(manifest['resources/js/app.js'].css || [])])];
const html = `<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">${css.map(file => `<link rel="stylesheet" href="/build/${file}">`).join('')}</head><body><div id="app"></div><script type="module" src="/build/${manifest['resources/js/app.js'].file}"></script></body></html>`;
createServer((request, response) => {
    const path = new URL(request.url, 'http://localhost').pathname;
    if (path.startsWith('/api/') || path.startsWith('/storage/')) { response.writeHead(404); response.end(); return; }
    if (path.startsWith('/build/')) {
        const target = resolve(root, '.' + path);
        if (!target.startsWith(root + sep)) { response.writeHead(404); response.end(); return; }
        try { response.setHeader('Content-Type', extname(target) === '.css' ? 'text/css' : 'text/javascript'); response.end(readFileSync(target)); }
        catch { response.writeHead(404); response.end(); }
        return;
    }
    response.setHeader('Content-Type', 'text/html'); response.end(html);
}).listen(8022, '127.0.0.1', () => console.log('Frontend fixture server on 8022; no real API or database access'));
