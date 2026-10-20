"""Admin console: direct entry point, content CRUD, user management, access control."""
import os
import re
import subprocess
import time

import requests

from envfile import get

BASE = "http://127.0.0.1:8000/index.php"
ADMIN = "http://127.0.0.1:8000/admin.php"
MYSQL = os.environ.get("MYSQL_BIN", r"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe")
DB_USER = get("DB_USERNAME", "root")
DB_PASS = get("DB_PASSWORD", "")
ADMIN_EMAIL = get("ADMIN_EMAIL", "admin@roidsseek.com")
ADMIN_PASSWORD = get("ADMIN_PASSWORD", "Admin@12345")
FAILS, PASSES = [], []
STAMP = str(int(time.time()))


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
    match = re.search(r'name="_token" value="([^"]+)"', html)
    return match.group(1) if match else ""


def login(session, email, password):
    page = session.get(BASE, params={"r": "login"}, timeout=30)
    return session.post(
        BASE,
        params={"r": "login"},
        data={"_token": csrf(page.text), "email": email, "password": password},
        timeout=30,
        allow_redirects=True,
    )


def admin_get(session, path="", **params):
    return session.get(ADMIN, params=params if path == "" else {"r": path, **params}, timeout=30)


def admin_post(session, route, data, **params):
    return session.post(BASE, params={"r": route, **params}, data=data, timeout=30, allow_redirects=True)


# ---------------------------------------------------------------- guests
guest = requests.Session()
r = guest.get(ADMIN, timeout=30)
check("guest admin.php redirects to login", "r=login" in r.url and "Log In" in r.text and "My Account" not in r.text, r.text[:200])
r = guest.get(BASE, params={"r": "admin/content", "entity": "articles"}, timeout=30)
check("guest content route redirects to login", "r=login" in r.url and "Log In" in r.text and "New article" not in r.text, r.text[:200])

# ---------------------------------------------------------------- admin
admin = requests.Session()
r = login(admin, ADMIN_EMAIL, ADMIN_PASSWORD)
check("admin login", "Admin Dashboard" in r.text, r.text[:200])

r = admin.get(ADMIN, timeout=30)
check("admin.php dashboard", r.status_code == 200 and "Dashboard" in r.text, r.status_code)
for label in ("Catalogue", "Content", "Categories", "Brands", "Goals", "Articles", "Events", "FAQs", "Pages"):
    check("sidebar has " + label, label in r.text)

entities = ["categories", "brands", "goals", "articles", "events", "faqs", "pages"]
for entity in entities:
    r = admin.get(BASE, params={"r": "admin/content", "entity": entity}, timeout=30)
    check("list " + entity, r.status_code == 200 and "New" in r.text, (r.status_code, r.text[:150]))

# ---------------------------------------------------------------- create / edit / delete
cases = [
    (
        "categories",
        {"name": "Test Category " + STAMP, "description": "Created by the admin test.", "sort_order": "7"},
        "name", "Test Category " + STAMP + " updated",
    ),
    (
        "articles",
        {"title": "Test Article " + STAMP, "excerpt": "Test excerpt", "body": "Test body",
         "published_at": "2026-10-20", "is_published": "1"},
        "title", "Test Article " + STAMP + " updated",
    ),
    (
        "faqs",
        {"question": "Test question " + STAMP + "?", "answer": "Test answer body.", "sort_order": "3"},
        "question", "Test question " + STAMP + " updated?",
    ),
    (
        "pages",
        {"title": "Test Page " + STAMP, "content": "<p>Test page content</p>"},
        "title", "Test Page " + STAMP + " updated",
    ),
]

created = {}
for entity, payload, label_column, updated_label in cases:
    page = admin.get(BASE, params={"r": "admin/content/edit", "entity": entity}, timeout=30)
    token = csrf(page.text)
    check("form " + entity, bool(token) and "details" in page.text, page.text[:150])
    r = admin_post(admin, "admin/content/save", {"_token": token, **payload}, entity=entity)
    check("create " + entity, "created" in r.text.lower() or "updated" in r.text.lower(), r.text[:200])

    rows = sql(
        "SELECT id FROM " + entity +
        " WHERE " + label_column + " = '" + payload[label_column].replace("'", "''") + "'"
    )
    check("db row " + entity, len(rows) == 1, rows)
    if not rows:
        continue
    item_id = rows[0][0]
    created[entity] = item_id

    edit = admin.get(BASE, params={"r": "admin/content/edit", "entity": entity, "id": item_id}, timeout=30)
    check("edit form " + entity, item_id in edit.text and "Save changes" in edit.text, edit.text[:150])
    r = admin_post(
        admin, "admin/content/save",
        {"_token": csrf(edit.text), "id": item_id, **payload, label_column: updated_label}, entity=entity,
    )
    check("update " + entity, "updated" in r.text.lower(), r.text[:200])
    rows = sql("SELECT id FROM " + entity + " WHERE id = " + item_id +
               " AND " + label_column + " = '" + updated_label.replace("'", "''") + "'")
    check("db update " + entity, len(rows) == 1, rows)

# delete all created rows
for entity, item_id in created.items():
    page = admin.get(BASE, params={"r": "admin/content", "entity": entity}, timeout=30)
    r = admin_post(admin, "admin/content/delete", {"_token": csrf(page.text), "id": item_id}, entity=entity)
    check("delete " + entity, "deleted" in r.text.lower(), r.text[:200])
    check("db delete " + entity, sql("SELECT id FROM " + entity + " WHERE id = " + item_id) == [])

# CSRF protection on the save endpoint
r = admin_post(admin, "admin/content/save", {"title": "No token " + STAMP}, entity="faqs")
check("save without CSRF rejected", "No token " + STAMP not in r.text and "expired" in r.text.lower(), r.text[:200])

# ---------------------------------------------------------------- users
email = f"admintest{STAMP}@example.com"
signup = requests.Session()
page = signup.get(BASE, params={"r": "register"}, timeout=30)
r = signup.post(BASE, params={"r": "register"}, data={
    "_token": csrf(page.text), "name": "Admin Test", "email": email,
    "password": "Testpass!2026", "password_confirmation": "Testpass!2026", "terms": "1",
}, timeout=30, allow_redirects=True)
check("temp customer registered", "Admin Test" in r.text, r.text[:200])
rows = sql("SELECT id FROM users WHERE email = '" + email + "'")
check("temp customer in db", len(rows) == 1, rows)
user_id = rows[0][0] if rows else "0"

page = admin.get(BASE, params={"r": "admin/users"}, timeout=30)
r = admin_post(admin, "admin/user/role", {"_token": csrf(page.text), "id": user_id, "role": "admin"})
check("promote customer", "Role updated to admin" in r.text, r.text[:200])
check("db role admin", sql("SELECT role FROM users WHERE id = " + user_id) == [["admin"]])

page = admin.get(BASE, params={"r": "admin/users"}, timeout=30)
r = admin_post(admin, "admin/user/role", {"_token": csrf(page.text), "id": user_id, "role": "customer"})
check("demote customer", "Role updated to customer" in r.text, r.text[:200])

page = admin.get(BASE, params={"r": "admin/users"}, timeout=30)
r = admin_post(admin, "admin/user/delete", {"_token": csrf(page.text), "id": user_id})
check("delete customer", "Account deleted" in r.text, r.text[:200])
check("db user deleted", sql("SELECT id FROM users WHERE id = " + user_id) == [])

# ---------------------------------------------------------------- customer denied
customer = requests.Session()
r = login(customer, "customer@example.com", "Customer@123")
r = customer.get(ADMIN, timeout=30)
check("customer blocked from admin.php", "Admin Dashboard" not in r.text and "Dashboard" not in r.text, r.text[:200])
r = customer.get(BASE, params={"r": "admin/content", "entity": "articles"}, timeout=30)
check("customer blocked from content", "New article" not in r.text and "Admin Dashboard" not in r.text, r.text[:200])

print("\n==== SUMMARY ====")
print("passed:", len(PASSES), " failed:", len(FAILS))
if FAILS:
    print("FAILED:", FAILS)
