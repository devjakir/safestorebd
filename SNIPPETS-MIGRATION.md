# Code Snippets → theme migration — reviews tab & HUSKY shop filter

Last updated: 23 August 2026

Two behaviours that only existed in the live **database** (Code Snippets plugin,
`wp_snippets`) now live in version-controlled theme files. This note records what
moved, what deliberately stayed in the database, and the one ordering rule that
stops the same hooks running twice.

---

## 1. Required plugins

This theme repo contains no plugins — `.git` sits at `wp-content/themes/safestorebd`,
so plugins are outside its root by construction. Install these on any environment
that has to match live.

| Plugin | Slug | Version on live | Status | Why |
|---|---|---|---|---|
| HUSKY – Products Filter for WooCommerce | `woocommerce-products-filter` | 1.4.3.1 (free) | **Required** | Provides the `[woof]` shortcode that `inc/shop-filter.php` renders above the shop and category grid. Without it the theme code is inert — `shortcode_exists( 'woof' )` returns false and nothing prints. No fatal, just no filter bar. |
| Code Snippets | `code-snippets` | — | **Optional** after this migration | Was the only home for the two PHP snippets below. Keep it installed if other snippets still live in it; the two snippets named in section 3 must be deactivated either way. |
| WooCommerce | `woocommerce` | 11.0.1 | **Required** | Already a hard dependency of the theme. |

Local dev note: neither `code-snippets` nor `woocommerce-products-filter` is
installed in `Local Sites/safestorebd`. The shop filter will not appear locally
until HUSKY is installed there. The reviews tab works locally with no extra plugin.

---

## 2. What now lives in code

| File | Was | Does |
|---|---|---|
| `inc/product-reviews.php` | Snippet *"Re-add WooCommerce Reviews tab (custom theme)"* | Forces `comments_open()` true for `product` posts, plus a late `woocommerce_product_tabs` fallback that re-adds a `reviews` tab if one is missing. |
| `inc/shop-filter.php` | Snippet *"Show product filter (HUSKY) above shop grid"* | Renders `[woof]` on `woocommerce_before_shop_loop` (priority 15), wrapped in `.sft-shop-filter`. Enqueues the stylesheet on shop / category / tag archives only. |
| `css/shop-filter.css` | Inline `<style>` printed into `wp_head` by the same snippet | The same rules, now a cacheable stylesheet with a `filemtime()` cache-buster. |
| `functions.php` | — | Two `require` lines for the files above. |

Changes made while porting, all behaviour-neutral:

- Anonymous closures became named `safestore_minimal_*` functions, so they can be
  unhooked with `remove_filter()` / `remove_action()` later.
- The inline `<style>` moved into `css/shop-filter.css` and is enqueued properly
  (dependency `safestore-minimal-style`, version = `filemtime()`).
- The render gate now matches the CSS gate. The snippet rendered the bar on every
  `woocommerce_before_shop_loop` but only styled it on shop / category / tag;
  both now use `safestore_minimal_shop_filter_enabled()`.
- A static guard in `safestore_minimal_shop_filter_render()` prevents a second bar
  if HUSKY's own auto-insert option is ever switched on.
- Filter priorities are unchanged from the snippets and are load-bearing.
  `safestore_minimal_restore_reviews_tab()` stays at **98** because WooCommerce
  registers `woocommerce_sort_product_tabs` at 99 — a tab added at 99 or later
  lands after the sort and its `priority` value is ignored. The tab's own
  `priority` stays **40**; it sorts last, after description (10), specifications
  (20) and delivery (25). `safestore_minimal_shop_filter_render()` stays on
  `woocommerce_before_shop_loop` at **15**, i.e. after WooCommerce notices (10)
  and before the catalog ordering dropdown (30).

### Why the reviews tab was missing in the first place

The theme never removed it. WooCommerce 11.0.1 adds the tab in
`woocommerce_default_product_tabs()` only when `comments_open()` is true
(`includes/wc-template-functions.php`, the `comments_open()` gate). Imported
products carry `comment_status = closed`, so the tab was never created. The
`comments_open` filter is the actual fix; the `woocommerce_product_tabs` half is
a fallback and will normally be a no-op.

---

## 3. Deployment order — read this before deploying

The theme code and the DB snippets do the same work. While both are live, every
hook fires twice: **duplicate Reviews tab, filter bar rendered twice.**

1. Merge and deploy the theme to live (Hostinger Git pull).
2. Load a product page and a category page. Confirm the duplicates are visible —
   that proves the theme code is actually running.
3. **Then** go to wp-admin → **Snippets** and deactivate both:
   - `Re-add WooCommerce Reviews tab (custom theme)`
   - `Show product filter (HUSKY) above shop grid`
4. Re-check both pages: one Reviews tab, one filter bar. Purge the
   Hostinger / LiteSpeed cache if the shop page still looks stale.
5. Leave the snippets deactivated (not deleted) for a week as a rollback path,
   then delete them.

**Deploy first, deactivate second.** The reverse order — deactivating the snippets
before the theme lands — leaves live with no reviews tab and no filter for the
length of the deploy. A brief double render is the safer failure.

Do not deactivate the **Code Snippets plugin** itself in step 3, only those two
snippets. Other snippets may still be in there.

---

## 4. What stays in the database

None of this is in the repo, and none of it is recreated by a deploy.

### HUSKY filter configuration — `wp_options`

Stored under `woof_settings` and companion keys. Current live configuration:

| Setting | Value |
|---|---|
| Brands (`product_brand`) | **On** — type `mselect` (multi drop-down) |
| Product categories (`product_cat`) | **On** — type `select` (drop-down) |
| Product tags (`product_tag`) | **Off** |
| Price | ion range-slider |
| In stock | **On** |
| On sale | **On** |
| `woof_try_ajax` | `1` |
| `woof_autosubmit` | `1` |
| `woof_set_automatically` | `0` — placement is owned by `inc/shop-filter.php` |

`woof_set_automatically` must stay `0`. Turning it on makes HUSKY insert its own
filter in addition to the theme's.

### WooCommerce review settings — `wp_options`

WooCommerce → Settings → Products → Reviews: product reviews enabled, star
ratings enabled. Unchanged by this migration, but the reviews tab depends on them.

### Fresh install checklist

A brand-new environment needs section 4 configured **by hand**:

1. Install and activate `woocommerce-products-filter` (HUSKY).
2. HUSKY settings → enable Brands (`mselect`) and Product categories (`select`);
   leave Product tags off.
3. Enable Price (ion range-slider), In stock, On sale.
4. Turn on AJAX and auto-submit; leave "insert filter automatically" **off**.
5. WooCommerce → Settings → Products → Reviews: enable reviews and star ratings.

---

## 5. Database risk

**The deploy pipeline does not touch the database.** The git repo root is the
theme directory, the remote is `github.com/devjakir/safestorebd`, and Hostinger's
Git deployment updates `wp-content/themes/…` only — see the notes in
`inc/smtp.php` and `deploy/wp-config-smtp-snippet.php`. There is no GitHub
Actions workflow, no rsync/FTP script, and no `.sql` file anywhere in the repo.
Pushing and deploying cannot wipe `woof_settings`.

The real risk is a **manual database import**. `Local Sites/safestorebd/app/sql/local.sql`
is Local's export of the *local* database, which has no HUSKY plugin and no
`woof_settings` row. Importing it to live — directly, or via a migration plugin,
or via a "push to host" from Local — silently erases the entire filter
configuration, and the filter bar will render empty or not at all.

Before any database operation on live:

```bash
wp db export live-options-backup.sql --tables=wp_options
```

If the configuration is lost anyway, section 4's checklist is the rebuild recipe.

---

## 6. Rollback

The theme side unhooks cleanly, because everything is a named function:

```php
// Reviews tab
remove_filter( 'comments_open', 'safestore_minimal_products_comments_open', 20 );
remove_filter( 'woocommerce_product_tabs', 'safestore_minimal_restore_reviews_tab', 98 );

// Shop filter bar
remove_action( 'woocommerce_before_shop_loop', 'safestore_minimal_shop_filter_render', 15 );
remove_action( 'wp_enqueue_scripts', 'safestore_minimal_shop_filter_assets', 24 );
```

Or comment out the two `require` lines in `functions.php` and re-activate the DB
snippets.
