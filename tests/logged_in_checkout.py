"""Logged-in checkout: order ownership, stock decrement, coupon usage counter.

Run against a freshly seeded database (php database/seed.php --fresh) so the
demo customer starts with three orders and WELCOME10 has used_count = 0.
"""
import os
import re
import subprocess
import sys

import requests

from envfile import get

BASE = "http://127.0.0.1:8000/index.php"
MYSQL = os.environ.get("MYSQL_BIN", r"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe")
DB_USER = get("DB_USERNAME", "root")
DB_PASS = get("DB_PASSWORD", "")
FAILS, PASSES = [], []


def check(name, ok, detail=""):
    (PASSES if ok else FAILS).append(name)
    print(("PASS " if ok else "FAIL ") + name + ((" :: " + str(detail)[:300]) if not ok else ""))


def sql(query):
    out = subprocess.run(
        [MYSQL, f"-u{DB_USER}", f"-p{DB_PASS}", "roidsseek", "-N", "-B", "-e", query],
        capture_output=True, text=True,
    )
    return [line.split("\t") for line in out.stdout.strip().splitlines() if line]


def csrf(html):
    m = re.search(r'name="_token" value="([^"]+)"', html)
    return m.group(1) if m else ""


def get(s, route, **kw):
    return s.get(BASE, params={"r": route, **kw}, timeout=30)


def post(s, route, data, **kw):
    return s.post(BASE, params={"r": route, **kw}, data=data, timeout=30)


s = requests.Session()
r = post(s, "login", {"_token": csrf(get(s, "login").text),
                      "email": "customer@example.com", "password": "Customer@123"})
check("customer login", "My Account" in r.text, r.text[:200])
orders_before = int(sql("SELECT COUNT(*) FROM orders WHERE email='customer@example.com'")[0][0])

r = get(s, "all")
pid = re.search(r'name="product_id" value="(\d+)"', r.text).group(1)
stock_before = int(sql(f"SELECT stock FROM products WHERE id={pid}")[0][0])
post(s, "cart/add", {"_token": csrf(r.text), "product_id": pid, "qty": "2"})
r = get(s, "cart")
r = post(s, "cart/coupon", {"_token": csrf(r.text), "code": "WELCOME10"})
check("coupon applied for logged-in user", "WELCOME10" in r.text.upper(), r.text[:300])
used_before = int(sql("SELECT used_count FROM coupons WHERE code='WELCOME10'")[0][0])

r = get(s, "checkout")
check("checkout prefills customer email", "customer@example.com" in r.text, r.text[:300])
t = csrf(r.text)
r = post(s, "checkout", {
    "_token": t, "email": "customer@example.com", "shipping_name": "Demo Customer",
    "shipping_phone": "5550100", "shipping_address": "9 Demo Ave", "shipping_city": "Austin",
    "shipping_state": "TX", "shipping_postal": "73301", "shipping_country": "USA",
    "notes": "", "payment_method": "bank_transfer",
})
m = re.search(r"number=([A-Za-z0-9\-]+)", r.url)
check("checkout redirect to order", bool(m), (r.url, r.text[:200]))
if m:
    num = m.group(1)
    r = get(s, "account/orders")
    check("new order listed in account", num in r.text, r.text[:300])
    row = sql(f"SELECT user_id, subtotal, discount, shipping_total, total FROM orders WHERE order_number='{num}'")
    check("order linked to user", row and row[0][0] == "2", row)
    if row:
        subtotal, discount, shipping, total = (float(row[0][i]) for i in (1, 2, 3, 4))
        check("discount applied from coupon", abs(discount - round(subtotal * 0.10, 2)) < 0.01,
              (subtotal, discount))
        check("order total consistent", abs(total - (subtotal - discount + shipping)) < 0.01,
              (subtotal, discount, shipping, total))
    check("stock decremented by 2", int(sql(f"SELECT stock FROM products WHERE id={pid}")[0][0]) == stock_before - 2,
          (stock_before, sql(f"SELECT stock FROM products WHERE id={pid}")))
    check("coupon used_count incremented",
          int(sql("SELECT used_count FROM coupons WHERE code='WELCOME10'")[0][0]) == used_before + 1)
    check("order count increased",
          int(sql("SELECT COUNT(*) FROM orders WHERE email='customer@example.com'")[0][0]) == orders_before + 1)
    print("ORDER_NUMBER=" + num)

print("\n==== SUMMARY ====")
print(f"passed: {len(PASSES)}  failed: {len(FAILS)}")
if FAILS:
    print("FAILED:", FAILS)
sys.exit(1 if FAILS else 0)
