#!/usr/bin/env node
// Minify theme CSS: style.css -> style.min.css, css/*.css -> css/*.min.css.
// Usage: node tools/minify-css.mjs            (all sheets)
//        node tools/minify-css.mjs css/footer.css style.css   (just these)
// The theme serves the .min twin only while filemtime(min) >= filemtime(src)
// (inc/performance.php, safestore_perf_min_asset_src), so re-run this after
// every CSS edit and upload the .min file LAST.
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join, dirname, basename, extname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { minify } from 'csso';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const args = process.argv.slice(2);
const files = args.length
  ? args
  : ['style.css', ...readdirSync(join(root, 'css')).filter(f => f.endsWith('.css') && !f.endsWith('.min.css')).map(f => 'css/' + f)];

let saved = 0;
for (const rel of files) {
  if (rel.endsWith('.min.css')) continue;
  const src = join(root, rel);
  const css = readFileSync(src, 'utf8');
  // One line, no comments (style.css's theme header too — WordPress reads the
  // header from style.css itself): Rank Math's remote "Minify CSS" check wants pure minified output.
  let out = minify(css, { comments: false }).css.replace(/\r?\n/g, '');
  const dest = join(dirname(src), basename(rel, extname(rel)) + '.min.css');
  writeFileSync(dest, out);
  const a = statSync(src).size, b = statSync(dest).size; saved += a - b;
  console.log(`${rel.padEnd(32)} ${(a/1024).toFixed(1)} KB -> ${(b/1024).toFixed(1)} KB`);
}
console.log(`saved ${(saved/1024).toFixed(1)} KB`);
