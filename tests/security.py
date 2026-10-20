"""Security/authorization checks: admin gating, CSRF, reset flow, order isolation."""
import re
import sys
import time

import requests

from envfile import get

BASE = "http://127.0.0.1:8000/index.php"
ADMIN_EMAIL = get("ADMIN_EMAIL", "admin@roidsseek.com")
ADMIN_PASSWORD = get("ADMIN_PASSWORD", "Admin@12345")
FAILS, PASSES = [], []


def check(name, ok, detail=""):
    (PASSES if ok else FAILS).append(name)
    print(("PASS " if ok else "FAIL ") + name + ((" :: " + str(detail)[:300]) if not ok else ""))


def get(s, route, **kw):
    return s.get(BASE, params={"r": route, **kw}, timeout=30, allow_redirects=True)


def post(s, route, data, **kw):
    return s.post(BASE, params={"r": route}, data=data, timeout=30, allow_redirects=True)


def csrf(html):
    m = re.search(r'name="_token" value="([^"]+)"', html)
    return m.group(1) if m else ""


def login(s, email, password):
    t = csrf(get(s, "login").text)
    return post(s, "login", {"_token": t, "email": email, "password": password})


# customer cannot reach admin
cust = requests.Session()
r = login(cust, "customer@example.com", "Customer@123")
check("customer login", "My Account" in r.text or "Sign out" in r.text, r.text[:200])
r = get(cust, "admin")
check("customer blocked from admin dashboard", "Admin Dashboard" not in r.text, r.text[:200])
r = get(cust, "admin/products")
check("customer blocked from admin products", "Admin Dashboard" not in r.text and "New product" not in r.text)

# unauthenticated account redirects to login
anon = requests.Session()
r = get(anon, "account")
check("guest redirected from account to login", "r=login" in r.url and "Log In" in r.text and "My Account" not in r.text, r.text[:200])

# CSRF: posting login without token must not authenticate
nocsrf = requests.Session()
r = post(nocsrf, "login", {"email": "customer@example.com", "password": "Customer@123"})
check("login without CSRF rejected", "expired" in r.text.lower() and "Welcome back" not in r.text, r.text[:200])
r = get(nocsrf, "account")
check("no session after CSRF-less login", "Log In" in r.text and "My Account" not in r.text, r.text[:200])

# CSRF: cart add without token
r = post(anon, "cart/add", {"product_id": "1", "qty": "1"})
r = get(anon, "cart")
check("cart add without CSRF rejected", "cart/update" not in r.text, r.text[:200])

# admin: delete a contact message
adm = requests.Session()
r = login(adm, ADMIN_EMAIL, ADMIN_PASSWORD)
check("admin login", "Admin Dashboard" in r.text, r.text[:200])
r = get(adm, "admin/messages")
ids = re.findall(r'name="id" value="(\d+)"', r.text)
if ids:
    t = csrf(r.text)
    before = len(ids)
    r = post(adm, "admin/message/delete", {"_token": t, "id": ids[0]})
    r = get(adm, "admin/messages")
    after = re.findall(r'name="id" value="(\d+)"', r.text)
    check("admin deletes message", ids[0] not in after, (before, after[:3]))
    # reply? no reply feature expected

# admin coupon edit
r = get(adm, "admin/coupons")
cid = re.search(r'name="id" value="(\d+)"', r.text)
if cid:
    t = csrf(r.text)
    r = post(adm, "admin/coupon/save", {"_token": t, "id": cid.group(1), "code": "TEST20",
                                        "type": "percent", "value": "25", "min_subtotal": "50",
                                        "is_active": "1"})
    r = get(adm, "admin/coupons")
    check("admin coupon edit", "25" in r.text, r.text[:200])

# password reset flow
reset = requests.Session()
r = post(reset, "forgot", {"_token": csrf(get(reset, "forgot").text), "email": "customer@example.com"})
link = re.search(r'r=reset&(?:amp;)?token=([a-f0-9]{64})', r.text)
check("forgot password generates reset link", bool(link), r.text[:300])
if link:
    r = get(reset, "reset", token=link.group(1))
    check("reset page accepts token", "new password" in r.text.lower() or "Reset" in r.text, r.text[:200])
    t = csrf(r.text)
    newpw = "ResetPass!2026"
    r = post(reset, "reset", {"_token": t, "token": link.group(1),
                              "password": newpw, "password_confirmation": newpw})
    check("reset post completes", "r=login" in r.url and "Log In" in r.text, r.text[:200])
    j = requests.Session()
    r = login(j, "customer@example.com", newpw)
    check("login with reset password", "Demo Customer" in r.text, r.text[:200])
    # restore original password
    t = csrf(get(j, "account").text)
    post(j, "account/password", {"_token": t, "current_password": newpw,
                                 "password": "Customer@123", "password_confirmation": "Customer@123"})

# order isolation: a brand-new customer must not read an existing order by number
newbie = requests.Session()
t = csrf(get(newbie, "register").text)
email = f"iso{int(time.time())}@example.com"
r = post(newbie, "register", {"_token": t, "name": "Isolation User", "email": email,
                              "password": "isopass123", "password_confirmation": "isopass123",
                              "terms": "1"})
check("isolation user registered", "Isolation User" in r.text, r.text[:200])
r = get(adm, "admin/orders")
num = re.search(r'(RS-\d{6}-[A-F0-9]{6})', r.text)
if num:
    r = get(newbie, "account/order", number=num.group(1))
    check("customer cannot read other customer order", "Order not found" in r.text or r.status_code == 404, r.text[:200])
    r = get(newbie, "account/orders")
    check("new customer order list empty", num.group(1) not in r.text, r.text[:200])
else:
    print("note: no order number on admin list")

print("\n==== SUMMARY ====")
print(f"passed: {len(PASSES)}  failed: {len(FAILS)}")
if FAILS:
    print("FAILED:", FAILS)
sys.exit(1 if FAILS else 0)
