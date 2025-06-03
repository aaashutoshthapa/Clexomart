/* Trigger for USER1 table*/


-- Trigger fot the setting the verifivation table
CREATE OR REPLACE TRIGGER trg_set_verified
BEFORE INSERT ON USER1
FOR EACH ROW
BEGIN
    -- Check if the user type is 'customer'
    IF LOWER(:NEW.user_type) = 'customer' THEN
        :NEW.admin_verified := 'Y';  -- Set verified to 'Y' for customers
    ELSIF LOWER(:NEW.user_type) = 'trader' THEN
        :NEW.admin_verified := 'N';  -- Set verified to 'N' for traders
    END IF;
END;
/


-- Insert into the TRADER_PENDING_VERIFICATION table when criteria meet
CREATE OR REPLACE TRIGGER trg_copy_trader_unverified
AFTER INSERT ON USER1
FOR EACH ROW
BEGIN
    IF LOWER(:NEW.user_type) = 'trader' THEN
        INSERT INTO TRADER_PENDING_VERIFICATION (
            user_id, first_name, last_name, user_type, email, user_image,
            contact_no, password, admin_verified, otp, is_verified, otp_expires_at,
            USER_IMAGE_MIMETYPE, USER_IMAGE_FILENAME, USER_IMAGE_LASTUPD,
            CREATED_AT, UPDATED_AT
        ) VALUES (
            :NEW.user_id, :NEW.first_name, :NEW.last_name, :NEW.user_type, :NEW.email, :NEW.user_image,
            :NEW.contact_no, :NEW.password, :NEW.admin_verified, :NEW.otp, :NEW.is_verified, :NEW.otp_expires_at,
            :NEW.USER_IMAGE_MIMETYPE, :NEW.USER_IMAGE_FILENAME, :NEW.USER_IMAGE_LASTUPD,
            :NEW.CREATED_AT, :NEW.UPDATED_AT
        );
    END IF;
END;
/


-- UPDATE THE TABLE user1 after the trader is verified by admin
CREATE OR REPLACE TRIGGER trg_trader_verified_cleanup
FOR UPDATE OF admin_verified ON TRADER_PENDING_VERIFICATION
COMPOUND TRIGGER

    TYPE user_id_table IS TABLE OF TRADER_PENDING_VERIFICATION.user_id%TYPE;
    g_user_ids user_id_table := user_id_table();

AFTER EACH ROW IS
BEGIN
    IF LOWER(:NEW.admin_verified) = 'y' AND LOWER(:OLD.admin_verified) != 'y' THEN
        UPDATE USER1
        SET 
            first_name = :NEW.first_name,
            last_name = :NEW.last_name,
            user_type = :NEW.user_type,
            email = :NEW.email,
            user_image = :NEW.user_image,
            contact_no = :NEW.contact_no,
            password = :NEW.password,
            admin_verified = :NEW.admin_verified,
            otp = :NEW.otp,
            is_verified = :NEW.is_verified,
            otp_expires_at = :NEW.otp_expires_at,
            USER_IMAGE_MIMETYPE = :NEW.USER_IMAGE_MIMETYPE,
            USER_IMAGE_FILENAME = :NEW.USER_IMAGE_FILENAME,
            USER_IMAGE_LASTUPD = :NEW.USER_IMAGE_LASTUPD,
            UPDATED_AT = SYSTIMESTAMP
        WHERE user_id = :NEW.user_id;

        g_user_ids.EXTEND;
        g_user_ids(g_user_ids.LAST) := :NEW.user_id;
    END IF;
END AFTER EACH ROW;

AFTER STATEMENT IS
BEGIN
    FOR i IN 1 .. g_user_ids.COUNT LOOP
        DELETE FROM TRADER_PENDING_VERIFICATION
        WHERE user_id = g_user_ids(i);
    END LOOP;
END AFTER STATEMENT;

END trg_trader_verified_cleanup;
/


   
/* Trigger for SHOP table*/

-- Trigger to make sure that there are no shop with same name 
CREATE OR REPLACE TRIGGER trg_shopname
BEFORE INSERT OR UPDATE ON SHOP
FOR EACH ROW 
DECLARE 
    v_shop_name_count NUMBER;
BEGIN
    IF :NEW.shop_name <> :OLD.shop_name OR :OLD.shop_name IS NULL THEN
        SELECT COUNT(*) INTO v_shop_name_count
        FROM SHOP
        WHERE shop_name = :NEW.shop_name;
        
        IF v_shop_name_count > 0 THEN
            RAISE_APPLICATION_ERROR(-20006, 'Shop name already exist.');
        END IF;
    END IF;
END;
/

-- Trigger to make sure that single trader can have 2 shopp and only terader cah have shop 
CREATE OR REPLACE TRIGGER trg_trader
BEFORE INSERT OR UPDATE ON SHOP 
FOR EACH ROW 
DECLARE 
    v_user_type VARCHAR2(50);
    v_shop_count NUMBER;
BEGIN
    -- Gets the user_type from the user1 table and stores onto v_user_type
    SELECT user_type INTO v_user_type
    FROM USER1
    WHERE user_id = :NEW.user_id;
    
    IF UPPER(v_user_type) != 'TRADER' THEN
        RAISE_APPLICATION_ERROR(-20007, 'Not a trader. Must open trader account.');
    END IF;
    
    -- Count the number of shops that one trader has
    SELECT COUNT(*) INTO v_shop_count
    FROM SHOP
    WHERE user_id = :NEW.user_id;
    
    IF v_shop_count >= 2 THEN
        RAISE_APPLICATION_ERROR (-20008, 'A trader can have maxmium of 2 shop.');
    END IF;
END;
/

/* Trigger for REPORT table*/



/* Trigger for CART table*/

-- Trigger for creating the cart automatically when the customer is created 
CREATE OR REPLACE TRIGGER trg_cart_creation
AFTER INSERT ON USER1
FOR EACH ROW 
BEGIN 
    IF LOWER(:NEW.user_type) = 'customer' THEN
        INSERT INTO CART(cart_id, user_id, creation_date)
        VALUES ('cart' || TO_CHAR(seq_cartid.NEXTVAL, 'FM0000'), :NEW.user_id, SYSDATE );
    END IF;
END;
/


/* Trigger for COUPON table*/

-- Trigger to make sure that all coupoun code are unique
CREATE OR REPLACE TRIGGER trg_unique_code
BEFORE INSERT OR UPDATE ON COUPON
FOR EACH ROW
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM COUPON
    WHERE coupon_code = :NEW.coupon_code
      AND coupon_id != :NEW.coupon_id;

    IF v_count > 0 THEN
        RAISE_APPLICATION_ERROR(-20013, 'Coupon already exists.');
    END IF;
END;
/



/* Trigger for CATEGORY table*/


/* Trigger for DISCOUNT table*/


/* Trigger for COLLECTION_SLOT table*/


/* Trigger for PRODUCT table*/

-- Trigger for the stock quantity
CREATE OR REPLACE TRIGGER trg_stock
BEFORE INSERT OR UPDATE ON PRODUCT
FOR EACH ROW
BEGIN 
     IF :NEW.stock < 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'Stock cannot be negative.');
    END IF;
END;
/


-- Trigger for the calculating the discount amount of hte product.
drop trigger trg_discount ;
/*
CREATE OR REPLACE TRIGGER trg_discount 
BEFORE INSERT OR UPDATE ON PRODUCT 
FOR EACH ROW 
DECLARE  
    -- Declare variable and giving them datatype of the table. attribute datatype
    v_discount_amount DISCOUNT.discount_amount%TYPE;     
    v_start_date DISCOUNT.start_date%TYPE;     
    v_end_date DISCOUNT.end_date%TYPE; 
BEGIN  
    IF :NEW.discount_id IS NOT NULL THEN  
        -- Retrieve discount details and storing them in the decleared variable.
        SELECT discount_amount, start_date, end_date  
        INTO v_discount_amount, v_start_date, v_end_date  
        FROM DISCOUNT  
        WHERE discount_id = :NEW.discount_id;  
  
        -- Apply discount if SYSDATE is within the discount period
        IF SYSDATE BETWEEN v_start_date AND v_end_date THEN  
            :NEW.price_after_discount := :NEW.unit_price - v_discount_amount;  
        ELSE  
            :NEW.price_after_discount := :NEW.unit_price;  
        END IF;  
    ELSE  
        :NEW.price_after_discount := :NEW.unit_price;  
    END IF;  
END;
/
*/

/* Trigger for ORDER1 table*/

-- Trigger for automatically when order is placed 
CREATE OR REPLACE TRIGGER trg_order_date
BEFORE INSERT OR UPDATE ON ORDER1
FOR EACH ROW

BEGIN 
    :NEW.order_date := SYSDATE;
END;
/

/* Trigger for PRODUCT_ORDER table*/  
CREATE OR REPLACE TRIGGER trg_insert_product_order
AFTER INSERT ON ORDER1
FOR EACH ROW
BEGIN
    INSERT INTO PRODUCT_ORDER (product_id, order_id)
    SELECT cp.product_id, :NEW.order_id
    FROM CART_PRODUCT cp
    WHERE cp.cart_id = :NEW.cart_id;
END;
/



/* Trigger for CART_PRODUCT table*/  

-- This creates trigger so that there will not be more thatn 20 product in the cart
Drop trigger trg_limit_products_per_cart;
/*
CREATE OR REPLACE TRIGGER trg_limit_products_per_cart
BEFORE INSERT OR UPDATE ON CART_PRODUCT
FOR EACH ROW
DECLARE
    total_products NUMBER;
BEGIN
    -- Count current total quantity of products in the cart, excluding the current one (for updates)
    SELECT NVL(SUM(product_quantity), 0)
    INTO total_products
    FROM CART_PRODUCT
    WHERE cart_id = :NEW.cart_id
    AND (:NEW.product_id IS NULL OR product_id != :NEW.product_id); -- avoid double count on update

    
    total_products := total_products + :NEW.product_quantity;  -- Add the new/updated quantity

    IF total_products > 20 THEN
        RAISE_APPLICATION_ERROR(-20011, 'A cart cannot contain more than 20 products in total.');
    END IF;
END;
/

*/

/* Trigger for WISHLIST table*/   
CREATE OR REPLACE TRIGGER trg_wishlist_creation
AFTER INSERT ON USER1
FOR EACH ROW 
BEGIN 
    IF LOWER(:NEW.user_type) = 'customer' THEN
        INSERT INTO WISHLIST (wishlist_id, user_id, creation_date)
        VALUES ('wis' || TO_CHAR(seq_wishlistid.NEXTVAL, 'FM0000'), :NEW.user_id, SYSDATE );
    END IF;
END;
/
 

/* Trigger for ORDER_REPORT table*/  
Drop trigger trg_order_report_insertion;

/*
CREATE OR REPLACE TRIGGER trg_order_report_insertion
AFTER INSERT ON ORDER1
FOR EACH ROW
DECLARE
    v_report_id VARCHAR2(8);
BEGIN
    -- Find the corresponding report_id where user_id matches
    SELECT report_id 
    INTO v_report_id
    FROM REPORT 
    WHERE user_id = :NEW.user_id;

    -- Insert into ORDER_REPORT if user_id matches
    IF v_report_id IS NOT NULL THEN
        INSERT INTO ORDER_REPORT (order_id, report_id)
        VALUES (:NEW.order_id, v_report_id);
    END IF;
END;
/
*/


   
/* Trigger for RFID_PRODUCT table */    
CREATE OR REPLACE TRIGGER trg_rfid_read_after
AFTER INSERT ON RFID_READ
FOR EACH ROW
DECLARE
    v_product_id PRODUCT.product_id%TYPE;
BEGIN
    ------------------------------------------------------------------
    -- 1. Find which product (if any) is associated with the UID
    ------------------------------------------------------------------
    BEGIN
        SELECT product_id
        INTO   v_product_id
        FROM   RFID_PRODUCT
        WHERE  rfid = :NEW.rfid;

    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            -- Unknown tag: do nothing, but you could log it if you wish
            RETURN;
    END;

    ------------------------------------------------------------------
    -- 2. Increment the stock atomically
    ------------------------------------------------------------------
    UPDATE PRODUCT
    SET    stock = NVL(stock,0) + 1
    WHERE  product_id = v_product_id;

END;
/




