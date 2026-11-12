#!/usr/bin/env node
// Minify theme JS: js/*.js -> js/*.min.js (terser). Same contract as minify-css:
// the theme serves the .min twin only while filemtime(min) >= filemtime(src).
// Usage: node tools/minify-js.mjs            (all)
//        node tools/minify-js.mjs js/foo.js  (just these)
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join, dirname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import { minify } from 'terser';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const args = process.argv.slice(2);
const files = args.length
  ? args
  : readdirSync(join(root, 'js')).filter(f => f.endsWith('.js') && !f.endsWith('.min.js')).map(f => 'js/' + f);

let saved = 0;
for (const rel of files) {
  if (rel.endsWith('.min.js')) continue;
  const src = join(root, rel);
  const code = readFileSync(src, 'utf8');
  const out = await minify(code, {
    ecma: 5,            // theme JS is ES5-style; keep it that way for old Android browsers
    compress: { passes: 2 },
    mangle: { toplevel: false }, // never rename globals other files or inline PHP may call
    format: { comments: false },
  });
  if (out.error) throw out.error;
  const dest = join(dirname(src), basename(rel, '.js') + '.min.js');
  writeFileSync(dest, out.code);
  const a = statSync(src).size, b = statSync(dest).size; saved += a - b;
  console.log(`${rel.padEnd(32)} ${(a/1024).toFixed(1)} KB -> ${(b/1024).toFixed(1)} KB`);
}
console.log(`saved ${(saved/1024).toFixed(1)} KB`);
