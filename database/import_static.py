"""Extract catalogue data from the mirrored static pages into database/data/*.json.

Run from the project root:  python database/import_static.py
"""
import html
import json
import os
import re
import shutil
from collections import OrderedDict

PROJECT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ROOT = os.path.join(PROJECT, "legacy") if os.path.isdir(os.path.join(PROJECT, "legacy")) else PROJECT
DATA = os.path.join(PROJECT, "database", "data")
os.makedirs(DATA, exist_ok=True)

CATEGORY_PAGES = OrderedDict([
    ("best-deals", "Best Deals"),
    ("bulk-discounts", "Bulk Discounts"),
    ("peptides-sarms", "Peptides and SARMS"),
    ("oral-steroids", "Oral Steroids"),
    ("injectable-steroids", "Injectable Steroids"),
    ("pct", "PCT"),
    ("hgh-and-insulin", "HGH and Insulin"),
    ("mens-health", "Men's Health"),
    ("weight-loss", "Weight Loss"),
    ("skin-care", "Skin Care"),
    ("general-health", "General Health Products"),
])
CATEGORY_ALIASES = {"best-deals-copy": "best-deals", "peptide-hubs": "peptides-sarms"}

BRAND_PAGES = OrderedDict([
    ("kalpa-pharmaceuticals", "Kalpa Pharmaceuticals"),
    ("dragon-pharma", "Dragon Pharma"),
    ("axiolabs", "Axiolabs"),
    ("british-dragon", "British Dragon"),
    ("stealth-labs", "Stealth Labs"),
    ("gen-shi-labs", "Gen-Shi Labs"),
    ("generic-asia", "Generic Asia"),
])

GOAL_PAGES = OrderedDict([
    ("bulking-supplements", "Bulking"),
    ("cutting-supplements", "Cutting"),
    ("recomping-supplements", "Recomping"),
    ("strength-supplements", "Strength"),
    ("recovery-supplements", "Recovery"),
    ("aesthetics-supplements", "Aesthetics"),
])

SHIPPING_PAGES = {"usa-domestic": "usa", "international-shipping": "international"}

LISTING_PAGES = list(CATEGORY_PAGES) + list(CATEGORY_ALIASES) + list(BRAND_PAGES) + \
    list(GOAL_PAGES) + list(SHIPPING_PAGES)


def read(name):
    with open(os.path.join(ROOT, name), encoding="utf-8", errors="ignore") as fh:
        return fh.read()


def clean(text):
    text = re.sub(r"<[^>]+>", " ", text or "")
    text = html.unescape(text)
    return re.sub(r"\s+", " ", text).strip()


def slugify(text):
    text = re.sub(r"[^a-z0-9]+", "-", (text or "").lower()).strip("-")
    return text or "item"


products = {}
page_members = {}


for page in LISTING_PAGES:
    path = os.path.join(ROOT, page + ".html")
    if not os.path.exists(path):
        continue
    src = read(page + ".html")
    starts = [m.start() for m in re.finditer(r'<div class="items-prod ', src)]
    members = []
    for idx, start in enumerate(starts):
        end = starts[idx + 1] if idx + 1 < len(starts) else len(src)
        block = src[start:end]

        offer = re.search(r'data-offer="(\d+)"', block)
        pid = int(offer.group(1)) if offer else None
        if pid is None:
            m = re.search(r'id="add-to-cart-(\d+)"', block)
            pid = int(m.group(1)) if m else None
        if pid is None:
            continue

        title = clean(re.search(r'class="title-main-link"[^>]*>(.*?)</a>', block, re.S).group(1))
        img = re.search(r'src="\./assets/products/([^"]+)"', block)
        image = "assets/products/" + img.group(1) if img else ""

        price = re.search(r'class="box-price[^"]*"[^>]*data-value="([\d.]+)"', block)
        price = float(price.group(1)) if price else 0.0
        old = re.search(r'class="box-old-price"[^>]*>(.*?)</small>', block, re.S)
        old_price = None
        if old:
            digits = re.sub(r"[^\d.]", "", clean(old.group(1)))
            if digits:
                old_price = float(digits)

        manufacturer = clean(re.search(r'data-manufacture="([^"]*)"', block).group(1)) \
            if re.search(r'data-manufacture="([^"]*)"', block) else ""
        substance = clean(re.search(r'data-substance="([^"]*)"', block).group(1)) \
            if re.search(r'data-substance="([^"]*)"', block) else ""
        sku = re.search(r'data-sku="([^"]*)"', block)
        sku = html.unescape(sku.group(1)) if sku else "RS-%d" % pid

        short = re.search(r'<div class="short-text">(.*?)</div>', block, re.S)
        short_html = short.group(1).strip() if short else ""
        short_text = clean(short_html)

        pharma = ""
        m = re.search(r"(?:Pharmaceutical name|Substance)\s*:?\s*([^:]+?)\s*(?:Pack|$)", short_text, re.I)
        if m:
            pharma = clean(m.group(1))

        pack = strength = ""
        m = re.search(r"Pack\s*:?\s*([^()]+?)\s*\(([^)]+)\)", short_text, re.I)
        if m:
            pack = clean(m.group(1))
            strength = clean(m.group(2))
        else:
            m = re.search(r"Pack\s*:?\s*(.+)$", short_text, re.I)
            if m:
                pack = clean(m.group(1))

        ribbon = ""
        m = re.search(r'class="ribbon"[^>]*>\s*<span>(.*?)</span>', block, re.S)
        if m:
            ribbon = clean(m.group(1))

        stock = True
        m = re.search(r'data-variations="([^"]*)"', block)
        if m:
            try:
                variations = json.loads(html.unescape(m.group(1)))
                if variations:
                    stock = any(bool(v.get("stock", True)) for v in variations)
            except Exception:
                pass

        location = "both"
        m = re.search(r'data-props="([^"]*)"', block)
        if m:
            props = html.unescape(m.group(1))
            if "location_usa" in props and "location_int" not in props:
                location = "usa"
            elif "location_int" in props and "location_usa" not in props:
                location = "international"

        brand_name = manufacturer.split(",")[0].strip()
        entry = products.get(pid)
        if entry is None:
            entry = products[pid] = {
                "id": pid,
                "name": title,
                "slug": re.sub(r"--s\d+\.webp$", "", img.group(1)) if img else "%s-%d" % (slugify(title), pid),
                "sku": sku,
                "price": price,
                "old_price": old_price,
                "image": image,
                "brand": brand_name,
                "substance": substance,
                "pharmaceutical_name": pharma,
                "pack": pack,
                "strength": strength,
                "short_description": short_text,
                "ribbon": ribbon,
                "stock": 50 if stock else 0,
                "shipping_location": location,
                "categories": [],
                "goals": [],
            }
        entry["categories"] = sorted(set(entry["categories"]))
        entry["goals"] = sorted(set(entry["goals"]))
        members.append(pid)

        if page in CATEGORY_ALIASES:
            entry["categories"].append(CATEGORY_ALIASES[page])
        elif page in CATEGORY_PAGES:
            entry["categories"].append(page)
        if page in GOAL_PAGES:
            entry["goals"].append(page)

    page_members[page] = sorted(set(members))
    print(f"{page}: {len(members)} cards, {len(set(members))} unique")

for p in products.values():
    p["categories"] = sorted(set(p["categories"]))
    p["goals"] = sorted(set(p["goals"]))
    if not p["categories"]:
        p["categories"] = ["general-health"]

print("unique products:", len(products))

# ---------- articles ----------
def parse_news(page):
    src = read(page + ".html")
    items = []
    for block in re.findall(r'<div class="item col-md-6">(.*?)</div>\s*</div>', src, re.S):
        img = re.search(r'<img src="\./([^"]+)"', block)
        date = re.search(r'class="date">([^<]*)<', block)
        title = re.search(r'class="title">(.*?)</strong>', block, re.S)
        excerpt = re.search(r"<p>(.*?)</p>", block, re.S)
        if not title:
            continue
        t = clean(title.group(1))
        items.append({
            "title": t,
            "slug": slugify(t),
            "image": img.group(1) if img else "",
            "published_at": clean(date.group(1)) if date else "",
            "excerpt": clean(excerpt.group(1)) if excerpt else "",
            "body": clean(excerpt.group(1)) if excerpt else "",
        })
    return items


articles = parse_news("articles")
events = parse_news("events")
print("articles:", len(articles), "events:", len(events))

# copy news images into assets/images for a stable path
os.makedirs(os.path.join(PROJECT, "assets", "images"), exist_ok=True)
for item in articles + events:
    if item["image"]:
        src = os.path.join(ROOT, item["image"])
        if os.path.exists(src):
            name = os.path.basename(item["image"])
            shutil.copyfile(src, os.path.join(PROJECT, "assets", "images", name))
            item["image"] = "assets/images/" + name

# ---------- faqs ----------
faq_src = read("faq.html")
faqs = []
for m in re.finditer(r'<a name="(\d+)" id="\d+"></a>(.*?)</li>', faq_src, re.S):
    block = m.group(2)
    strong = re.search(r"<strong>(.*?)</strong>", block, re.S)
    if not strong:
        continue
    question = clean(strong.group(1))
    question = re.sub(r"^\d+\.\s*", "", question)
    answer_html = re.sub(r"<strong>.*?</strong>", "", block, count=1, flags=re.S)
    answer = clean(answer_html)
    if question and answer:
        faqs.append({"question": question, "answer": answer})
print("faq source length:", len(faq_src), "parsed:", len(faqs))
print("faqs:", len(faqs))

with open(os.path.join(DATA, "products.json"), "w", encoding="utf-8") as fh:
    json.dump(list(products.values()), fh, indent=1, ensure_ascii=False)
with open(os.path.join(DATA, "news.json"), "w", encoding="utf-8") as fh:
    json.dump({"articles": articles, "events": events}, fh, indent=1, ensure_ascii=False)
with open(os.path.join(DATA, "faqs.json"), "w", encoding="utf-8") as fh:
    json.dump(faqs, fh, indent=1, ensure_ascii=False)
with open(os.path.join(DATA, "taxonomy.json"), "w", encoding="utf-8") as fh:
    json.dump({
        "categories": [{"slug": s, "name": n} for s, n in CATEGORY_PAGES.items()],
        "brands": [{"slug": s, "name": n} for s, n in BRAND_PAGES.items()],
        "goals": [{"slug": s, "name": n} for s, n in GOAL_PAGES.items()],
    }, fh, indent=1, ensure_ascii=False)

print("wrote", DATA)
