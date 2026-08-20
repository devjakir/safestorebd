# WhatsApp on SafeStoreBD — setup, failover, and why the number keeps getting blocked

Last updated: 20 August 2026

---

## 1. What changed in the theme

Both numbers now live in **one place**: `Settings → WhatsApp Chat` in wp-admin.

| Slot | Number | Where it shows |
|---|---|---|
| Primary | `+880 1761-699627` | Chat panel (first button), footer, home support bar, product pages, all policy-page CTAs |
| Second line | `+880 1811-892291` | Chat panel (second button), footer |

**Before**, `8801811892291` was hardcoded in 12 files. Changing it meant 12 edits, and missing one left a dead link on a live page. **Now** every WhatsApp link in the theme reads from the settings screen — a blocked number is a one-field fix.

Files changed:

- `inc/whatsapp-chat.php` — dual-line settings, helper API, one-time migration
- `css/whatsapp-chat.css` — styling for the two-button panel
- `footer.php` — one contact row per line
- `templates/home/support-bar.php` — primary as the link, second number in the detail row (the grid is a fixed 4 columns, so a 5th cell would have wrapped)
- 10 page templates — `$wa_href` now calls `safestore_wa_link()`

Helper API for any future template work:

```php
safestore_wa_lines()                  // both lines: slot, number, display, label
safestore_wa_link( 'primary' )        // https://wa.me/8801761699627
safestore_wa_link( 'backup', $text )  // with a pre-filled message
safestore_wa_display( 'primary' )     // "+880 1761-699627"
```

### One thing to check on first admin load

Your database already stores `8801811892291` as the single number. A one-time migration runs the first time you open wp-admin: it promotes `8801761699627` to primary and keeps `8801811892291` as the second line. **Open `Settings → WhatsApp Chat` and confirm both fields read correctly before you consider this done.** If you'd rather have them the other way round, just swap the two fields and save — the migration will not run again.

### Not changed on purpose

The `tel:` links still point at `+880 1811-892291`. A WhatsApp ban does not affect the voice line, so those are a separate concern. Say the word if you want the dialable number centralised too.

---

## 2. Root cause: why the account keeps getting blocked

You're on the **free WhatsApp Business app**. That app is built for one person, on one phone, replying to people who messaged first. Meta's automated systems don't read intent — they read behaviour patterns, and an e-commerce shop's normal day looks a lot like spam to them.

Ranked by how often it's the actual cause for a shop like yours:

**1. Outbound messages to people who never opted in.** Order confirmations, COD checks, delivery updates and "new stock arrived" blasts sent to customers who don't have your number saved. Every recipient who taps *Block* or *Report* feeds a quality signal. Past a threshold the number is restricted automatically — no human involved. This is the single most common cause and it's fully compatible with "we're a legitimate shop", which is why it catches people off guard.

**2. Any third-party automation bolted onto the number.** Bulk sender panels, "WhatsApp marketing" services, WhatsApp Web bots, Baileys / whatsapp-web.js scripts, or a modded APK (GB WhatsApp, WhatsApp Plus, Yo WhatsApp). These violate the terms outright and are detected reliably. **Read section 3 before you act on this one** — it's directly relevant to the failover you asked for.

**3. Volume that looks automated.** The free app was never designed for a team pushing 50–100+ messages a day. Speed and repetition alone can trip the filters, even with perfectly legitimate content.

**4. A cold number ramping too fast.** A newly registered number that immediately messages many unknown contacts has no trust history to draw on. Worth keeping in mind for `+880 1761-699627` specifically — ease it in rather than moving your whole outbound volume onto it on day one.

### Self-check

You skipped the outbound question, so here's how to settle it yourself. Ask whoever handles the phone: *in a normal week, do we message customers who haven't messaged us first?* If the answer is yes for anything beyond a reply to an existing thread, cause **#1** is your answer and the rest of this document is the fix. If the answer is a firm no and it's still happening, look hard at **#2** — including any app a staff member installed without telling you.

---

## 3. The failover you asked for — and the trap in it

Three things worth being straight about:

**WhatsApp has no forwarding.** There is no call-forward, no simultaneous ring, no "if this number is down, deliver to that one". The feature does not exist, on the app or on the API. Every tool that advertises it is an unofficial script driving a logged-in session.

**Which means the obvious fix makes the problem worse.** Auto-forwarding between two numbers requires exactly the kind of unauthorised automation that is cause **#2** above. Wire that up and you don't get failover — you get *both* numbers banned, and the second one faster than the first, because it inherits none of the trust and all of the behaviour. If you've already tried something like this, that alone may explain the repeat blocks.

**Nothing can reliably detect a ban from outside.** There's no public API that answers "is this number currently active on WhatsApp". A `wa.me` link to a banned number just fails in the visitor's browser. So a genuinely automatic swap has nothing trustworthy to trigger on.

### What actually works, and is now live

Both numbers are visible at the same time. A visitor who can't get through on one uses the other in the same tap — no detection, no automation, no delay, nothing to maintain. That's real redundancy from the customer's side, which is the side that matters. And because everything reads from one setting, if a number dies you change one field and the whole site follows in about five seconds.

The `Show both lines` checkbox lets you drop back to a single advertised number whenever you want, without losing the second one.

---

## 4. The durable fix: WhatsApp Business Platform (Cloud API)

The free app will keep getting you blocked because you're using it for something it isn't. The Cloud API is the supported way to do what you're actually doing, and bans stop being a recurring event because the rules are explicit instead of inferred.

What you get:

- Multiple phone numbers under one business account — new portfolios start capped at **2 numbers**, rising to **20** after business verification or once you hit the 2,000-message tier. Real multi-number operation, properly supported.
- A shared team inbox, so staff aren't passing a phone around.
- Message templates pre-approved by Meta, so outbound stops being a gamble.
- A visible quality rating per number, so you see trouble coming instead of waking up to a ban.

Costs, as of the 2026 rules:

- Billing is **per message** (changed from per-conversation on 1 July 2025), priced by category and country.
- **Incoming customer messages are free**, permanently. Since your website drives customer-initiated chats, a large share of your volume sits here.
- **From 1 October 2026 the free 24-hour service window ends** — agent replies inside that window become chargeable at utility/authentication rates. Worth factoring in now, it's about six weeks out.
- Click-to-WhatsApp ads keep a 72-hour free entry window.

One catch to plan around: messaging limits pool across all numbers in a business portfolio, so heavy use on one number affects the others' throughput.

**Practical route for a Bangladesh shop:** go through a Business Solution Provider rather than wiring up Meta directly. They handle verification, the shared inbox and template approvals. Get quotes from a few, and check current per-message rates for Bangladesh before committing — published rate tables move.

**Important:** don't migrate a number that's currently banned. Verify a clean number, or get the ban lifted first.

---

## 5. If a number gets blocked again

1. **Appeal from inside the app.** WhatsApp Business app → *Request a review*. Give the number, business name and a short factual explanation. Most Business app reviews are decided within about 24 hours.
2. **If `Request a review` isn't offered, the ban is final** for that number. No appeal path exists. Move to the second line and start the Cloud API process.
3. **Meanwhile, swap the site over.** `Settings → WhatsApp Chat` → put the working number in the Primary field → Save. Every link on the site updates immediately.
4. **Don't just register a fresh number and carry on.** A brand-new number repeating the same behaviour gets banned faster, and messages from an unrecognised number are more likely to be reported. Fix the behaviour, or the third number goes the same way as the first two.

---

## 6. Recommended order

1. Confirm both numbers in `Settings → WhatsApp Chat`, then check the floating widget on the live site shows two buttons.
2. Answer the outbound question in section 2 honestly. If anything is sending bulk or unsolicited messages — a tool, a service, or a person with a broadcast list — stop it today. Nothing else you do matters while that continues.
3. Ease `+880 1761-699627` into service rather than moving all volume onto it at once.
4. Start Cloud API conversations with two or three BSPs. Treat the current two-number setup as the bridge, not the destination.
