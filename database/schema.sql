-- RoidsSeek storefront schema (MySQL 8)
-- Import with: mysql -u root -p roidsseek < database/schema.sql
-- or run: php database/seed.php (creates tables and seeds data)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS product_goal;
DROP TABLE IF EXISTS product_category;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS goals;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS faqs;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS articles;
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(40) NULL,
    role            ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE brands (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(120) NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY brands_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(120) NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description TEXT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY categories_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE goals (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(120) NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY goals_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sku                VARCHAR(60) NOT NULL,
    slug               VARCHAR(190) NOT NULL,
    name               VARCHAR(190) NOT NULL,
    brand_id           INT UNSIGNED NULL,
    substance          VARCHAR(120) NULL,
    pharmaceutical_name VARCHAR(190) NULL,
    pack               VARCHAR(120) NULL,
    strength           VARCHAR(120) NULL,
    short_description  TEXT NULL,
    description        MEDIUMTEXT NULL,
    price              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    old_price          DECIMAL(10,2) NULL,
    stock              INT NOT NULL DEFAULT 0,
    image              VARCHAR(255) NULL,
    ribbon             VARCHAR(120) NULL,
    shipping_location  ENUM('usa','international','both') NOT NULL DEFAULT 'both',
    is_featured        TINYINT(1) NOT NULL DEFAULT 0,
    is_active          TINYINT(1) NOT NULL DEFAULT 1,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY products_slug_unique (slug),
    UNIQUE KEY products_sku_unique (sku),
    KEY products_brand_index (brand_id),
    KEY products_active_index (is_active),
    CONSTRAINT products_brand_fk FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_category (
    product_id  INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, category_id),
    CONSTRAINT pc_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT pc_category_fk FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_goal (
    product_id INT UNSIGNED NOT NULL,
    goal_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, goal_id),
    CONSTRAINT pg_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT pg_goal_fk FOREIGN KEY (goal_id) REFERENCES goals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE carts (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL,
    session_id  VARCHAR(64) NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY carts_session_index (session_id),
    CONSTRAINT carts_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cart_items (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cart_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty        INT NOT NULL DEFAULT 1,
    price      DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY cart_items_unique (cart_id, product_id),
    CONSTRAINT ci_cart_fk FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE,
    CONSTRAINT ci_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE coupons (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code          VARCHAR(40) NOT NULL,
    type          ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    value         DECIMAL(10,2) NOT NULL,
    min_subtotal  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    expires_at    DATE NULL,
    usage_limit   INT NULL,
    used_count    INT NOT NULL DEFAULT 0,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY coupons_code_unique (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number     VARCHAR(32) NOT NULL,
    user_id          INT UNSIGNED NULL,
    email            VARCHAR(190) NOT NULL,
    status           ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
    subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping_total   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    coupon_code      VARCHAR(40) NULL,
    payment_method   VARCHAR(40) NOT NULL DEFAULT 'bank_transfer',
    shipping_name    VARCHAR(190) NOT NULL,
    shipping_phone   VARCHAR(40) NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city    VARCHAR(120) NULL,
    shipping_state   VARCHAR(120) NULL,
    shipping_postal  VARCHAR(40) NULL,
    shipping_country VARCHAR(120) NULL,
    notes            TEXT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY orders_number_unique (order_number),
    KEY orders_user_index (user_id),
    CONSTRAINT orders_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id   INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    name       VARCHAR(190) NOT NULL,
    sku        VARCHAR(60) NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    qty        INT NOT NULL,
    total      DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY order_items_order_index (order_id),
    CONSTRAINT oi_order_fk FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT oi_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reviews (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NULL,
    name       VARCHAR(120) NOT NULL,
    rating     TINYINT NOT NULL DEFAULT 5,
    title      VARCHAR(190) NULL,
    body       TEXT NOT NULL,
    status     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY reviews_product_index (product_id, status),
    CONSTRAINT reviews_product_fk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT reviews_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email      VARCHAR(190) NOT NULL,
    token      VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY password_resets_token_index (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE contact_messages (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    subject    VARCHAR(190) NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE newsletter_subscribers (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email      VARCHAR(190) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY newsletter_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE faqs (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    question   VARCHAR(255) NOT NULL,
    answer     TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE articles (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug         VARCHAR(190) NOT NULL,
    title        VARCHAR(190) NOT NULL,
    excerpt      TEXT NULL,
    body         MEDIUMTEXT NULL,
    image        VARCHAR(255) NULL,
    published_at DATE NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY articles_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE events (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug         VARCHAR(190) NOT NULL,
    title        VARCHAR(190) NOT NULL,
    excerpt      TEXT NULL,
    image        VARCHAR(255) NULL,
    published_at DATE NULL,
    PRIMARY KEY (id),
    UNIQUE KEY events_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pages (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug     VARCHAR(120) NOT NULL,
    title    VARCHAR(190) NOT NULL,
    content  MEDIUMTEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY pages_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
