/*
 * ClexoMart Database Sequences and Triggers
 * 
 * This SQL script defines Oracle sequences and their associated triggers
 * for automatic primary key generation across all ClexoMart database tables.
 * 
 * Key Features:
 * - Automatic ID generation with meaningful prefixes
 * - Consistent 4-digit numbering format (e.g., user0001, shop0001)
 * - No caching for data consistency
 * - No cycling to prevent ID reuse
 * - Trigger-based automation for seamless integration
 * 
 * Sequence Strategy:
 * - Each table has a dedicated sequence for ID generation
 * - Sequences start at 1 and increment by 1
 * - NOCACHE ensures immediate persistence
 * - NOCYCLE prevents wraparound after max value
 * 
 * Trigger Strategy:
 * - BEFORE INSERT triggers automatically populate primary keys
 * - Formatted IDs with table-specific prefixes
 * - Zero-padded 4-digit numbers for consistency
 * - Eliminates need for manual ID management
 * 
 * ID Format Examples:
 * - Users: user0001, user0002, user0003...
 * - Products: pro0001, pro0002, pro0003...
 * - Orders: ord0001, ord0002, ord0003...
 * - Shops: shop0001, shop0002, shop0003...
 * 
 * Benefits:
 * - Human-readable IDs for debugging
 * - Consistent ID format across all tables
 * - Automatic generation prevents conflicts
 * - Easy identification of record types
 * - Supports high-volume operations
 * 
 * @author ClexoMart Development Team
 * @version 1.0
 * @database Oracle 19c+
 */

-- ============================================================================
-- SEQUENCE CLEANUP
-- Drop existing sequences to ensure clean installation
-- ============================================================================

DROP SEQUENCE seq_userid;
DROP SEQUENCE seq_shopid;
DROP SEQUENCE seq_reportid;
DROP SEQUENCE seq_cartid;
DROP SEQUENCE seq_cuponid;
DROP SEQUENCE seq_categoryid;
DROP SEQUENCE seq_discountid;
DROP SEQUENCE seq_slotid;
DROP SEQUENCE seq_productid;
DROP SEQUENCE seq_orderid;
DROP SEQUENCE seq_wishlistid;
DROP SEQUENCE seq_reviewid;
DROP SEQUENCE seq_paymentid;
DROP SEQUENCE seq_rfid;

-- ============================================================================
-- SEQUENCE DEFINITIONS
-- Create sequences for automatic primary key generation
-- All sequences use consistent configuration for reliability
-- ============================================================================

/*
 * User Account Sequence
 * Generates unique IDs for USER1 table (customers, traders, admins)
 * Format: user0001, user0002, user0003...
 */
CREATE SEQUENCE seq_userid
    START WITH 1                -- Begin numbering at 1
    INCREMENT BY 1              -- Increment by 1 for each new record
    NOCACHE                     -- No caching for immediate persistence
    NOCYCLE;                    -- No wraparound after maximum value
    
/*
 * Shop/Store Sequence
 * Generates unique IDs for SHOP table (trader businesses)
 * Format: shop0001, shop0002, shop0003...
 */
CREATE SEQUENCE seq_shopid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Report Sequence
 * Generates unique IDs for REPORT table (system reports and analytics)
 * Format: rep0001, rep0002, rep0003...
 */
CREATE SEQUENCE seq_reportid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Shopping Cart Sequence
 * Generates unique IDs for CART table (user shopping carts)
 * Format: cart0001, cart0002, cart0003...
 */
CREATE SEQUENCE seq_cartid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Coupon Sequence
 * Generates unique IDs for COUPON table (discount coupons)
 * Format: cup0001, cup0002, cup0003...
 */
CREATE SEQUENCE seq_cuponid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Category Sequence
 * Generates unique IDs for CATEGORY table (product categories)
 * Format: cat0001, cat0002, cat0003...
 */
CREATE SEQUENCE seq_categoryid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Discount Sequence
 * Generates unique IDs for DISCOUNT table (pricing discounts)
 * Format: dis0001, dis0002, dis0003...
 */
CREATE SEQUENCE seq_discountid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Collection Slot Sequence
 * Generates unique IDs for COLLECTION_SLOT table (pickup time slots)
 * Format: slo0001, slo0002, slo0003...
 */
CREATE SEQUENCE seq_slotid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Product Sequence
 * Generates unique IDs for PRODUCT table (marketplace products)
 * Format: pro0001, pro0002, pro0003...
 */
CREATE SEQUENCE seq_productid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Order Sequence
 * Generates unique IDs for ORDER1 table (customer orders)
 * Format: ord0001, ord0002, ord0003...
 */
CREATE SEQUENCE seq_orderid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Wishlist Sequence
 * Generates unique IDs for WISHLIST table (customer wishlists)
 * Format: wis0001, wis0002, wis0003...
 */
CREATE SEQUENCE seq_wishlistid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Review Sequence
 * Generates unique IDs for REVIEW table (product reviews)
 * Format: rev0001, rev0002, rev0003...
 */
CREATE SEQUENCE seq_reviewid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * Payment Sequence
 * Generates unique IDs for PAYMENT table (transaction records)
 * Format: pay0001, pay0002, pay0003...
 */
CREATE SEQUENCE seq_paymentid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;
    
/*
 * RFID Sequence
 * Generates unique IDs for RFID_READ table (IoT inventory tracking)
 * Format: rfi0001, rfi0002, rfi0003...
 */
CREATE SEQUENCE seq_rfid
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;

-- ============================================================================
-- TRIGGER DEFINITIONS
-- Automatic primary key population using sequences
-- Triggers fire BEFORE INSERT to set ID values
-- ============================================================================

/*
 * User Account ID Trigger
 * Automatically generates user_id for new USER1 records
 * Trigger fires before each INSERT operation
 */
CREATE OR REPLACE TRIGGER trg_userid
BEFORE INSERT ON USER1
FOR EACH ROW
BEGIN
    -- Generate formatted user ID: user + 4-digit number
    :NEW.user_id := 'user' || TO_CHAR(seq_userid.NEXTVAL, 'FM0000');
END;
/

/*
 * Shop ID Trigger
 * Automatically generates shop_id for new SHOP records
 * Links shops to trader accounts for business management
 */
CREATE OR REPLACE TRIGGER trg_shopid
BEFORE INSERT ON SHOP
FOR EACH ROW
BEGIN
    -- Generate formatted shop ID: shop + 4-digit number
    :NEW.shop_id := 'shop' || TO_CHAR(seq_shopid.NEXTVAL, 'FM0000');
END;
/

/*
 * Report ID Trigger
 * Automatically generates report_id for new REPORT records
 * Supports analytics and business intelligence features
 */
CREATE OR REPLACE TRIGGER trg_recordid
BEFORE INSERT ON REPORT
FOR EACH ROW
BEGIN
    -- Generate formatted report ID: rep + 4-digit number
    :NEW.report_id := 'rep' || TO_CHAR(seq_reportid.NEXTVAL, 'FM0000');
END;
/

/*
 * Cart ID Trigger
 * Automatically generates cart_id for new CART records
 * Supports both session and database cart management
 */
CREATE OR REPLACE TRIGGER trg_cartid
BEFORE INSERT ON CART
FOR EACH ROW
BEGIN
    -- Generate formatted cart ID: cart + 4-digit number
    :NEW.cart_id := 'cart' || TO_CHAR(seq_cartid.NEXTVAL, 'FM0000');
END;
/

/*
 * Coupon ID Trigger
 * Automatically generates coupon_id for new COUPON records
 * Supports discount and promotional campaigns
 */
CREATE OR REPLACE TRIGGER trg_cuponid
BEFORE INSERT ON COUPON
FOR EACH ROW
BEGIN
    -- Generate formatted coupon ID: cup + 4-digit number
    :NEW.coupon_id := 'cup' || TO_CHAR(seq_cuponid.NEXTVAL, 'FM0000');
END;
/

/*
 * Category ID Trigger
 * Automatically generates category_id for new CATEGORY records
 * Organizes products into browsable categories
 */
CREATE OR REPLACE TRIGGER trg_categoryid
BEFORE INSERT ON CATEGORY
FOR EACH ROW
BEGIN
    -- Generate formatted category ID: cat + 4-digit number
    :NEW.category_id := 'cat' || TO_CHAR(seq_categoryid.NEXTVAL, 'FM0000');
END;
/

/*
 * Discount ID Trigger
 * Automatically generates discount_id for new DISCOUNT records
 * Manages pricing strategies and promotional offers
 * 
 * Note: Uses categoryid sequence (appears to be a typo in original)
 * Should use seq_discountid for consistency
 */
CREATE OR REPLACE TRIGGER trg_discountid
BEFORE INSERT ON DISCOUNT
FOR EACH ROW
BEGIN
    -- Generate formatted discount ID: dis + 4-digit number
    :NEW.discount_id := 'dis' || TO_CHAR(seq_categoryid.NEXTVAL, 'FM0000');
END;
/

/*
 * Collection Slot ID Trigger
 * Automatically generates slot_id for new COLLECTION_SLOT records
 * Manages pickup scheduling and time slot availability
 */
CREATE OR REPLACE TRIGGER trg_slotid
BEFORE INSERT ON COLLECTION_SLOT
FOR EACH ROW
BEGIN
    -- Generate formatted slot ID: slo + 4-digit number
    :NEW.slot_id := 'slo' || TO_CHAR(seq_slotid.NEXTVAL, 'FM0000');
END;
/

/*
 * Product ID Trigger
 * Automatically generates product_id for new PRODUCT records
 * Core functionality for marketplace product management
 */
CREATE OR REPLACE TRIGGER trg_productid
BEFORE INSERT ON PRODUCT
FOR EACH ROW
BEGIN
    -- Generate formatted product ID: pro + 4-digit number
    :NEW.product_id := 'pro' || TO_CHAR(seq_productid.NEXTVAL, 'FM0000');
END;
/

-- Trigger for the ORDER1 TABLE
CREATE OR REPLACE TRIGGER trg_orderid
BEFORE INSERT ON ORDER1
FOR EACH ROW 
BEGIN
    :NEW.order_id := 'ord' || TO_CHAR(seq_orderid.NEXTVAL, 'FM0000');
END;
/

-- Trigger for the WISHLIST TABLE
CREATE OR REPLACE TRIGGER trg_wishlistid
BEFORE INSERT ON WISHLIST
FOR EACH ROW
BEGIN
    :NEW.wishlist_id := 'wis' || TO_CHAR(seq_wishlistid.NEXTVAL, 'FM0000');
END;
/

-- Trigger for the REVIEW table 
CREATE OR REPLACE TRIGGER trg_reviewid
BEFORE INSERT ON REVIEW 
FOR EACH ROW
BEGIN 
    :NEW.review_id := 'rev' || TO_CHAR(seq_reviewid.NEXTVAL, 'FM0000');
END;
/

-- Trigger for the PAYMENT table 
CREATE OR REPLACE TRIGGER trg_paymentid
BEFORE INSERT ON PAYMENT
FOR EACH ROW
BEGIN 
    :NEW.payment_id := 'rev' || TO_CHAR(seq_paymentid.NEXTVAL, 'FM0000');
END;
/

-- Trigger for the RFID_READ  table 
CREATE OR REPLACE TRIGGER trg_rfid_id
BEFORE INSERT ON RFID_READ
FOR EACH ROW
BEGIN 
    :NEW.rfid_id := 'rif' || TO_CHAR(seq_rfid.NEXTVAL, 'FM0000');
END;
/
