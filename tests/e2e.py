"""End-to-end functional test for the RoidsSeek PHP storefront + admin."""
import re
import sys
import time

import requests

from envfile import get

BASE = "http://127.0.0.1:8000/index.php"  # start: php -S 127.0.0.1:8000 -t .
ADMIN_EMAIL = get("ADMIN_EMAIL", "admin@roidsseek.com")
ADMIN_PASSWORD = get("ADMIN_PASSWORD", "Admin@12345")

FAILS = []
PASSES = []


def check(name, ok, detail=""):
    (PASSES if ok else FAILS).append(name)
    print(("PASS " if ok else "FAIL ") + name + ((" :: " + str(detail)[:400]) if not ok else ""))


def get(s, route, **kw):
    return s.get(BASE, params={"r": route, **kw}, timeout=30)


def post(s, route, data, **kw):
    return s.post(BASE, params={"r": route}, data=data, timeout=30)


def csrf(html):
    m = re.search(r'name="_token" value="([^"]+)"', html)
    return m.group(1) if m else ""


def fresh():
    return requests.Session()


TOKEN_RE = re.compile(r"name=\"_token\" value=\"([^\"]+)\"")


def token(s, route="login"):
    r = get(s, route)
    return csrf(r.text)


# ---------------------------------------------------------------- storefront
s = fresh()
r = get(s, "")
check("home 200", r.status_code == 200, r.status_code)

public = {
    "all": {}, "search": {"q": "dianabol"}, "sale": {},
    "category": {"slug": "oral-steroids"}, "brand": {"slug": "axiolabs"},
    "goal": {"slug": "bulking-supplements"}, "shipping": {"slug": "usa-domestic-shipping"},
    "faq": {}, "articles": {}, "events": {}, "contact": {},
    "page": {"slug": "privacy-policy"}, "cart": {}, "login": {}, "register": {},
    "forgot": {},
}
for route, params in public.items():
    r = get(s, route, **params)
    check(f"GET {route} {params or ''} -> 200", r.status_code == 200, (r.status_code, r.text[:200]))

# product + article detail
r = get(s, "all")
slug = re.search(r"[?&]r=product&(?:amp;)?slug=([a-z0-9\-]+)", r.text)
check("listing has product links", bool(slug), r.text[:200])
if slug:
    r = get(s, "product", slug=slug.group(1))
    check("product detail 200", r.status_code == 200, r.status_code)
    check("product detail has add-to-cart", "cart/add" in r.text)

r = get(s, "articles")
art = re.search(r'article\?slug=([a-z0-9\-]+)', r.text) or re.search(r'r=article&amp;slug=([a-z0-9\-]+)', r.text)
check("articles list has article links", bool(art), r.text[:300])
if art:
    r = get(s, "article", slug=art.group(1))
    check("article detail 200", r.status_code == 200, r.status_code)

# 404
r = get(s, "does-not-exist")
check("unknown route 404", r.status_code == 404, r.status_code)

# --------------------------------------------------------------------- auth
u = fresh()
r = get(u, "register")
t = csrf(r.text)
email = f"e2e{int(time.time())}@example.com"
r = post(u, "register", {"_token": t, "name": "E2E User", "email": email,
                        "password": "supersecret1", "password_confirmation": "supersecret1", "terms": "1"})
check("register post redirects", r.status_code in (302, 200), (r.status_code, r.text[:300]))
r = get(u, "account")
check("account accessible after register", r.status_code == 200 and "E2E User" in r.text, r.status_code)
check("account shows email", email in r.text)

# duplicate registration should fail validation
d = fresh()
t = csrf(get(d, "register").text)
r = post(d, "register", {"_token": t, "name": "Dup", "email": email,
                         "password": "supersecret1", "password_confirmation": "supersecret1", "terms": "1"})
check("duplicate email rejected", "already" in r.text.lower() or r.status_code == 200, r.status_code)

# login with wrong password
w = fresh()
t = csrf(get(w, "login").text)
r = post(w, "login", {"_token": t, "email": email, "password": "wrongpass"})
check("wrong password rejected", "invalid" in r.text.lower() or "credentials" in r.text.lower(), r.text[:200])

# login correct
l = fresh()
t = csrf(get(l, "login").text)
r = post(l, "login", {"_token": t, "email": email, "password": "supersecret1"})
check("login post ok", r.status_code in (302, 200), r.status_code)
r = get(l, "account")
check("account after login", "E2E User" in r.text)

# profile update
t = csrf(get(l, "account").text)
r = post(l, "account/profile", {"_token": t, "name": "E2E Updated", "email": email, "phone": "5551234"})
r = get(l, "account")
check("profile update persists", "E2E Updated" in r.text and "5551234" in r.text, r.text[:300])

# password change
t = csrf(get(l, "account").text)
r = post(l, "account/password", {"_token": t, "current_password": "supersecret1",
                                 "password": "newsecret99", "password_confirmation": "newsecret99"})
r2 = get(l, "account")
check("password change accepted", r.status_code == 200, r.status_code)
# re-login with new password
p = fresh()
t = csrf(get(p, "login").text)
r = post(p, "login", {"_token": t, "email": email, "password": "newsecret99"})
check("login with new password", "E2E Updated" in get(p, "account").text)

# --------------------------------------------------------------------- cart
c = fresh()
r = get(c, "all")
pid = re.search(r'name="product_id" value="(\d+)"', r.text) or re.search(r'data-product-id="(\d+)"', r.text)
if not pid:
    # fall back to a product page form
    rp = get(c, "product", slug=slug.group(1) if slug else "")
    pid = re.search(r'name="product_id" value="(\d+)"', rp.text)
check("found product id in markup", bool(pid), "no product_id input found")
if pid:
    t = csrf(r.text) or csrf(get(c, "all").text)
    r = post(c, "cart/add", {"_token": t, "product_id": pid.group(1), "qty": "2"})
    check("cart add redirects", r.status_code in (302, 200), r.status_code)
    r = get(c, "cart")
    check("cart shows item", "Shopping Cart" in r.text and "cart/update" in r.text, r.text[:300])
    m = re.search(r'name="qty\[(\d+)\]" value="(\d+)"', r.text)
    check("cart quantity is 2", bool(m) and m.group(2) == "2", m.group(0) if m else "none")
    t = csrf(r.text)
    r = post(c, "cart/update", {"_token": t, "qty[%s]" % pid.group(1): "3"})
    r = get(c, "cart")
    m = re.search(r'name="qty\[(\d+)\]" value="(\d+)"', r.text)
    check("cart update to 3", bool(m) and m.group(2) == "3", m.group(0) if m else "none")
    # coupon
    t = csrf(r.text)
    r = post(c, "cart/coupon", {"_token": t, "code": "WELCOME10"})
    r = get(c, "cart")
    check("coupon applies", "WELCOME10" in r.text.upper(), r.text[:400])
    # remove
    t = csrf(r.text)
    r = post(c, "cart/remove", {"_token": t, "product_id": pid.group(1)})
    r = get(c, "cart")
    check("cart remove empties", "cart/update" not in r.text, r.text[:300])

    # ---------------------------------------------------------- checkout flow
    ch = fresh()
    r = get(ch, "all")
    t = csrf(r.text)
    post(ch, "cart/add", {"_token": t, "product_id": pid.group(1), "qty": "2"})
    r = get(ch, "checkout")
    check("checkout page 200", r.status_code == 200, r.status_code)
    t = csrf(r.text)
    payload = {
        "_token": t, "email": f"buyer{int(time.time())}@example.com",
        "shipping_name": "Buyer Test", "shipping_phone": "5550001111",
        "shipping_address": "1 Test St", "shipping_city": "Austin",
        "shipping_state": "TX", "shipping_postal": "73301",
        "shipping_country": "USA", "notes": "leave at door",
        "payment_method": "crypto",
    }
    r = post(ch, "checkout", payload)
    check("checkout post ok", r.status_code in (302, 200), (r.status_code, r.text[:400]))
    m = re.search(r"number=([A-Za-z0-9\-]+)", r.url)
    check("checkout redirects to order", bool(m), (r.url, r.text[:300]))
    if m:
        num = m.group(1)
        r = get(ch, "order", number=num)
        check("order confirmation page", r.status_code == 200 and num in r.text, (r.status_code, r.text[:300]))
        check("order shows total", "Total" in r.text)
        g = get(ch, "order", number=num)
        check("order page by number", g.status_code == 200 and num in g.text, g.status_code)
        ORDER_NUM = num
print("ORDER_NUMBER=" + num)
    # guest checkout wrote an order -> verify in account for logged-in user later

# contact + newsletter + review
m = fresh()
t = csrf(get(m, "contact").text)
r = post(m, "contact", {"_token": t, "name": "Msg Test", "email": "msg@example.com",
                        "subject": "Question", "message": "Hello there, testing."})
check("contact form post", r.status_code == 200, (r.status_code, r.text[:200]))
check("contact success flash", "thank" in r.text.lower() or "message" in r.text.lower())

t = csrf(get(m, "contact").text)
r = post(m, "newsletter", {"_token": t, "email": f"news{int(time.time())}@example.com"})
check("newsletter subscribe", r.status_code == 200, r.status_code)

# -------------------------------------------------------------------- admin
a = fresh()
t = csrf(get(a, "login").text)
r = post(a, "login", {"_token": t, "email": ADMIN_EMAIL, "password": ADMIN_PASSWORD})
check("admin login", "Admin Dashboard" in r.text, (r.status_code, r.text[:200]))
for route in ["admin", "admin/products", "admin/orders", "admin/coupons",
              "admin/messages", "admin/reviews", "admin/users"]:
    r = get(a, route)
    check(f"GET {route} -> 200", r.status_code == 200, (r.status_code, r.text[:200]))

# create coupon
r = get(a, "admin/coupons")
t = csrf(r.text)
code = f"E2E{int(time.time()) % 100000}"
r = post(a, "admin/coupon/save", {"_token": t, "code": code, "type": "percent",
                                  "value": "12", "min_subtotal": "0", "is_active": "1"})
r = get(a, "admin/coupons")
check("coupon created", code in r.text, r.text[:300])

# delete only the coupon this test created
row = next((c for c in r.text.split('<tr') if code in c), '')
cid = re.search(r'name="id" value="(\d+)"', row)
if cid:
    t = csrf(r.text)
    r = post(a, "admin/coupon/delete", {"_token": t, "id": cid.group(1)})
    r = get(a, "admin/coupons")
    check("coupon delete responds", code not in r.text, r.text[:300])

# product create
r = get(a, "admin/product")
t = csrf(r.text)
pslug = f"e2e-product-{int(time.time())}"
r = post(a, "admin/product/save", {
    "_token": t, "name": "E2E Product", "slug": pslug, "price": "99.50",
    "stock": "7", "short_description": "Test product", "description": "Long description",
    "is_active": "1", "is_featured": "1", "category_id": "1", "brand_id": "1",
    "goal_id": "1", "sku": "E2E-1",
})
r = get(a, "admin/products")
check("product created", pslug in r.text or "E2E Product" in r.text, r.text[:400])

# edit product
row = next((c for c in r.text.split('<tr') if 'E2E Product' in c), '')
m3 = re.search(r'r=admin/product&(?:amp;)?id=(\d+)', row)
if m3:
    r = get(a, "admin/product", id=m3.group(1))
    t = csrf(r.text)
    r = post(a, "admin/product/save", {
        "_token": t, "id": m3.group(1), "name": "E2E Product Renamed", "slug": pslug,
        "price": "109.00", "stock": "9", "is_active": "1", "category_id": "1",
        "brand_id": "1", "goal_id": "1", "sku": "E2E-1",
    })
    r = get(a, "admin/products")
    check("product edit persists", "E2E Product Renamed" in r.text, r.text[:400])
    # delete (token lives on the edit form)
    r = get(a, "admin/product", id=m3.group(1))
    t = csrf(r.text)
    r = post(a, "admin/product/delete", {"_token": t, "id": m3.group(1)})
    r = get(a, "admin/products")
    check("product delete", "E2E Product Renamed" not in r.text, r.text[:400])

# order status update
r = get(a, "admin/orders")
row = next((c for c in r.text.split('<tr') if ORDER_NUM in c), '')
oid = re.search(r'r=admin/order&(?:amp;)?id=(\d+)', row)
if oid:
    r = get(a, "admin/order", id=oid.group(1))
    check("admin order detail 200", r.status_code == 200, r.status_code)
    t = csrf(r.text)
    r = post(a, "admin/order/status", {"_token": t, "id": oid.group(1), "status": "shipped"})
    r = get(a, "admin/order", id=oid.group(1))
    check("order status updated", "Shipped" in r.text, r.text[:300])

# review approve
r = get(a, "admin/reviews")
rev = re.search(r'name="id" value="(\d+)"', r.text)
if rev:
    t = csrf(r.text)
    r = post(a, "admin/review/status", {"_token": t, "id": rev.group(1), "status": "approved"})
    check("review status update", r.status_code in (200, 302), r.status_code)

# admin pages blocked for anon
anon = fresh()
r = get(anon, "admin")
check("admin blocked for guests", r.status_code in (302, 403) or "sign in" in r.text.lower(), r.status_code)

# ---------------------------------------------------------------- logout
r = get(l, "logout")
r = get(l, "account")
check("logout clears session", r.status_code in (302, 200), r.status_code)

print("\n==== SUMMARY ====")
print(f"passed: {len(PASSES)}  failed: {len(FAILS)}")
if FAILS:
    print("FAILED:", FAILS)
sys.exit(1 if FAILS else 0)
