/**
 * ClexoMart E-commerce Database Schema
 * 
 * This SQL script creates the complete database structure for the ClexoMart
 * e-commerce platform with IoT inventory management capabilities.
 * 
 * Database Features:
 * - User management with role-based access (Customer, Trader, Admin)
 * - Multi-vendor shop system with product catalog
 * - Shopping cart and order processing
 * - RFID-based inventory tracking
 * - Pickup slot reservation system
 * - Review and wishlist functionality
 * - Payment processing integration
 * 
 * Key Design Principles:
 * - Referential integrity with foreign key constraints
 * - Custom ID generation using Oracle sequences and triggers
 * - Support for BLOB data (images) with metadata
 * - Flexible discount and coupon system
 * - Audit trail with timestamp fields
 * 
 * Table Categories:
 * 1. Core Tables: USER1, SHOP, PRODUCT, CATEGORY
 * 2. Commerce Tables: CART, ORDER1, PAYMENT, COUPON
 * 3. IoT Tables: RFID_READ, RFID_PRODUCT
 * 4. Junction Tables: CART_PRODUCT, WISHLIST_PRODUCT, etc.
 * 5. System Tables: TRADER_PENDING_VERIFICATION, ORDER_STATUS
 * 
 * @author ClexoMart Development Team
 * @version 1.0
 * @database Oracle 19c+
 */

-- ============================================================================
-- DATABASE CLEANUP
-- Drop all existing tables to ensure clean installation
-- CASCADE CONSTRAINTS removes all foreign key dependencies
-- ============================================================================

DROP TABLE USER1 CASCADE CONSTRAINTS;                    -- Main user accounts table
DROP TABLE SHOP CASCADE CONSTRAINTS;                     -- Trader shop information
DROP TABLE REPORT CASCADE CONSTRAINTS;                   -- User reports and feedback
DROP TABLE CART CASCADE CONSTRAINTS;                     -- Shopping cart instances
DROP TABLE PAYMENT CASCADE CONSTRAINTS;                  -- Payment transaction records
DROP TABLE ORDER1 CASCADE CONSTRAINTS;                   -- Customer orders (Oracle reserved word: ORDER)
DROP TABLE COUPON CASCADE CONSTRAINTS;                   -- Discount coupons
DROP TABLE COLLECTION_SLOT CASCADE CONSTRAINTS;          -- Pickup time slot management
DROP TABLE ORDER_REPORT CASCADE CONSTRAINTS;             -- Order-report junction table
DROP TABLE REVIEW CASCADE CONSTRAINTS;                   -- Product reviews
DROP TABLE WISHLIST CASCADE CONSTRAINTS;                 -- Customer wishlists
DROP TABLE PRODUCT CASCADE CONSTRAINTS;                  -- Product catalog
DROP TABLE DISCOUNT CASCADE CONSTRAINTS;                 -- Discount schemes
DROP TABLE CATEGORY CASCADE CONSTRAINTS;                 -- Product categories
DROP TABLE WISHLIST_PRODUCT CASCADE CONSTRAINTS;         -- Wishlist items junction
DROP TABLE CART_PRODUCT CASCADE CONSTRAINTS;             -- Cart items junction
DROP TABLE PRODUCT_ORDER CASCADE CONSTRAINTS;            -- Order items junction (legacy)
DROP TABLE RFID_READ CASCADE CONSTRAINTS;                -- RFID scan log
DROP TABLE TRADER_PENDING_VERIFICATION CASCADE CONSTRAINTS; -- Trader application queue
DROP TABLE RFID_PRODUCT CASCADE CONSTRAINTS;             -- RFID tag assignments
DROP TABLE ORDER_ITEM CASCADE CONSTRAINTS;               -- Order line items
DROP TABLE ORDER_STATUS CASCADE CONSTRAINTS;             -- Order status tracking

-- ============================================================================
-- CORE FOUNDATION TABLES
-- Essential tables that form the backbone of the system
-- ============================================================================

/**
 * USER1 Table - Main User Account Management
 * 
 * Central table for all system users including customers, traders, and admins.
 * Note: Named USER1 because USER is a reserved word in Oracle.
 * 
 * Key Features:
 * - Role-based access control via user_type field
 * - Email verification system with OTP support
 * - Admin verification workflow for traders
 * - BLOB storage for profile images with metadata
 * - Audit trail with created/updated timestamps
 * 
 * User Types:
 * - 'customer': End users who purchase products
 * - 'trader': Merchants who sell products through shops
 * - 'admin': System administrators with full access
 * 
 * Verification Workflow:
 * - Customers: Auto-verified upon registration
 * - Traders: Require admin approval (admin_verified = 'Y')
 * - Email verification via OTP system
 */
CREATE TABLE USER1 (
    user_id VARCHAR2(8) PRIMARY KEY,           -- Auto-generated: 'user0001', 'user0002', etc.
    first_name VARCHAR2(255),                  -- User's first name
    last_name VARCHAR2(255),                   -- User's last name
    user_type VARCHAR2(50),                    -- Role: 'customer', 'trader', 'admin'
    email VARCHAR2(255),                       -- Unique email address for login
    user_image BLOB,                           -- Profile picture binary data
    contact_no NUMBER(15),                     -- Phone number for communication
    password VARCHAR2(255),                    -- Hashed password (bcrypt/argon2)
    admin_verified CHAR(1),                    -- Admin approval: 'Y'/'N' (traders only)
    otp NUMBER(7),                            -- One-time password for email verification
    is_verified NUMBER(1),                     -- Email verification status: 1=verified, 0=pending
    otp_expires_at TIMESTAMP,                 -- OTP expiration time
    USER_IMAGE_MIMETYPE VARCHAR2(255),         -- Image file type (image/jpeg, image/png)
    USER_IMAGE_FILENAME VARCHAR2(255),         -- Original filename for download
    USER_IMAGE_LASTUPD DATE,                   -- Last update timestamp for image
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Account creation time
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP   -- Last profile update time
);

CREATE TABLE CATEGORY ( 
    category_id VARCHAR2(8) PRIMARY KEY, 
    category_name VARCHAR2(255), 
    category_description VARCHAR2(255) 
);


CREATE TABLE SHOP ( 
    shop_id VARCHAR2(8) PRIMARY KEY, 
    shop_name VARCHAR2(255),
    shop_discription VARCHAR2(255),
    user_id VARCHAR2(8), 
    logo BLOB,
    SHOP_IMAGE_MIMETYPE VARCHAR2(255),
    SHOP_IMAGE_FILENAME VARCHAR2(255),
    SHOP_IMAGE_LASTUPD DATE,
    CONSTRAINT fk_shop_user FOREIGN KEY (user_id) REFERENCES USER1(user_id)
);

ALTER TABLE SHOP
ADD category_id VARCHAR2(8);

ALTER TABLE SHOP
ADD CONSTRAINT fk_shop_category FOREIGN KEY (category_id) REFERENCES CATEGORY(category_id);



CREATE TABLE REPORT ( 
    report_id VARCHAR2(8) PRIMARY KEY, 
    report_date DATE, 
    report_title VARCHAR2(255), 
    report_body VARCHAR2(255), 
    user_id VARCHAR2(8), 
    CONSTRAINT fk_report_user FOREIGN KEY (user_id) REFERENCES USER1(user_id) 
);

CREATE TABLE CART ( 
    cart_id VARCHAR2(8) PRIMARY KEY, 
    user_id VARCHAR2(8), 
    creation_date DATE,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES USER1(user_id) 
);

CREATE TABLE COUPON ( 
    coupon_id VARCHAR2(8) PRIMARY KEY, 
    coupon_code VARCHAR2(255), 
    start_date DATE, 
    end_date DATE, 
    coupon_amount DECIMAL(8,2)
);



CREATE TABLE DISCOUNT ( 
    discount_id VARCHAR2(8) PRIMARY KEY, 
    discount_amount DECIMAL(8,2), 
    start_date DATE, 
    end_date DATE 
);

CREATE TABLE COLLECTION_SLOT ( 
    slot_id VARCHAR2(8) PRIMARY KEY, 
    day VARCHAR2(15), 
    time TIMESTAMP,
    no_order number
);

-- Create product and order-related tables
CREATE TABLE PRODUCT ( 
    product_id VARCHAR2(8) PRIMARY KEY, 
    product_name VARCHAR2(255), 
    stock INTEGER, 
    shop_id VARCHAR2(8), 
    category_id VARCHAR2(8), 
    description VARCHAR2(255), 
    unit_price DECIMAL(8,2), 
    discount_id VARCHAR2(8), 
    price_after_discount DECIMAL(8,2), 
    PRODUCT_image BLOB, 
    PRODUCT_IMAGE_MIMETYPE VARCHAR2(255), 
    PRODUCT_IMAGE_FILENAME VARCHAR2(255), 
    PRODUCT_IMAGE_LASTUPD DATE,
    CONSTRAINT fk_product_shop FOREIGN KEY (shop_id) REFERENCES SHOP(shop_id), 
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES CATEGORY(category_id), 
    CONSTRAINT fk_product_discount FOREIGN KEY (discount_id) REFERENCES DISCOUNT(discount_id)
);


CREATE TABLE ORDER1 ( 
    order_id VARCHAR2(8) PRIMARY KEY, 
    order_date DATE, 
    coupon_id VARCHAR2(8), 
    cart_id VARCHAR2(8), 
    payment_amount DECIMAL(8,2), 
    slot_id VARCHAR2(8), 
    CONSTRAINT fk_order_coupon FOREIGN KEY (coupon_id) REFERENCES COUPON(coupon_id), 
    CONSTRAINT fk_order_cart FOREIGN KEY (cart_id) REFERENCES CART(cart_id), 
    CONSTRAINT fk_order_collection_slot FOREIGN KEY (slot_id) REFERENCES COLLECTION_SLOT(slot_id) 
);

-- Add user_id column to the ORDER1 table
ALTER TABLE ORDER1
ADD user_id VARCHAR2(8);

-- Add foreign key constraint on user_id referencing USER1 table
ALTER TABLE ORDER1
ADD CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES USER1(user_id);


CREATE TABLE PRODUCT_ORDER ( 
    product_id VARCHAR2(8), 
    order_id VARCHAR2(8), 
    PRIMARY KEY (product_id, order_id), 
    CONSTRAINT fk_product_order_product FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id), 
    CONSTRAINT fk_product_order_order FOREIGN KEY (order_id) REFERENCES ORDER1(order_id) 
);

CREATE TABLE CART_PRODUCT ( 
    cart_id VARCHAR2(8), 
    product_id VARCHAR2(8), 
    product_quantity INTEGER, 
    total_amount DECIMAL(8,2), 
    PRIMARY KEY (cart_id, product_id), 
    CONSTRAINT fk_cart_product_product FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id), 
    CONSTRAINT fk_cart_product_cart FOREIGN KEY (cart_id) REFERENCES CART(cart_id) 
);

CREATE TABLE WISHLIST ( 
    wishlist_id VARCHAR2(8) PRIMARY KEY, 
    user_id VARCHAR2(8), 
    creation_date DATE,
    CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES USER1(user_id) 
);

CREATE TABLE WISHLIST_PRODUCT ( 
    wishlist_id VARCHAR2(8), 
    product_id VARCHAR2(8), 
    product_quantity INTEGER, 
    PRIMARY KEY (wishlist_id, product_id), 
    CONSTRAINT fk_wishlist_product_product FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id), 
    CONSTRAINT fk_wishlist_product_wishlist FOREIGN KEY (wishlist_id) REFERENCES WISHLIST(wishlist_id) 
);

CREATE TABLE REVIEW ( 
    review_id VARCHAR2(8) PRIMARY KEY, 
    product_id VARCHAR2(8), 
    user_id VARCHAR2(8), 
    review_description VARCHAR2(255), 
    review_date DATE, 
    CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES USER1(user_id), 
    CONSTRAINT fk_product_review FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id) 
);

-- Create dependent relationship tables
CREATE TABLE ORDER_REPORT ( 
    order_id VARCHAR2(8), 
    report_id VARCHAR2(8), 
    PRIMARY KEY (order_id, report_id), 
    CONSTRAINT fk_order_report_order FOREIGN KEY (order_id) REFERENCES ORDER1(order_id), 
    CONSTRAINT fk_order_report_report FOREIGN KEY (report_id) REFERENCES REPORT(report_id) 
);

CREATE TABLE PAYMENT ( 
    payment_id VARCHAR2(8) PRIMARY KEY, 
    payment_method VARCHAR2(255), 
    payment_date DATE, 
    user_id VARCHAR2(8), 
    order_id VARCHAR2(8), 
    payment_amount DECIMAL(8,2), 
    CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES USER1(user_id), 
    CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES ORDER1(order_id) 
);

-- required table 

CREATE TABLE TRADER_PENDING_VERIFICATION (
    user_id VARCHAR2(8) PRIMARY KEY, 
    first_name VARCHAR2(255), 
    last_name VARCHAR2(255),
    user_type VARCHAR2(50), 
    email VARCHAR2(255), 
    user_image BLOB,
    contact_no NUMBER(15),
    password VARCHAR2(255),
    admin_verified CHAR(1),
    otp NUMBER(7),
    is_verified NUMBER(1),
    otp_expires_at TIMESTAMP,
    USER_IMAGE_MIMETYPE VARCHAR2(255),
    USER_IMAGE_FILENAME VARCHAR2(255),
    USER_IMAGE_LASTUPD DATE,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE RFID_READ(
    rfid_id VARCHAR2(8) PRIMARY KEY,
    rfid varchar2(32),
    time TIMESTAMP
);


CREATE TABLE RFID_PRODUCT
(
    rfid VARCHAR2(32)  PRIMARY KEY,          -- tag UID itself is unique
    product_id VARCHAR2(8)   NOT NULL,

    CONSTRAINT fk_rfid_product_product
        FOREIGN KEY (product_id)
        REFERENCES PRODUCT (product_id)
);


CREATE TABLE ORDER_ITEM (
    order_item_id VARCHAR2(10) PRIMARY KEY,
    order_id VARCHAR2(8) NOT NULL,
    product_id VARCHAR2(8) NOT NULL,
    quantity NUMBER(5) DEFAULT 1 NOT NULL,
    unit_price NUMBER(10,2) NOT NULL,
    item_total AS (quantity * unit_price),
    CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES ORDER1(order_id),
    CONSTRAINT fk_order_item_product FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id)
);


CREATE TABLE ORDER_STATUS (
    order_id VARCHAR2(8) PRIMARY KEY,
    status VARCHAR2(20) DEFAULT 'pending' NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT fk_order_status_order FOREIGN KEY (order_id) REFERENCES ORDER1(order_id),
    CONSTRAINT chk_order_status CHECK (status IN ('pending', 'processing', 'completed', 'cancelled'))
);


-- up
INSERT INTO ORDER_ITEM (order_item_id, order_id, product_id, quantity, unit_price)
SELECT 
    'OI' || LPAD(ROWNUM, 6, '0'), 
    po.order_id, 
    po.product_id, 
    1, -- Default quantity
    p.unit_price
FROM PRODUCT_ORDER po
JOIN PRODUCT p ON po.product_id = p.product_id;
