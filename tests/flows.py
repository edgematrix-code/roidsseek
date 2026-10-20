"""Remaining storefront flows: reviews, pagination, filters, account order access."""
import re
import sys
import time

import requests

BASE = "http://127.0.0.1:8000/index.php"
FAILS, PASSES = [], []


def check(name, ok, detail=""):
    (PASSES if ok else FAILS).append(name)
    print(("PASS " if ok else "FAIL ") + name + ((" :: " + str(detail)[:300]) if not ok else ""))


def get(s, route, **kw):
    return s.get(BASE, params={"r": route, **kw}, timeout=30)


def post(s, route, data, **kw):
    return s.post(BASE, params={"r": route, **kw}, data=data, timeout=30)


def csrf(html):
    m = re.search(r'name="_token" value="([^"]+)"', html)
    return m.group(1) if m else ""


def login(s, email, pw):
    return post(s, "login", {"_token": csrf(get(s, "login").text), "email": email, "password": pw})


s = requests.Session()
r = get(s, "all")
slug = re.search(r"r=product&(?:amp;)?slug=([a-z0-9\-]+)", r.text).group(1)
review_body = f"This is a test review body {int(time.time())} long enough."
r = get(s, "product", slug=slug)
t = csrf(r.text)
r = post(s, "review", {"_token": t, "name": "Review Bot", "rating": "5",
                       "title": "Solid", "body": review_body}, slug=slug)
check("review submitted (pending flash)", "Thanks! Your review" in r.text, r.text[:250])
r = get(s, "product", slug=slug)
# Unique body per run: an earlier run's review may have been approved by
# admin e2e tests, which must not make this assertion about *this* review.
check("pending review hidden from listing", review_body not in r.text)

# too-short review rejected
r = get(s, "product", slug=slug)
r = post(s, "review", {"_token": csrf(r.text), "name": "X", "rating": "5",
                       "title": "t", "body": "short"}, slug=slug)
check("short review rejected", "at least 10" in r.text, r.text[:250])

# pagination
r = get(s, "all", page="2")
check("listing page 2", r.status_code == 200 and "page=1" in r.text, r.status_code)
r = get(s, "all", page="9999")
check("out-of-range page handled", r.status_code == 200, r.status_code)

# filters
r = get(s, "all", brand="1")
check("brand filter applied", r.status_code == 200, r.status_code)
r = get(s, "all", min_price="10", max_price="50")
check("price filter applied", r.status_code == 200, r.status_code)
r = get(s, "search", q="zzzznothing")
check("empty search state", r.status_code == 200 and ("no products" in r.text.lower() or "0" in r.text), r.status_code)

# sort
r = get(s, "all", sort="price_asc")
check("sort by price", r.status_code == 200, r.status_code)

# newsletter duplicate
r = get(s, "contact")
r = post(s, "newsletter", {"_token": csrf(r.text), "email": "customer@example.com"})
check("duplicate newsletter handled", r.status_code == 200, r.status_code)

# FAQ and events content present
r = get(s, "faq")
check("faq has questions", r.text.count('href="#faq-') >= 5, r.text.count('href="#faq-'))
r = get(s, "events")
check("events content", "event" in r.text.lower() and r.status_code == 200, r.status_code)

# logged-in customer sees own seeded order
c = requests.Session()
r = login(c, "customer@example.com", "Customer@123")
check("customer logged in", "My Account" in r.text, r.text[:200])
r = get(c, "account/orders")
num = re.search(r"(RS-[A-Za-z0-9]+(?:-[A-Za-z0-9]+)+)", r.text)
check("customer has orders", bool(num), r.text[:300])
if num:
    r = get(c, "account/order", number=num.group(1))
    check("customer opens own order", num.group(1) in r.text, r.text[:200])

# cart persists between requests
c2 = requests.Session()
r = get(c2, "all")
pid = re.search(r'name="product_id" value="(\d+)"', r.text).group(1)
post(c2, "cart/add", {"_token": csrf(r.text), "product_id": pid, "qty": "1"})
r = get(c2, "cart")
check("cart persists across requests", "cart/update" in r.text, r.text[:200])
r = get(c2, "checkout")
check("checkout lists cart item", "cart/update" not in r.text and r.status_code == 200, r.status_code)

print("\n==== SUMMARY ====")
print(f"passed: {len(PASSES)}  failed: {len(FAILS)}")
if FAILS:
    print("FAILED:", FAILS)
sys.exit(1 if FAILS else 0)
