# tools/

Build helpers for the SafeStoreBD theme. `cd tools && npm install` once.

| Command | What it does |
|---|---|
| `npm run minify` | both of the next two |
| `npm run minify-css` | `style.css` → `style.min.css`, `css/*.css` → `css/*.min.css` (csso, one line, no comments) |
| `npm run minify-js` | `js/*.js` → `js/*.min.js` (terser, ES5, globals not renamed) |
| `npm run optimize-images` | resizes / re-encodes theme images (sharp) |

The theme serves a `.min.css` / `.min.js` twin only while `filemtime(min) >= filemtime(src)`
(`safestore_perf_min_asset_src()` in `inc/performance.php`; `SCRIPT_DEBUG` forces the
source). So **after editing any CSS or JS: run `npm run minify`, commit the `.min` files
too, and when deploying by hand upload the `.min` files LAST** — an older `.min`
is silently ignored (nothing breaks, the page just gets the unminified file).
