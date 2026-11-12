# tools/

Build helpers for the SafeStoreBD theme. `cd tools && npm install` once.

| Command | What it does |
|---|---|
| `npm run minify-css` | `style.css` → `style.min.css`, `css/*.css` → `css/*.min.css` (csso, one line, no comments) |
| `npm run optimize-images` | resizes / re-encodes theme images (sharp) |

The theme serves a `.min.css` twin only while `filemtime(min) >= filemtime(src)`
(`safestore_perf_min_asset_src()` in `inc/performance.php`; `SCRIPT_DEBUG` forces the
source). So **after editing any CSS: run `npm run minify-css`, commit the `.min.css`
too, and when deploying by hand upload the `.min.css` file LAST** — an older `.min`
is silently ignored (nothing breaks, the page just gets the unminified file).
