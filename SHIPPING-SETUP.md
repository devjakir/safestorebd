# Steadfast Courier shipping — setup

Last updated: 6 November 2026

The delivery charge at checkout used to be two WooCommerce **Flat rate** methods,
both stored in the database: ৳80 for the Dhaka zone and ৳130 for the rest of
Bangladesh. They ignored weight and quantity. The theme now adds a
**Steadfast Courier** shipping method that charges by parcel weight and delivery
area. The new rates start only after the admin steps below.

One fix is live as soon as the theme is deployed. Changing **District** now
re-prices the order straight away. Before, WooCommerce waited until Full Address
was filled, so the order summary could show the previous district's charge,
e.g. Cumilla with Dhaka's ৳80.

---

## 1. How the charge is worked out

```
billable kg = cart weight rounded UP to whole kg (minimum 1 kg)
charge      = first-kg price + (billable kg − 1) × extra-kg price
```

Cart weight is each product's **Weight** (Product data → Shipping) × quantity.
Products with an empty Weight count as **0 kg** by default, so an order of only
such products pays the first-kg price, the same as today.

| Cart weight | Inside Dhaka City | Outside Dhaka City |
|---|---|---|
| up to 1 kg | ৳70 | ৳135 |
| 1.01 – 2 kg | ৳90 | ৳155 |
| 2.01 – 3 kg | ৳110 | ৳175 |
| 4.01 – 5 kg | ৳150 | ৳215 |

These are the defaults: ৳70 / ৳135 for the first kg and +৳20 for each extra kg.

**Which rate applies.** The rate depends on the District and Thana / Upazila
fields at checkout:

| Area | Rate |
|---|---|
| District Dhaka, any city thana (Mirpur, Uttara, Gulshan…) | Inside Dhaka City |
| District Dhaka, thana not picked yet | Inside Dhaka City (the order can't be placed without a thana) |
| Savar, Ashulia, Keraniganj, Dhamrai, Dohar, Nawabganj; all of Gazipur and Narayanganj | Outside Dhaka City, or the optional **Dhaka suburbs** rate if you set one |
| Every other district | Outside Dhaka City |

Steadfast's public rate card (checked 6 Nov 2026, pickup from Dhaka City, home
delivery) is ৳75 inside Dhaka, ৳105 for Dhaka suburbs and ৳135 outside Dhaka for
the first kg, and +৳20 for each extra kg up to 5 kg. Above 5 kg their public
slabs are irregular. Steadfast also takes 1% of the cash it collects (COD). That
fee is billed to the store, not added at checkout.

---

## 2. Switch it on (WooCommerce admin)

1. **Set the prices once.** Go to WooCommerce → Settings → Shipping →
   **Steadfast Courier**. Check the prices and click **Save changes**. These
   prices apply in every zone.
2. **Add the method to each zone.** Go to WooCommerce → Settings → Shipping →
   Shipping zones. For the **Dhaka** zone *and* the zone for the rest of
   Bangladesh: Edit → **Add shipping method** → **Steadfast Courier** → Save.
3. **Turn the old flat rates off.** In the same two zones, switch **Flat rate**
   to disabled. Don't delete it yet; keeping it makes rollback one click.
4. **Check it.** Open the checkout with one item in the cart.
   - District Dhaka + Thana Mirpur should show
     "Steadfast Courier — Inside Dhaka City: ৳70".
   - Thana Savar should show "Outside Dhaka City: ৳135".
   - District Cumilla should show ৳135.
5. **Purge the page cache** (Cloudflare / any cache plugin) so the Shipping &
   Delivery page and product pages show the new prices.

Once the method is on in a zone, the site copy updates itself from these
settings: the Shipping & Delivery page table, its meta description, and the
"Dhaka" / "Nationwide" delivery chips on product pages. Until then they keep
the old wording, so the site never shows a price checkout doesn't charge.

**Rollback:** enable Flat rate again and disable Steadfast Courier in both
zones. The site copy switches back to the old wording on its own.

---

## 3. Product weights

The per-kg charge only kicks in for products that have a weight.

- Enter the **packed** weight in kg in Product data → Shipping → Weight, e.g.
  `0.4` for a helmet, `1.2` for a pair of safety shoes.
- Variable products: a variation with no weight uses the parent's weight.
- Once the courier is switched on, a hint under the Weight field shows the
  current prices.
- Want weightless products to add something anyway? Set **Products with no
  weight count as (kg)** in the Steadfast Courier settings (per unit, e.g. `0.5`).

---

## 4. For staff booking parcels

Every order's shipping line in admin shows **Delivery zone** and **Billable
weight** (e.g. "Inside Dhaka City", "2 kg"). Book the parcel on Steadfast with
that weight. The thana is the order's City field.

---

## 5. Code map

| File | What it does |
|---|---|
| `inc/shipping-courier.php` | Defaults, the charge formula (`safestorebd_courier_charge()`), area detection (`safestorebd_courier_lane()`), method registration, site-copy filters, Weight-field hint |
| `inc/class-safestorebd-shipping-courier.php` | The WooCommerce shipping method: settings page and `calculate_shipping()` |
| `inc/checkout-thana.php` | The Thana / Upazila field (`billing_city`) the rate reads |

For a promo, such as free delivery over ৳5,000, use the
`safestorebd_courier_rate` filter. It receives the rate arguments, the area
(`inside` / `suburb` / `outside`), the weight in kg and the package.
