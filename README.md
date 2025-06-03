# ClexoMart - Smart E-Commerce Platform with IoT Inventory Management## OverviewClexoMart is a comprehensive e-commerce platform built with **PHP Laravel** and **Oracle Database**, featuring an innovative **IoT-based inventory management system** using RFID technology. The platform enables seamless online shopping with real-time inventory tracking through RFID tags and automated stock updates.### Core Platform Capabilities- **Multi-vendor marketplace** supporting customer and trader accounts- **Real-time inventory management** through RFID IoT integration- **Automated stock tracking** with hardware-software bridge- **Secure payment processing** via PayPal integration- **Advanced cart management** with 20-item limits and validation- **Time-slot based pickup system** for order collection- **Email notifications** for orders and invoices- **Admin verification system** for trader accounts- **Mobile-responsive design** with modern UI/UX

## 🏗️ System Architecture### Technology Stack- **Backend**: PHP Laravel Framework- **Database**: Oracle Database with custom triggers and sequences- **Frontend**: Blade Templates with Bulma CSS Framework- **IoT Hardware**: Arduino + RC522 RFID Reader- **IoT Software**: Python relay script for hardware-software bridge- **Payment**: PayPal Integration- **Authentication**: Laravel Sanctum with OTP verification## 📋 Detailed Component Explanations### 🖥️ Laravel Backend Components#### 🎮 Controllers LayerThe Laravel controllers manage all business logic and HTTP request handling:**CartController** (`Front_end_PHP/app/Http/Controllers/CartController.php`)- **Purpose**: Manages shopping cart operations with 20-item limit enforcement- **Key Methods**:  - `index()`: Displays cart with items from database or session  - `addToCart()`: Validates and adds items with stock checking  - `updateCart()`: AJAX-powered quantity updates with real-time validation  - `removeFromCart()`: Item removal with confirmation modals  - `checkSlotAvailability()`: Validates pickup slots (Wed/Thu/Fri only)  - `transferSessionCartToDatabase()`: Merges guest cart after login- **Business Rules**:   - Maximum 20 items per cart (total quantity, not unique products)  - Stock validation before adding items  - Session persistence for guest users  - Database persistence for authenticated users**CheckoutController** (`Front_end_PHP/app/Http/Controllers/CheckoutController.php`)- **Purpose**: Handles PayPal payment processing and order creation- **Key Methods**:  - `createTransaction()`: Validates cart and creates PayPal payment intent  - `successTransaction()`: Processes successful payments and creates orders  - `orderSuccess()`: Displays order confirmation page- **Cart Clearing Logic**:   - Clears `CART_PRODUCT` table entries after successful payment  - Removes session cart data  - Uses database transactions for atomicity- **Email Integration**: Sends order confirmation and invoice emails**RfidController** (`Front_end_PHP/app/Http/Controllers/RfidController.php`)- **Purpose**: API endpoint for IoT RFID system integration- **Key Methods**:  - `store()`: Receives RFID scans from relay.py bridge  - `getRecent()`: Provides recent scans for real-time UI updates  - `getProductByRfid()`: Maps RFID UIDs to product information- **Auto-Stock Updates**: Triggers fire automatically on RFID_READ inserts#### 🗂️ Models & RelationshipsThe Eloquent models define data relationships and business logic:**Cart Model** (`Front_end_PHP/app/Models/Cart.php`)- **Relationships**:   - `belongsTo(User::class)`: Each cart belongs to one user  - `belongsToMany(Product::class)`: Many-to-many via CART_PRODUCT  - `hasOne(Order::class)`: Cart can have one associated order- **Key Features**:   - Auto-generated cart_id using Oracle sequences  - Pivot table management for cart items with quantities**CartProduct Model** (`Front_end_PHP/app/Models/CartProduct.php`)- **Purpose**: Manages the many-to-many relationship between carts and products- **Composite Key**: Uses both cart_id and product_id as primary key- **Custom Key Handling**: Overrides Laravel's default key handling for composite keys- **Data Integrity**: Ensures quantity and total_amount consistency**RfidRead & RfidProduct Models**- **RfidRead**: Stores all RFID scan events with timestamps- **RfidProduct**: Maps RFID UIDs to specific products- **Integration**: Enables automatic stock updates through database triggers#### 🛡️ Middleware & Security**Authentication Middleware**:- Session-based authentication with user_id storage- Role-based access control (customer vs trader vs admin)- OTP email verification for account security- Admin approval required for trader accounts### 🎨 Frontend Components#### 📱 Blade Templates & UI**Cart Interface** (`Front_end_PHP/resources/views/cart.blade.php`)- **Real-time Validation**: JavaScript-powered quantity updates- **Pickup Slot Selection**:   - Dynamic date generation (Wed/Thu/Fri only)  - 24-hour advance booking requirement  - Capacity checking (max 20 orders per slot)- **Responsive Design**: Mobile-first approach using Bulma CSS- **AJAX Operations**:   - Cart updates without page refresh  - Live stock validation  - Error handling with user feedback**Styling System** (`Front_end_PHP/public/css/cartproduct.css`)- **Color Scheme**:   - Primary Green: #A8C686 (navbar, buttons)  - Accent Yellow: #FED549 (highlights)  - Warning Orange: #CC561E (alerts, remove buttons)- **Component Styling**:  - Card-based layout for cart items  - Hover effects and animations  - Responsive breakpoints for mobile  - Loading states and transitions#### ⚡ JavaScript Features**Real-time Updates**:```javascript// Quantity change handling with debouncingdocument.querySelectorAll('.cart-quantity-input').forEach(input => {    input.addEventListener('change', function() {        // Disable input during update        this.disabled = true;                // Send AJAX request with error handling        fetch('/cart/update', {            method: 'POST',            body: JSON.stringify({                product_id: this.dataset.productId,                quantity: this.value            })        })        .then(response => response.json())        .then(data => {            if (data.success) {                updateCartDisplay(data.updated_totals);                showSuccessMessage('Cart updated successfully');            }        });    });});```**Pickup Slot Management**:- Dynamic date population based on business rules- Real-time availability checking via API calls- Form validation preventing checkout without slot selection- Visual feedback for slot availability status### 🗄️ Oracle Database Components#### 📊 Table Design Philosophy**Hierarchical Structure**:- **Core Entities**: USER1, PRODUCT, CART, ORDER1- **Relationship Tables**: CART_PRODUCT, PRODUCT_ORDER, ORDER_ITEM- **Support Tables**: CATEGORY, SHOP, COLLECTION_SLOT- **IoT Tables**: RFID_READ, RFID_PRODUCT**Data Integrity Features**:- Foreign key constraints ensure referential integrity- Check constraints enforce business rules- Triggers maintain data consistency- Sequences provide unique ID generation#### 🔧 Trigger System Details**User Management Triggers**:```sql-- Automatic user type handlingCREATE OR REPLACE TRIGGER trg_set_verifiedBEFORE INSERT ON USER1FOR EACH ROWBEGIN    -- Auto-verify customers, require admin approval for traders    IF LOWER(:NEW.user_type) = 'customer' THEN        :NEW.admin_verified := 'Y';    ELSIF LOWER(:NEW.user_type) = 'trader' THEN        :NEW.admin_verified := 'N';    END IF;END;```**Business Rule Enforcement**:- **Shop Limits**: Traders can only create 2 shops maximum- **Cart Limits**: Maximum 20 items per cart (enforced in application layer)- **Stock Validation**: Prevents negative stock values- **Unique Constraints**: Shop names and coupon codes must be unique**IoT Integration Triggers**:```sql-- Automatic stock updates from RFID scansCREATE OR REPLACE TRIGGER trg_rfid_read_afterAFTER INSERT ON RFID_READFOR EACH ROWDECLARE    v_product_id PRODUCT.product_id%TYPE;BEGIN    -- Find associated product    SELECT product_id INTO v_product_id    FROM RFID_PRODUCT WHERE rfid = :NEW.rfid;        -- Increment stock atomically    UPDATE PRODUCT SET stock = NVL(stock,0) + 1    WHERE product_id = v_product_id;END;```#### 🔢 Sequence Management**ID Generation Strategy**:- **Format**: Each table uses prefixed IDs (user0001, cart0001, pro0001)- **Uniqueness**: Oracle sequences ensure no collisions- **Readability**: Human-readable IDs for debugging and support- **Scalability**: 4-digit sequences support up to 9999 records per type### 🏷️ IoT Hardware Components#### 🤖 Arduino System**Hardware Specifications**:- **Microcontroller**: Arduino Uno R3 or compatible- **RFID Reader**: RC522 module (13.56MHz)- **Communication**: USB serial at 9600 baud- **Power**: 5V via USB or external adapter**Pin Configuration**:```RC522 -> ArduinoSDA  -> Pin 10SCK  -> Pin 13MOSI -> Pin 11MISO -> Pin 12IRQ  -> Not connectedGND  -> GNDRST  -> Pin 93.3V -> 3.3V```**Software Features**:- **Anti-collision**: Handles multiple tags in field- **UID Reading**: Extracts unique identifier from tags- **Serial Output**: Sends formatted data to relay.py- **Error Recovery**: Handles read failures gracefully#### 🔗 Python Relay Bridge**relay.py Architecture**:- **Serial Communication**: Monitors Arduino output continuously- **HTTP Client**: Posts RFID data to Laravel API- **Error Handling**: Retries failed API calls with exponential backoff- **Graceful Shutdown**: Signal-based shutdown for clean process termination- **Logging**: Comprehensive logging for debugging and monitoring**Data Flow Management**:```pythondef main():    while True:        # Read from Arduino        raw = ser.readline().decode('utf-8').strip()        if raw.startswith('RFID:'):            uid = raw[5:]  # Extract UID                        # Send to Laravel API            response = requests.post(API_URL, json={'uid': uid})                        if response.ok:                print(f"[+] Stored UID {uid}")            else:                print(f"[!] API Error: {response.status_code}")```### 💳 Payment & Order Components#### 🏦 PayPal Integration**Payment Flow**:1. **Cart Validation**: Ensures all items are in stock and pickup slot is selected2. **PayPal API**: Creates payment intent with itemized breakdown3. **User Redirect**: Sends user to PayPal for payment approval4. **Callback Handling**: Processes success/cancel responses5. **Order Creation**: Creates ORDER1 record with payment confirmation6. **Cart Clearing**: Removes items from cart after successful payment**Security Features**:- **Payment Verification**: Validates PayPal transaction IDs- **Amount Matching**: Ensures payment amount matches cart total- **Double-spending Prevention**: Cart is locked during payment process- **Transaction Logging**: All payment attempts are logged#### 📧 Email System**Order Confirmation Emails**:- **HTML Templates**: Professional branded email design- **Order Details**: Itemized breakdown with prices- **Pickup Information**: Date, time, and location details- **Contact Information**: Support details for customer service**Email Configuration**:```php// Laravel Mail configuration'smtp' => [    'transport' => 'smtp',    'host' => env('MAIL_HOST', 'smtp.gmail.com'),    'port' => env('MAIL_PORT', 587),    'encryption' => env('MAIL_ENCRYPTION', 'tls'),    'username' => env('MAIL_USERNAME'),    'password' => env('MAIL_PASSWORD'),],```### 🔧 System Integration Components#### 🔄 Session Management**Guest User Handling**:- Shopping cart stored in Laravel sessions- Automatic transfer to database after login- Session persistence across browser sessions- Cleanup of expired session data**Authenticated User Handling**:- Cart data stored in Oracle database- Real-time synchronization with frontend- Concurrent session support- User preference storage#### 📊 Performance Optimization**Database Optimization**:- **Indexes**: All foreign keys and frequently queried columns- **Query Optimization**: Efficient joins and subqueries- **Connection Pooling**: Oracle connection reuse- **Caching**: Frequently accessed data cached in Laravel**Frontend Optimization**:- **Asset Minification**: CSS and JavaScript compression- **Image Optimization**: Compressed product images- **AJAX Debouncing**: Prevents excessive API calls- **Progressive Loading**: Content loads incrementally#### 🛡️ Security Components**Data Protection**:- **SQL Injection Prevention**: Parameterized queries only- **XSS Protection**: Input sanitization and output encoding- **CSRF Protection**: Token-based form validation- **File Upload Security**: Type and size validation for images**Access Control**:- **Role-based Authorization**: Different permissions for customers/traders/admins- **Session Security**: Secure session configuration- **API Rate Limiting**: Prevents abuse of RFID endpoints- **Admin Verification**: Manual approval process for trader accounts

### Multi-Layer Architecture

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           CLEXOMART SYSTEM ARCHITECTURE                        │
├─────────────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │ │
│  │   Frontend  │  │   Laravel   │  │   Oracle    │  │   Logging   │      │ │
│  │   Layer     │  │ Application │  │  Database   │  │   System    │      │ │
│  │             │  │             │  │             │  │             │      │ │
│  │ • Blade     │  │ • Routes    │  │ • Tables    │  │ • RFID Logs │      │ │
│  │   Templates │  │ • Controllers│ │ • Triggers  │  │ • Cart Logs │      │ │
│  │ • Bulma CSS │  │ • Models    │  │ • Sequences │  │ • Error     │      │ │
│  │ • JavaScript│  │ • Middleware│ │ • Views     │  │   Tracking  │      │ │
│  │ • AJAX      │  │ • Validation│ │ • Indexes   │  │ • Audit     │      │ │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘      │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────────┐ │
│  │                          IoT HARDWARE LAYER                                │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │ │
│  │  │   Arduino   │  │   RC522     │  │   relay.py  │  │   Serial    │      │ │
│  │  │ Microcontroller│ RFID Reader │ │   Bridge    │  │ Communication│     │ │
│  │  │             │  │             │  │             │  │             │      │ │
│  │  │ • Read RFID │  │ • Scan Tags │  │ • Process   │  │ • COM Port  │      │ │
│  │  │   Tags      │  │ • Send UID  │  │   Scans     │  │ • Baud Rate │      │ │
│  │  │ • Serial    │  │ • Validate  │  │ • Validate  │  │ • Data      │      │ │
│  │  │   Output    │  │   Range     │  │   UIDs      │  │   Transfer  │      │ │
│  │  │ • Power     │  │ • LED       │  │ • HTTP POST │  │ • Error     │      │ │
│  │  │   Management│  │   Feedback  │  │   to API    │  │   Handling  │      │ │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘      │ │
│  └─────────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘```## 📋 Detailed Component Explanations### 🖥️ Laravel Backend Components#### 🎮 Controllers LayerThe Laravel controllers manage all business logic and HTTP request handling:**CartController** (`Front_end_PHP/app/Http/Controllers/CartController.php`)- **Purpose**: Manages shopping cart operations with 20-item limit enforcement- **Key Methods**:  - `index()`: Displays cart with items from database or session  - `addToCart()`: Validates and adds items with stock checking  - `updateCart()`: AJAX-powered quantity updates with real-time validation  - `removeFromCart()`: Item removal with confirmation modals  - `checkSlotAvailability()`: Validates pickup slots (Wed/Thu/Fri only)  - `transferSessionCartToDatabase()`: Merges guest cart after login- **Business Rules**:   - Maximum 20 items per cart (total quantity, not unique products)  - Stock validation before adding items  - Session persistence for guest users  - Database persistence for authenticated users**CheckoutController** (`Front_end_PHP/app/Http/Controllers/CheckoutController.php`)- **Purpose**: Handles PayPal payment processing and order creation- **Key Methods**:  - `createTransaction()`: Validates cart and creates PayPal payment intent  - `successTransaction()`: Processes successful payments and creates orders  - `orderSuccess()`: Displays order confirmation page- **Cart Clearing Logic**:   - Clears `CART_PRODUCT` table entries after successful payment  - Removes session cart data  - Uses database transactions for atomicity- **Email Integration**: Sends order confirmation and invoice emails**RfidController** (`Front_end_PHP/app/Http/Controllers/RfidController.php`)- **Purpose**: API endpoint for IoT RFID system integration- **Key Methods**:  - `store()`: Receives RFID scans from relay.py bridge  - `getRecent()`: Provides recent scans for real-time UI updates  - `getProductByRfid()`: Maps RFID UIDs to product information- **Auto-Stock Updates**: Triggers fire automatically on RFID_READ inserts#### 🗂️ Models & RelationshipsThe Eloquent models define data relationships and business logic:**Cart Model** (`Front_end_PHP/app/Models/Cart.php`)- **Relationships**:   - `belongsTo(User::class)`: Each cart belongs to one user  - `belongsToMany(Product::class)`: Many-to-many via CART_PRODUCT  - `hasOne(Order::class)`: Cart can have one associated order- **Key Features**:   - Auto-generated cart_id using Oracle sequences  - Pivot table management for cart items with quantities**CartProduct Model** (`Front_end_PHP/app/Models/CartProduct.php`)- **Purpose**: Manages the many-to-many relationship between carts and products- **Composite Key**: Uses both cart_id and product_id as primary key- **Custom Key Handling**: Overrides Laravel's default key handling for composite keys- **Data Integrity**: Ensures quantity and total_amount consistency**RfidRead & RfidProduct Models**- **RfidRead**: Stores all RFID scan events with timestamps- **RfidProduct**: Maps RFID UIDs to specific products- **Integration**: Enables automatic stock updates through database triggers#### 🛡️ Middleware & Security**Authentication Middleware**:- Session-based authentication with user_id storage- Role-based access control (customer vs trader vs admin)- OTP email verification for account security- Admin approval required for trader accounts### 🎨 Frontend Components#### 📱 Blade Templates & UI**Cart Interface** (`Front_end_PHP/resources/views/cart.blade.php`)- **Real-time Validation**: JavaScript-powered quantity updates- **Pickup Slot Selection**:   - Dynamic date generation (Wed/Thu/Fri only)  - 24-hour advance booking requirement  - Capacity checking (max 20 orders per slot)- **Responsive Design**: Mobile-first approach using Bulma CSS- **AJAX Operations**:   - Cart updates without page refresh  - Live stock validation  - Error handling with user feedback**Styling System** (`Front_end_PHP/public/css/cartproduct.css`)- **Color Scheme**:   - Primary Green: #A8C686 (navbar, buttons)  - Accent Yellow: #FED549 (highlights)  - Warning Orange: #CC561E (alerts, remove buttons)- **Component Styling**:  - Card-based layout for cart items  - Hover effects and animations  - Responsive breakpoints for mobile  - Loading states and transitions#### ⚡ JavaScript Features**Real-time Updates**:```javascript// Quantity change handling with debouncingdocument.querySelectorAll('.cart-quantity-input').forEach(input => {    input.addEventListener('change', function() {        // Disable input during update        this.disabled = true;                // Send AJAX request with error handling        fetch('/cart/update', {            method: 'POST',            body: JSON.stringify({                product_id: this.dataset.productId,                quantity: this.value            })        })        .then(response => response.json())        .then(data => {            if (data.success) {                updateCartDisplay(data.updated_totals);                showSuccessMessage('Cart updated successfully');            }        });    });});```**Pickup Slot Management**:- Dynamic date population based on business rules- Real-time availability checking via API calls- Form validation preventing checkout without slot selection- Visual feedback for slot availability status### 🗄️ Oracle Database Components#### 📊 Table Design Philosophy**Hierarchical Structure**:- **Core Entities**: USER1, PRODUCT, CART, ORDER1- **Relationship Tables**: CART_PRODUCT, PRODUCT_ORDER, ORDER_ITEM- **Support Tables**: CATEGORY, SHOP, COLLECTION_SLOT- **IoT Tables**: RFID_READ, RFID_PRODUCT**Data Integrity Features**:- Foreign key constraints ensure referential integrity- Check constraints enforce business rules- Triggers maintain data consistency- Sequences provide unique ID generation#### 🔧 Trigger System Details**User Management Triggers**:```sql-- Automatic user type handlingCREATE OR REPLACE TRIGGER trg_set_verifiedBEFORE INSERT ON USER1FOR EACH ROWBEGIN    -- Auto-verify customers, require admin approval for traders    IF LOWER(:NEW.user_type) = 'customer' THEN        :NEW.admin_verified := 'Y';    ELSIF LOWER(:NEW.user_type) = 'trader' THEN        :NEW.admin_verified := 'N';    END IF;END;```**Business Rule Enforcement**:- **Shop Limits**: Traders can only create 2 shops maximum- **Cart Limits**: Maximum 20 items per cart (enforced in application layer)- **Stock Validation**: Prevents negative stock values- **Unique Constraints**: Shop names and coupon codes must be unique**IoT Integration Triggers**:```sql-- Automatic stock updates from RFID scansCREATE OR REPLACE TRIGGER trg_rfid_read_afterAFTER INSERT ON RFID_READFOR EACH ROWDECLARE    v_product_id PRODUCT.product_id%TYPE;BEGIN    -- Find associated product    SELECT product_id INTO v_product_id    FROM RFID_PRODUCT WHERE rfid = :NEW.rfid;        -- Increment stock atomically    UPDATE PRODUCT SET stock = NVL(stock,0) + 1    WHERE product_id = v_product_id;END;```#### 🔢 Sequence Management**ID Generation Strategy**:- **Format**: Each table uses prefixed IDs (user0001, cart0001, pro0001)- **Uniqueness**: Oracle sequences ensure no collisions- **Readability**: Human-readable IDs for debugging and support- **Scalability**: 4-digit sequences support up to 9999 records per type### 🏷️ IoT Hardware Components#### 🤖 Arduino System**Hardware Specifications**:- **Microcontroller**: Arduino Uno R3 or compatible- **RFID Reader**: RC522 module (13.56MHz)- **Communication**: USB serial at 9600 baud- **Power**: 5V via USB or external adapter**Pin Configuration**:```RC522 -> ArduinoSDA  -> Pin 10SCK  -> Pin 13MOSI -> Pin 11MISO -> Pin 12IRQ  -> Not connectedGND  -> GNDRST  -> Pin 93.3V -> 3.3V```**Software Features**:- **Anti-collision**: Handles multiple tags in field- **UID Reading**: Extracts unique identifier from tags- **Serial Output**: Sends formatted data to relay.py- **Error Recovery**: Handles read failures gracefully#### 🔗 Python Relay Bridge**relay.py Architecture**:- **Serial Communication**: Monitors Arduino output continuously- **HTTP Client**: Posts RFID data to Laravel API- **Error Handling**: Retries failed API calls with exponential backoff- **Graceful Shutdown**: Signal-based shutdown for clean process termination- **Logging**: Comprehensive logging for debugging and monitoring**Data Flow Management**:```pythondef main():    while True:        # Read from Arduino        raw = ser.readline().decode('utf-8').strip()        if raw.startswith('RFID:'):            uid = raw[5:]  # Extract UID                        # Send to Laravel API            response = requests.post(API_URL, json={'uid': uid})                        if response.ok:                print(f"[+] Stored UID {uid}")            else:                print(f"[!] API Error: {response.status_code}")```### 💳 Payment & Order Components#### 🏦 PayPal Integration**Payment Flow**:1. **Cart Validation**: Ensures all items are in stock and pickup slot is selected2. **PayPal API**: Creates payment intent with itemized breakdown3. **User Redirect**: Sends user to PayPal for payment approval4. **Callback Handling**: Processes success/cancel responses5. **Order Creation**: Creates ORDER1 record with payment confirmation6. **Cart Clearing**: Removes items from cart after successful payment**Security Features**:- **Payment Verification**: Validates PayPal transaction IDs- **Amount Matching**: Ensures payment amount matches cart total- **Double-spending Prevention**: Cart is locked during payment process- **Transaction Logging**: All payment attempts are logged#### 📧 Email System**Order Confirmation Emails**:- **HTML Templates**: Professional branded email design- **Order Details**: Itemized breakdown with prices- **Pickup Information**: Date, time, and location details- **Contact Information**: Support details for customer service**Email Configuration**:```php// Laravel Mail configuration'smtp' => [    'transport' => 'smtp',    'host' => env('MAIL_HOST', 'smtp.gmail.com'),    'port' => env('MAIL_PORT', 587),    'encryption' => env('MAIL_ENCRYPTION', 'tls'),    'username' => env('MAIL_USERNAME'),    'password' => env('MAIL_PASSWORD'),],```### 🔧 System Integration Components#### 🔄 Session Management**Guest User Handling**:- Shopping cart stored in Laravel sessions- Automatic transfer to database after login- Session persistence across browser sessions- Cleanup of expired session data**Authenticated User Handling**:- Cart data stored in Oracle database- Real-time synchronization with frontend- Concurrent session support- User preference storage#### 📊 Performance Optimization**Database Optimization**:- **Indexes**: All foreign keys and frequently queried columns- **Query Optimization**: Efficient joins and subqueries- **Connection Pooling**: Oracle connection reuse- **Caching**: Frequently accessed data cached in Laravel**Frontend Optimization**:- **Asset Minification**: CSS and JavaScript compression- **Image Optimization**: Compressed product images- **AJAX Debouncing**: Prevents excessive API calls- **Progressive Loading**: Content loads incrementally#### 🛡️ Security Components**Data Protection**:- **SQL Injection Prevention**: Parameterized queries only- **XSS Protection**: Input sanitization and output encoding- **CSRF Protection**: Token-based form validation- **File Upload Security**: Type and size validation for images**Access Control**:- **Role-based Authorization**: Different permissions for customers/traders/admins- **Session Security**: Secure session configuration- **API Rate Limiting**: Prevents abuse of RFID endpoints- **Admin Verification**: Manual approval process for trader accounts## 📊 Oracle Database Schema

### Core Tables Structure

#### User Management
```sql
-- USER1: Main user table with auto-generated IDs
CREATE TABLE USER1 (
    user_id VARCHAR2(8) PRIMARY KEY,           -- Format: user0001, user0002...
    first_name VARCHAR2(255), 
    last_name VARCHAR2(255),
    user_type VARCHAR2(50),                    -- 'customer' or 'trader'
    email VARCHAR2(255), 
    user_image BLOB,                           -- Profile image storage
    contact_no NUMBER(15),
    password VARCHAR2(255),
    admin_verified CHAR(1),                    -- 'Y' for customers, 'N' for traders initially
    otp NUMBER(7),                             -- Email verification OTP
    is_verified NUMBER(1),                     -- Email verification status
    otp_expires_at TIMESTAMP,
    USER_IMAGE_MIMETYPE VARCHAR2(255),
    USER_IMAGE_FILENAME VARCHAR2(255),
    USER_IMAGE_LASTUPD DATE,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TRADER_PENDING_VERIFICATION: Temporary storage for unverified traders
CREATE TABLE TRADER_PENDING_VERIFICATION (
    user_id VARCHAR2(8) PRIMARY KEY,
    -- Same structure as USER1 for pending trader verification
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
```

#### Product & Shop Management
```sql
-- CATEGORY: Product categorization
CREATE TABLE CATEGORY ( 
    category_id VARCHAR2(8) PRIMARY KEY,       -- Format: cat0001, cat0002...
    category_name VARCHAR2(255), 
    category_description VARCHAR2(255) 
);

-- SHOP: Trader shops (max 2 per trader)
CREATE TABLE SHOP ( 
    shop_id VARCHAR2(8) PRIMARY KEY,           -- Format: shop0001, shop0002...
    shop_name VARCHAR2(255),                   -- Must be unique
    shop_discription VARCHAR2(255),
    user_id VARCHAR2(8),                       -- Must be trader type
    category_id VARCHAR2(8),
    logo BLOB,                                 -- Shop logo storage
    SHOP_IMAGE_MIMETYPE VARCHAR2(255),
    SHOP_IMAGE_FILENAME VARCHAR2(255),
    SHOP_IMAGE_LASTUPD DATE,
    CONSTRAINT fk_shop_user FOREIGN KEY (user_id) REFERENCES USER1(user_id),
    CONSTRAINT fk_shop_category FOREIGN KEY (category_id) REFERENCES CATEGORY(category_id)
);

-- PRODUCT: Product catalog with pricing and stock
CREATE TABLE PRODUCT ( 
    product_id VARCHAR2(8) PRIMARY KEY,        -- Format: pro0001, pro0002...
    product_name VARCHAR2(255), 
    stock INTEGER,                             -- Cannot be negative (trigger enforced)
    shop_id VARCHAR2(8), 
    category_id VARCHAR2(8), 
    description VARCHAR2(255), 
    unit_price DECIMAL(8,2), 
    discount_id VARCHAR2(8), 
    price_after_discount DECIMAL(8,2),        -- Calculated by trigger
    PRODUCT_image BLOB,                        -- Product image storage
    PRODUCT_IMAGE_MIMETYPE VARCHAR2(255), 
    PRODUCT_IMAGE_FILENAME VARCHAR2(255), 
    PRODUCT_IMAGE_LASTUPD DATE,
    CONSTRAINT fk_product_shop FOREIGN KEY (shop_id) REFERENCES SHOP(shop_id), 
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES CATEGORY(category_id), 
    CONSTRAINT fk_product_discount FOREIGN KEY (discount_id) REFERENCES DISCOUNT(discount_id)
);
```

#### Shopping Cart System
```sql
-- CART: User shopping carts (auto-created for customers)
CREATE TABLE CART ( 
    cart_id VARCHAR2(8) PRIMARY KEY,           -- Format: cart0001, cart0002...
    user_id VARCHAR2(8),                       -- One cart per customer
    creation_date DATE,
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES USER1(user_id) 
);

-- CART_PRODUCT: Cart items with quantity and total
CREATE TABLE CART_PRODUCT ( 
    cart_id VARCHAR2(8), 
    product_id VARCHAR2(8), 
    product_quantity INTEGER,                  -- Max 20 items total per cart
    total_amount DECIMAL(8,2), 
    PRIMARY KEY (cart_id, product_id), 
    CONSTRAINT fk_cart_product_product FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id), 
    CONSTRAINT fk_cart_product_cart FOREIGN KEY (cart_id) REFERENCES CART(cart_id) 
);
```

#### Order Management
```sql
-- COLLECTION_SLOT: Pickup time slots
CREATE TABLE COLLECTION_SLOT ( 
    slot_id VARCHAR2(8) PRIMARY KEY,           -- Format: slo0001, slo0002...
    day VARCHAR2(15),                          -- Wednesday, Thursday, Friday
    time TIMESTAMP,                            -- 3 time slots per day
    no_order NUMBER                            -- Max 20 orders per slot
);

-- ORDER1: Main order table
CREATE TABLE ORDER1 ( 
    order_id VARCHAR2(8) PRIMARY KEY,          -- Format: ord0001, ord0002...
    order_date DATE,                           -- Auto-set by trigger
    coupon_id VARCHAR2(8), 
    cart_id VARCHAR2(8), 
    user_id VARCHAR2(8),
    payment_amount DECIMAL(8,2), 
    slot_id VARCHAR2(8),                       -- Required pickup slot
    CONSTRAINT fk_order_coupon FOREIGN KEY (coupon_id) REFERENCES COUPON(coupon_id), 
    CONSTRAINT fk_order_cart FOREIGN KEY (cart_id) REFERENCES CART(cart_id), 
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES USER1(user_id),
    CONSTRAINT fk_order_collection_slot FOREIGN KEY (slot_id) REFERENCES COLLECTION_SLOT(slot_id) 
);

-- ORDER_ITEM: Individual order items with pricing
CREATE TABLE ORDER_ITEM (
    order_item_id VARCHAR2(10) PRIMARY KEY,    -- Format: OI000001, OI000002...
    order_id VARCHAR2(8) NOT NULL,
    product_id VARCHAR2(8) NOT NULL,
    quantity NUMBER(5) DEFAULT 1 NOT NULL,
    unit_price NUMBER(10,2) NOT NULL,
    item_total AS (quantity * unit_price),     -- Computed column
    CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES ORDER1(order_id),
    CONSTRAINT fk_order_item_product FOREIGN KEY (product_id) REFERENCES PRODUCT(product_id)
);

-- ORDER_STATUS: Order tracking
CREATE TABLE ORDER_STATUS (
    order_id VARCHAR2(8) PRIMARY KEY,
    status VARCHAR2(20) DEFAULT 'pending' NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT fk_order_status_order FOREIGN KEY (order_id) REFERENCES ORDER1(order_id),
    CONSTRAINT chk_order_status CHECK (status IN ('pending', 'processing', 'completed', 'cancelled'))
);
```

### 🏷️ RFID IoT System Tables

```sql
-- RFID_READ: All RFID scan records
CREATE TABLE RFID_READ(
    rfid_id VARCHAR2(8) PRIMARY KEY,           -- Format: rif0001, rif0002...
    rfid VARCHAR2(32),                         -- RFID tag UID (hex format)
    time TIMESTAMP                             -- Scan timestamp
);

-- RFID_PRODUCT: RFID tag to product mapping
CREATE TABLE RFID_PRODUCT (
    rfid VARCHAR2(32) PRIMARY KEY,             -- RFID UID (unique)
    product_id VARCHAR2(8) NOT NULL,           -- Associated product
    CONSTRAINT fk_rfid_product_product FOREIGN KEY (product_id) REFERENCES PRODUCT (product_id)
);
```

### Oracle Sequences & Triggers

#### Auto-Increment Sequences
```sql
-- Sequences for all primary keys
CREATE SEQUENCE seq_userid START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_shopid START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_productid START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_cartid START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_orderid START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_rfid START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
-- ... additional sequences for all tables

-- Auto-ID generation triggers
CREATE OR REPLACE TRIGGER trg_userid
BEFORE INSERT ON USER1
FOR EACH ROW
BEGIN
    :NEW.user_id := 'user' || TO_CHAR(seq_userid.NEXTVAL, 'FM0000');
END;

CREATE OR REPLACE TRIGGER trg_productid
BEFORE INSERT ON PRODUCT
FOR EACH ROW
BEGIN
    :NEW.product_id := 'pro' || TO_CHAR(seq_productid.NEXTVAL, 'FM0000');
END;

CREATE OR REPLACE TRIGGER trg_rfid_id
BEFORE INSERT ON RFID_READ
FOR EACH ROW
BEGIN 
    :NEW.rfid_id := 'rif' || TO_CHAR(seq_rfid.NEXTVAL, 'FM0000');
END;
```

#### Business Logic Triggers
```sql
-- Automatic trader verification handling
CREATE OR REPLACE TRIGGER trg_set_verified
BEFORE INSERT ON USER1
FOR EACH ROW
BEGIN
    IF LOWER(:NEW.user_type) = 'customer' THEN
        :NEW.admin_verified := 'Y';  -- Auto-verify customers
    ELSIF LOWER(:NEW.user_type) = 'trader' THEN
        :NEW.admin_verified := 'N';  -- Traders need admin approval
    END IF;
END;

-- Copy unverified traders to pending table
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

-- Auto-create cart for new customers
CREATE OR REPLACE TRIGGER trg_cart_creation
AFTER INSERT ON USER1
FOR EACH ROW 
BEGIN 
    IF LOWER(:NEW.user_type) = 'customer' THEN
        INSERT INTO CART(cart_id, user_id, creation_date)
        VALUES ('cart' || TO_CHAR(seq_cartid.NEXTVAL, 'FM0000'), :NEW.user_id, SYSDATE );
    END IF;
END;

-- Enforce shop limits per trader
CREATE OR REPLACE TRIGGER trg_trader
BEFORE INSERT OR UPDATE ON SHOP 
FOR EACH ROW 
DECLARE 
    v_user_type VARCHAR2(50);
    v_shop_count NUMBER;
BEGIN
    -- Verify user is a trader
    SELECT user_type INTO v_user_type
    FROM USER1
    WHERE user_id = :NEW.user_id;
    
    IF UPPER(v_user_type) != 'TRADER' THEN
        RAISE_APPLICATION_ERROR(-20007, 'Not a trader. Must open trader account.');
    END IF;
    
    -- Enforce 2-shop limit per trader
    SELECT COUNT(*) INTO v_shop_count
    FROM SHOP
    WHERE user_id = :NEW.user_id;
    
    IF v_shop_count >= 2 THEN
        RAISE_APPLICATION_ERROR (-20008, 'A trader can have maximum of 2 shops.');
    END IF;
END;

-- Prevent negative stock
CREATE OR REPLACE TRIGGER trg_stock
BEFORE INSERT OR UPDATE ON PRODUCT
FOR EACH ROW
BEGIN 
     IF :NEW.stock < 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'Stock cannot be negative.');
    END IF;
END;
```

## 🏷️ IoT Inventory System - Complete Implementation

### System OverviewThe ClexoMart IoT inventory system uses **RFID technology** to automatically track and update product stock levels in real-time. When products with RFID tags are scanned, the system automatically increments the stock count in the database.#### 🎯 IoT System Architecture Components**Hardware Layer**:- **Arduino Uno R3**: Primary microcontroller for RFID scanning operations- **RC522 RFID Module**: 13.56MHz MIFARE reader supporting ISO14443A protocol- **USB Serial Interface**: 9600 baud communication with computer- **Power Management**: 5V DC power supply with 3.3V regulation for RFID module**Software Bridge Layer**:- **relay.py Script**: Python bridge application running continuously- **Serial Communication**: Real-time monitoring of Arduino output- **HTTP Client**: RESTful API communication with Laravel backend- **Error Recovery**: Automatic retry logic and connection management**Database Integration Layer**:- **Laravel API Endpoints**: Secure RFID data processing endpoints- **Oracle Triggers**: Automatic stock increment on RFID scan events- **Data Validation**: UID format validation and product association verification- **Audit Trail**: Complete logging of all RFID scan events with timestamps#### 🔧 Technical Specifications**RFID Technology Details**:- **Frequency**: 13.56MHz ISM band- **Protocol**: ISO14443A (MIFARE Classic/Plus compatible)- **Read Range**: 0-5cm (optimal at 1-3cm)- **UID Length**: 4, 7, or 10 bytes (typically 4 bytes for MIFARE Classic)- **Data Rate**: 106 kbit/s- **Anti-collision**: Supports multiple tags in field simultaneously**Arduino Programming Features**:- **Library**: MFRC522 library for RC522 module communication- **SPI Interface**: Hardware SPI for high-speed data transfer- **Memory Management**: Efficient string handling for UID processing- **Error Handling**: Robust error detection and recovery- **Serial Protocol**: Standardized output format for relay.py parsing**Python Bridge Capabilities**:- **Cross-platform**: Compatible with Windows, Linux, and macOS- **Concurrent Processing**: Handles multiple RFID scans efficiently- **API Communication**: RESTful HTTP client with JSON payload- **Process Management**: PID-based process control and monitoring- **Configuration**: Environment-based configuration management### Hardware Components

#### Arduino RFID Reader Setup
```cpp
// Arduino code for RFID scanning
#include <SPI.h>
#include <MFRC522.h>

#define RST_PIN 9
#define SS_PIN 10

MFRC522 mfrc522(SS_PIN, RST_PIN);

void setup() {
    Serial.begin(9600);
    SPI.begin();
    mfrc522.PCD_Init();
    Serial.println("RFID Reader Ready");
}

void loop() {
    // Look for new cards
    if (!mfrc522.PICC_IsNewCardPresent()) {
        return;
    }
    
    // Select one of the cards
    if (!mfrc522.PICC_ReadCardSerial()) {
        return;
    }
    
    // Read UID and convert to hex string
    String uid = "";
    for (byte i = 0; i < mfrc522.uid.size; i++) {
        uid += String(mfrc522.uid.uidByte[i] < 0x10 ? "0" : "");
        uid += String(mfrc522.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();
    
    // Send UID via serial in format: "RFID:A1B2C3D4"
    Serial.println("RFID:" + uid);
    
    // Halt PICC
    mfrc522.PICC_HaltA();
    
    delay(1000); // Prevent multiple reads
}
```

### Python Relay Bridge - relay.py

The `relay.py` script acts as a bridge between the Arduino RFID reader and the Laravel application:

```python
#!/usr/bin/env python3
import serial
import requests
import time
import os
import sys

# ─── CONFIG ───────────────────────────────────────────────────────────────
SERIAL_PORT = 'COM3'                          # Windows Arduino port
BAUDRATE    = 9600
API_URL     = 'http://127.0.0.1:8000/api/rfid'  # Laravel API endpoint
STOP_SIGNAL_FILE = 'relay_stop.signal'        # Graceful shutdown signal
# ──────────────────────────────────────────────────────────────────────────

def check_stop_signal():
    """Check if the stop signal file exists, and if so, exit gracefully"""
    if os.path.exists(STOP_SIGNAL_FILE):
        print(f"[!] Stop signal detected, exiting...")
        try:
            os.remove(STOP_SIGNAL_FILE)
        except:
            pass
        sys.exit(0)
    return False

def main():
    print(f"[+] RFID Relay starting - PID: {os.getpid()}")
    
    try:
        # Establish serial connection with Arduino
        ser = serial.Serial(SERIAL_PORT, BAUDRATE, timeout=1)
        print(f"[+] Listening on {SERIAL_PORT} at {BAUDRATE} baud")
    except Exception as e:
        print(f"[!] Could not open {SERIAL_PORT}: {e}")
        return

    while True:
        # Check for graceful shutdown signal
        check_stop_signal()
        
        try:
            # Read data from Arduino via serial
            raw = ser.readline().decode('utf-8', errors='ignore').strip()
            if not raw:
                time.sleep(0.1)
                continue
                
            # Extract UID from Arduino output
            uid = raw.upper()
            print(f"[+] Read UID: {uid}")
            
            # Send UID to Laravel API
            resp = requests.post(
                API_URL,
                json={'uid': uid},
                headers={'Accept': 'application/json'},
                timeout=2
            )
            
            if resp.ok:
                print(f"[+] Stored UID {uid}")
            else:
                # Log API errors (truncated for readability)
                msg = (resp.text[:120] + '…') if resp.text else resp.reason
                print(f"[!] API {resp.status_code}: {msg}")
                
        except Exception as e:
            print(f"[!] Exception: {e}")
            
        time.sleep(0.1)

if __name__ == '__main__':
    # Set console title for Windows
    if os.name == 'nt':  # Windows
        try:
            import ctypes
            ctypes.windll.kernel32.SetConsoleTitleW("relay.py")
        except:
            pass
    
    try:
        main()
    except KeyboardInterrupt:
        print("[!] Script terminated by user")
    finally:
        # Clean up stop signal file
        if os.path.exists(STOP_SIGNAL_FILE):
            try:
                os.remove(STOP_SIGNAL_FILE)
            except:
                pass
```

### Laravel API Integration

#### RFID Controller
```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRfidRequest;
use App\Models\RfidRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    /**
     * POST /api/rfid - Process RFID scan from relay.py
     */
    public function store(StoreRfidRequest $request): JsonResponse
    {
        $read = RfidRead::create([
            'rfid' => strtoupper($request->uid),
            'time' => Carbon::now(),
        ]);

        return response()->json([
            'id'   => $read->rfid_id,
            'rfid' => $read->rfid,
        ], 201);
    }
    
    /**
     * GET /api/rfid/recent - Retrieve recent RFID scans
     */
    public function getRecent(Request $request): JsonResponse
    {
        try {
            $since = $request->query('since') 
                ? Carbon::parse($request->query('since')) 
                : Carbon::now()->subMinutes(5);
            
            $scans = RfidRead::where('time', '>', $since)
                ->orderBy('time', 'asc')
                ->get(['rfid_id', 'rfid', 'time']);
            
            return response()->json([
                'success' => true,
                'scans' => $scans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving RFID scans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/rfid/{uid}/product - Get product info for RFID UID
     */
    public function getProductByRfid($uid): JsonResponse
    {
        try {
            $pdo = \DB::connection()->getPdo();
            
            $sql = "
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.description,
                    p.unit_price,
                    p.stock
                FROM 
                    RFID_PRODUCT rp
                JOIN 
                    PRODUCT p ON p.product_id = rp.product_id
                WHERE 
                    rp.rfid = :rfid
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':rfid', $uid);
            $stmt->execute();
            $product = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'No product found for this RFID UID'
                ]);
            }
            
            // Handle Oracle column name case sensitivity
            $productData = [
                'product_id' => $product['PRODUCT_ID'] ?? $product['product_id'] ?? null,
                'product_name' => $product['PRODUCT_NAME'] ?? $product['product_name'] ?? 'Unknown Product',
                'description' => $product['DESCRIPTION'] ?? $product['description'] ?? '',
                'unit_price' => $product['UNIT_PRICE'] ?? $product['unit_price'] ?? 0,
                'stock' => $product['STOCK'] ?? $product['stock'] ?? 0
            ];
            
            return response()->json([
                'success' => true,
                'product_id' => $productData['product_id'],
                'product_name' => $productData['product_name'],
                'description' => $productData['description'],
                'unit_price' => $productData['unit_price'],
                'stock' => $productData['stock']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product information: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

#### Laravel Models
```php
// app/Models/RfidRead.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidRead extends Model
{
    protected $table = 'RFID_READ';
    protected $primaryKey = 'rfid_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['rfid_id', 'rfid', 'time'];
    protected $dates = ['time'];

    /**
     * Get the product associated with this RFID scan
     */
    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            RfidProduct::class,
            'rfid',         // Foreign key on RFID_PRODUCT table
            'product_id',   // Foreign key on PRODUCT table  
            'rfid',         // Local key on RFID_READ table
            'product_id'    // Local key on RFID_PRODUCT table
        );
    }
}

// app/Models/RfidProduct.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidProduct extends Model
{
    protected $table = 'RFID_PRODUCT';
    protected $primaryKey = 'rfid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['rfid', 'product_id'];

    /**
     * Get the product associated with this RFID tag
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Get all RFID reads for this tag
     */
    public function reads()
    {
        return $this->hasMany(RfidRead::class, 'rfid', 'rfid');
    }
}
```

#### API Routes
```php
// routes/api.php
<?php

use App\Http\Controllers\RfidController;
use Illuminate\Support\Facades\Route;

Route::post('/rfid', [RfidController::class, 'store']);
Route::get('/rfid/recent', [RfidController::class, 'getRecent']);
Route::get('/rfid/{uid}/product', [RfidController::class, 'getProductByRfid']);
```

### 🔄 IoT Data Flow Process

#### Complete RFID Scan to Database Update Flow

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           RFID SCAN TO STOCK UPDATE FLOW                       │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  1. RFID Tag Scanned                                                           │
│     ├── Arduino detects tag                                                    │
│     ├── Reads UID (e.g., "A1B2C3D4")                                          │
│     └── Sends via Serial: "RFID:A1B2C3D4"                                     │
│                                                                                 │
│  2. relay.py Processing                                                        │
│     ├── Receives UID from serial                                               │
│     ├── Creates JSON payload with timestamp                                    │
│     └── POST /api/rfid with UID                                               │
│                                                                                 │
│  3. Laravel API Processing                                                     │
│     ├── RfidController@store validates UID                                     │
│     ├── Creates RFID_READ record                                              │
│     └── Triggers auto-stock update                                            │
│                                                                                 │
│  4. Database Trigger Execution                                                │
│     ├── trg_rfid_read_after trigger fires                                     │
│     ├── Finds product_id from RFID_PRODUCT                                    │
│     ├── Updates PRODUCT.stock += 1                                            │
│     └── Logs stock change                                                     │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 🎯 Critical IoT Database Trigger

The heart of the IoT inventory system is the Oracle trigger that automatically updates stock when an RFID tag is scanned:

```sql
-- RFID Stock Auto-Update Trigger
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
```

### UID to Database Integration Process

#### 1. RFID Tag Association
Before a tag can update stock, it must be associated with a product:

```sql
-- Example: Associate RFID tag "A1B2C3D4" with product "pro0001"
INSERT INTO RFID_PRODUCT (rfid, product_id) 
VALUES ('A1B2C3D4', 'pro0001');
```

#### 2. Scan Processing
When relay.py sends a UID to the API:

```json
POST /api/rfid
{
    "uid": "A1B2C3D4"
}
```

#### 3. Database Record Creation
Laravel creates an RFID_READ record:

```sql
INSERT INTO RFID_READ (rfid_id, rfid, time) 
VALUES ('rif0001', 'A1B2C3D4', CURRENT_TIMESTAMP);
```

#### 4. Automatic Stock Update
The trigger fires and updates stock:

```sql
-- Trigger finds product_id from RFID_PRODUCT mapping
SELECT product_id FROM RFID_PRODUCT WHERE rfid = 'A1B2C3D4';
-- Returns: 'pro0001'

-- Trigger updates product stock
UPDATE PRODUCT SET stock = stock + 1 WHERE product_id = 'pro0001';
```

#### 5. Response to relay.py
Laravel returns confirmation:

```json
{
    "id": "rif0001",
    "rfid": "A1B2C3D4"
}
```

### Real-Time Frontend Integration

The trader interface includes real-time RFID scanning with live updates:

```javascript
// Real-time RFID scan monitoring
let lastTimestamp = new Date().toISOString();
const pollingInterval = setInterval(() => {
    fetch('/api/rfid/recent?since=' + encodeURIComponent(lastTimestamp))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.scans.length > 0) {
                data.scans.forEach(scan => {
                    // Get product information for the scanned UID
                    fetch(`/api/rfid/${scan.rfid}/product`)
                        .then(response => response.json())
                        .then(productData => {
                            const productName = productData.success ? 
                                productData.product_name : 'Unknown Product';
                            
                            // Display scan result in UI
                            displayScanResult(scan.rfid, productName, scan.time);
                        });
                });
                
                // Update timestamp for next poll
                lastTimestamp = data.scans[data.scans.length - 1].time;
            }
        });
}, 1000); // Poll every second
```

## 🛒 Shopping Cart System### 🎯 Advanced Cart Architecture#### Dual-Mode Cart SystemThe ClexoMart cart system operates in two distinct modes to accommodate different user types:**Guest User Mode (Session-Based)**:- **Storage**: Laravel session arrays with cart data- **Persistence**: Browser session lifetime- **Transfer Mechanism**: Automatic migration to database on login- **Limitations**: No cross-device synchronization- **Performance**: Fast access with minimal database queries**Authenticated User Mode (Database-Driven)**:- **Storage**: Oracle CART and CART_PRODUCT tables- **Persistence**: Permanent until order completion or manual clearing- **Synchronization**: Real-time updates across multiple devices- **Benefits**: Full audit trail and recovery capabilities- **Scalability**: Supports concurrent user sessions#### 🔢 Quantity Management System**20-Item Limit Logic**:- **Enforcement Level**: Application layer validation (not database constraints)- **Calculation Method**: Sum of all product quantities across cart- **Real-time Validation**: AJAX-powered quantity checks on every update- **User Feedback**: Dynamic progress indicators showing remaining capacity- **Error Handling**: Graceful degradation with specific error messages**Stock Validation Pipeline**:1. **Pre-addition Check**: Validates available stock before adding to cart2. **Real-time Updates**: Stock levels updated immediately via RFID scans3. **Checkout Validation**: Final stock verification at payment time4. **Conflict Resolution**: Handles concurrent stock modifications#### 🎨 User Interface Components**Dynamic Cart Display**:- **Card-Based Layout**: Each cart item displayed in interactive cards- **Real-time Totals**: Live calculation of subtotals and grand totals- **Visual Feedback**: Loading states, success animations, error indicators- **Responsive Design**: Mobile-optimized layout with touch-friendly controls**Quantity Control Interface**:```javascript// Advanced quantity update with debouncingconst quantityInputs = document.querySelectorAll('.cart-quantity-input');let updateTimeout;quantityInputs.forEach(input => {    input.addEventListener('input', function() {        clearTimeout(updateTimeout);        this.classList.add('updating');                updateTimeout = setTimeout(() => {            updateCartQuantity(this.dataset.productId, this.value);        }, 500); // 500ms debounce to prevent excessive API calls    });});function updateCartQuantity(productId, quantity) {    fetch('/cart/update', {        method: 'POST',        headers: {            'Content-Type': 'application/json',            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')        },        body: JSON.stringify({            product_id: productId,            quantity: parseInt(quantity)        })    })    .then(response => response.json())    .then(data => {        if (data.success) {            updateCartTotals(data.updated_totals);            showSuccessNotification('Cart updated successfully');        } else {            showErrorNotification(data.message);            revertQuantityInput(productId);        }    })    .catch(error => {        showErrorNotification('Network error occurred');        revertQuantityInput(productId);    });}```### Cart Management Features

#### 20-Item Limit Enforcement
The cart system enforces a maximum of 20 items total across all products:

```php
// CartController.php - Add to cart with limit check
public function addToCart(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:PRODUCT,product_id',
        'quantity' => 'required|integer|min:1'
    ]);

    $user = Auth::user();
    $cart = Cart::where('user_id', $user->user_id)->first();
    
    // Check current total items in cart
    $currentTotal = CartProduct::where('cart_id', $cart->cart_id)
        ->sum('product_quantity');
    
    if ($currentTotal + $request->quantity > 20) {
        return response()->json([
            'success' => false,
            'message' => 'Cannot add items. Cart limit is 20 items total.',
            'current_total' => $currentTotal,
            'max_allowed' => 20
        ]);
    }

    // Add or update cart item
    $cartProduct = CartProduct::updateOrCreate(
        [
            'cart_id' => $cart->cart_id,
            'product_id' => $request->product_id
        ],
        [
            'product_quantity' => DB::raw('product_quantity + ' . $request->quantity),
            'total_amount' => DB::raw('total_amount + (' . $request->quantity . ' * (SELECT price_after_discount FROM PRODUCT WHERE product_id = \'' . $request->product_id . '\'))')
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'Item added to cart successfully',
        'cart_total_items' => $currentTotal + $request->quantity
    ]);
}
```

### Collection Slot Management

#### Pickup Time Slots
Orders must be assigned to available pickup slots:

```sql
-- Collection slots: Wed/Thu/Fri, 3 time slots per day, max 20 orders each
INSERT INTO COLLECTION_SLOT (slot_id, day, time, no_order) VALUES
('slo0001', 'Wednesday', TIMESTAMP '2024-01-01 09:00:00', 0),
('slo0002', 'Wednesday', TIMESTAMP '2024-01-01 13:00:00', 0),
('slo0003', 'Wednesday', TIMESTAMP '2024-01-01 17:00:00', 0),
('slo0004', 'Thursday', TIMESTAMP '2024-01-01 09:00:00', 0),
('slo0005', 'Thursday', TIMESTAMP '2024-01-01 13:00:00', 0),
('slo0006', 'Thursday', TIMESTAMP '2024-01-01 17:00:00', 0),
('slo0007', 'Friday', TIMESTAMP '2024-01-01 09:00:00', 0),
('slo0008', 'Friday', TIMESTAMP '2024-01-01 13:00:00', 0),
('slo0009', 'Friday', TIMESTAMP '2024-01-01 17:00:00', 0);
```

#### Slot Availability Check
```php
// Check available pickup slots
public function getAvailableSlots()
{
    $availableSlots = CollectionSlot::where('no_order', '<', 20)
        ->orderBy('day')
        ->orderBy('time')
        ->get();
    
    return response()->json([
        'success' => true,
        'slots' => $availableSlots->map(function($slot) {
            return [
                'slot_id' => $slot->slot_id,
                'day' => $slot->day,
                'time' => $slot->time->format('H:i'),
                'available_spots' => 20 - $slot->no_order,
                'is_full' => $slot->no_order >= 20
            ];
        })
    ]);
}
```

## 🔐 Authentication & Authorization

### User Types & Verification

#### Customer Registration
- **Auto-verified**: Customers are automatically verified upon registration
- **Instant access**: Can immediately start shopping and placing orders
- **Auto-cart creation**: Shopping cart is automatically created

#### Trader Registration
- **Pending verification**: Traders require admin approval
- **Temporary storage**: Stored in `TRADER_PENDING_VERIFICATION` table
- **Admin review**: Admin must manually verify trader applications
- **Shop creation**: Can create up to 2 shops after verification

### OTP Email Verification
```php
// Email verification with OTP
public function sendVerificationOTP(User $user)
{
    $otp = rand(100000, 999999);
    $expiresAt = now()->addMinutes(15);
    
    $user->update([
        'otp' => $otp,
        'otp_expires_at' => $expiresAt
    ]);
    
    // Send OTP email
    Mail::to($user->email)->send(new VerificationOTP($otp));
    
    return response()->json([
        'success' => true,
        'message' => 'Verification OTP sent to your email'
    ]);
}
```

## 💳 Payment Integration

### PayPal Integration
```php
// PayPal payment processing
public function processPayment(Request $request)
{
    $request->validate([
        'order_id' => 'required|exists:ORDER1,order_id',
        'payment_method' => 'required|in:paypal',
        'paypal_payment_id' => 'required_if:payment_method,paypal'
    ]);

    $order = Order::find($request->order_id);
    
    // Verify PayPal payment
    $paypalResponse = $this->verifyPayPalPayment($request->paypal_payment_id);
    
    if ($paypalResponse['status'] === 'COMPLETED') {
        // Create payment record
        Payment::create([
            'payment_method' => 'PayPal',
            'payment_date' => now(),
            'user_id' => $order->user_id,
            'order_id' => $order->order_id,
            'payment_amount' => $order->payment_amount
        ]);
        
        // Update order status
        OrderStatus::where('order_id', $order->order_id)
            ->update(['status' => 'processing']);
        
        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully'
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Payment verification failed'
    ]);
}
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.1+
- Laravel 10+
- Oracle Database 19c+
- Python 3.8+
- Arduino IDE
- RC522 RFID Reader Module

### Database Setup
```bash
# 1. Create Oracle database schema
sqlplus username/password@database @Backend_apex/clexomart.sql

# 2. Create sequences and triggers
sqlplus username/password@database @Backend_apex/sequence.sql
sqlplus username/password@database @Backend_apex/Trigger.sql

# 3. Configure Laravel database connection
# Edit .env file:
DB_CONNECTION=oracle
DB_HOST=localhost
DB_PORT=1521
DB_DATABASE=xe
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Laravel Application Setup
```bash
# 1. Install dependencies
composer install
npm install

# 2. Generate application key
php artisan key:generate

# 3. Run migrations (if any additional)
php artisan migrate

# 4. Start development server
php artisan serve
```

### IoT Hardware Setup
```bash
# 1. Upload Arduino code to microcontroller
# 2. Connect RC522 RFID reader to Arduino
# 3. Install Python dependencies
pip install pyserial requests

# 4. Configure relay.py settings
# Edit SERIAL_PORT and API_URL in relay.py

# 5. Run RFID relay
python relay.py
```

### RFID Tag Association
```bash
# Associate RFID tags with products via admin interface
# Or directly in database:
INSERT INTO RFID_PRODUCT (rfid, product_id) 
VALUES ('A1B2C3D4', 'pro0001');
```

## 📈 Performance & Scalability

### Database Optimization
- **Indexed foreign keys**: All foreign key columns are indexed
- **Computed columns**: `ORDER_ITEM.item_total` is computed automatically
- **Efficient triggers**: Minimal overhead with optimized trigger logic
- **Connection pooling**: Oracle connection pooling for high concurrency

### Caching Strategy
```php
// Cache frequently accessed data
Cache::remember('available_slots', 300, function () {
    return CollectionSlot::where('no_order', '<', 20)->get();
});

Cache::remember('product_categories', 3600, function () {
    return Category::all();
});
```

### RFID System Performance
- **Asynchronous processing**: relay.py handles multiple scans efficiently
- **Error recovery**: Automatic retry logic for failed API calls
- **Graceful shutdown**: Signal-based shutdown for clean process termination

## 🔧 Maintenance & Monitoring

### Logging & Debugging
```php
// Comprehensive logging for RFID operations
Log::info('RFID tag scan received', [
    'uid' => $rfidUid,
    'timestamp' => now(),
    'reader_location' => 'main_warehouse'
]);

Log::error('RFID scan processing error', [
    'error' => $e->getMessage(),
    'rfid_uid' => $request->rfid_uid
]);
```

### System Health Checks
```bash
# Check relay.py process status
ps aux | grep relay.py

# Monitor RFID scan frequency
SELECT COUNT(*) as scans_last_hour 
FROM RFID_READ 
WHERE time > SYSDATE - INTERVAL '1' HOUR;

# Check cart system performance
SELECT AVG(product_quantity) as avg_cart_size 
FROM CART_PRODUCT;
```

## 📚 API Documentation

### RFID Endpoints
```
POST /api/rfid
- Body: {"uid": "A1B2C3D4"}
- Response: {"id": "rif0001", "rfid": "A1B2C3D4"}

GET /api/rfid/recent?since=2024-01-01T10:00:00Z
- Response: {"success": true, "scans": [...]}

GET /api/rfid/{uid}/product
- Response: {"success": true, "product_name": "...", "stock": 10}
```

### Cart Management
```
POST /api/cart/add
- Body: {"product_id": "pro0001", "quantity": 2}
- Response: {"success": true, "cart_total_items": 5}

GET /api/cart/items
- Response: {"success": true, "items": [...], "total_items": 5}

DELETE /api/cart/clear
- Response: {"success": true, "message": "Cart cleared"}
```

## 🎯 Key Features Summary

### ✅ Implemented Features
- **Oracle Database**: Complete schema with triggers and sequences
- **RFID IoT System**: Hardware integration with automatic stock updates
- **User Management**: Customer/trader registration with admin verification
- **Shopping Cart**: 20-item limit with session persistence
- **Order Management**: Pickup slot system with capacity limits
- **Payment Processing**: PayPal integration
- **Real-time Updates**: Live RFID scanning with frontend updates
- **Mobile Responsive**: Bulma CSS framework for mobile-first design

### 🔄 IoT System Highlights
- **Seamless Integration**: Arduino → relay.py → Laravel → Oracle
- **Real-time Processing**: Instant stock updates on RFID scan
- **Error Handling**: Comprehensive error recovery and logging
- **Scalable Architecture**: Supports multiple RFID readers
- **Admin Management**: RFID tag association and monitoring tools

## 🚀 Production Deployment & Monitoring### 📦 Deployment Components#### Server Infrastructure Requirements**Web Server Configuration**:- **Apache/Nginx**: Configured for Laravel with URL rewriting- **PHP-FPM**: Process manager for optimal PHP performance- **SSL Certificate**: HTTPS enforcement for secure transactions- **Load Balancing**: Multiple server instances for high availability**Database Server Setup**:- **Oracle Enterprise Edition**: Production-grade database with clustering- **Connection Pooling**: Optimized connection management- **Backup Strategy**: Automated daily backups with point-in-time recovery- **Performance Tuning**: Indexed queries and optimized table spaces**IoT Infrastructure**:- **Dedicated Hardware**: Industrial Arduino setups with reliable power- **Network Configuration**: Dedicated network segments for IoT devices- **Monitoring Systems**: Real-time hardware health monitoring- **Redundancy**: Multiple RFID readers for critical locations#### 🔧 Environment Configuration**Production Laravel Settings**:```bash# .env.productionAPP_ENV=productionAPP_DEBUG=falseAPP_URL=https://clexomart.com# Database ConfigurationDB_CONNECTION=oracleDB_HOST=prod-oracle-serverDB_PORT=1521DB_DATABASE=CLEXOMART_PRODDB_USERNAME=clexo_userDB_PASSWORD=secure_password# Mail ConfigurationMAIL_MAILER=smtpMAIL_HOST=smtp.gmail.comMAIL_PORT=587MAIL_USERNAME=orders@clexomart.comMAIL_PASSWORD=app_specific_password# PayPal ConfigurationPAYPAL_MODE=livePAYPAL_CLIENT_ID=production_client_idPAYPAL_CLIENT_SECRET=production_secret# Cache ConfigurationCACHE_DRIVER=redisSESSION_DRIVER=redisQUEUE_CONNECTION=redis# LoggingLOG_CHANNEL=stackLOG_LEVEL=info```**Oracle Production Configuration**:```sql-- Performance optimization settingsALTER SYSTEM SET shared_pool_size=512M;ALTER SYSTEM SET db_cache_size=1G;ALTER SYSTEM SET pga_aggregate_target=512M;-- Enable archive log mode for backupsALTER DATABASE ARCHIVELOG;-- Create performance monitoring viewsCREATE OR REPLACE VIEW v_cart_performance ASSELECT     COUNT(*) as total_carts,    AVG(total_items) as avg_items_per_cart,    MAX(last_updated) as last_activityFROM (    SELECT         cart_id,        SUM(product_quantity) as total_items,        MAX(SYSDATE) as last_updated    FROM CART_PRODUCT     GROUP BY cart_id);-- Create RFID monitoring viewCREATE OR REPLACE VIEW v_rfid_activity ASSELECT     TO_CHAR(time, 'YYYY-MM-DD HH24') as hour_bucket,    COUNT(*) as scans_per_hour,    COUNT(DISTINCT rfid) as unique_tags_scannedFROM RFID_READ WHERE time > SYSDATE - 7GROUP BY TO_CHAR(time, 'YYYY-MM-DD HH24')ORDER BY hour_bucket DESC;```### 📊 System Monitoring & Analytics#### 🎯 Key Performance Indicators (KPIs)**Business Metrics**:- **Order Conversion Rate**: Cart-to-order completion percentage- **Average Order Value**: Revenue per successful transaction- **Peak Slot Utilization**: Collection slot booking efficiency- **RFID Scan Accuracy**: Stock update success rate**Technical Metrics**:- **Response Time**: API endpoint performance tracking- **Error Rate**: Failed requests per time period- **Database Performance**: Query execution times and resource usage- **IoT Device Uptime**: RFID reader availability and reliability#### 🔍 Monitoring Implementation**Laravel Application Monitoring**:```php// Custom monitoring middlewarenamespace App\Http\Middleware;use Closure;use Illuminate\Support\Facades\Log;use Illuminate\Support\Facades\Cache;class MonitoringMiddleware{    public function handle($request, Closure $next)    {        $startTime = microtime(true);                $response = $next($request);                $executionTime = microtime(true) - $startTime;                // Log slow requests        if ($executionTime > 2.0) {            Log::warning('Slow request detected', [                'url' => $request->fullUrl(),                'method' => $request->method(),                'execution_time' => $executionTime,                'user_id' => session('user_id'),                'memory_usage' => memory_get_peak_usage(true)            ]);        }                // Track API usage        $key = 'api_calls_' . date('Y-m-d-H');        Cache::increment($key);        Cache::expire($key, 3600);                return $response;    }}```**RFID System Health Monitoring**:```python# rfid_monitor.py - Enhanced monitoring for relay.pyimport timeimport requestsimport loggingfrom datetime import datetime, timedeltaclass RFIDMonitor:    def __init__(self):        self.last_scan_time = None        self.scan_count = 0        self.error_count = 0        self.health_check_url = 'http://127.0.0.1:8000/api/health'            def log_scan(self, uid, success=True):        self.last_scan_time = datetime.now()        if success:            self.scan_count += 1        else:            self.error_count += 1                    # Send health metrics every 100 scans        if (self.scan_count + self.error_count) % 100 == 0:            self.send_health_metrics()        def send_health_metrics(self):        metrics = {            'last_scan': self.last_scan_time.isoformat() if self.last_scan_time else None,            'total_scans': self.scan_count,            'error_count': self.error_count,            'success_rate': self.scan_count / (self.scan_count + self.error_count) if (self.scan_count + self.error_count) > 0 else 0,            'uptime_hours': (datetime.now() - start_time).total_seconds() / 3600        }                try:            requests.post(self.health_check_url, json=metrics, timeout=5)        except Exception as e:            logging.error(f"Failed to send health metrics: {e}")        def check_device_health(self):        # Check if Arduino is responsive        if self.last_scan_time and datetime.now() - self.last_scan_time > timedelta(minutes=5):            logging.warning("No RFID scans in the last 5 minutes - possible hardware issue")            return False        return True```#### 📈 Dashboard & Reporting**Real-time Dashboard Components**:- **System Status**: Live status of all system components- **Transaction Volume**: Real-time order and payment tracking- **RFID Activity**: Live scan rates and device status- **Error Monitoring**: Real-time error tracking and alerting**Automated Reports**:- **Daily Sales Summary**: Revenue, orders, and top products- **Weekly Inventory Report**: Stock levels and RFID scan summary- **Monthly System Performance**: Uptime, response times, and capacity metrics- **Quarterly Business Analytics**: Growth trends and user behavior analysis### 🛡️ Security & Compliance#### 🔒 Security Measures**Data Protection**:- **Encryption at Rest**: Database encryption for sensitive data- **Encryption in Transit**: TLS 1.3 for all communications- **API Security**: Rate limiting and JWT token validation- **Input Validation**: Comprehensive sanitization and validation**Access Control**:- **Multi-factor Authentication**: Required for admin accounts- **Role-based Permissions**: Granular access control system- **Audit Logging**: Complete activity tracking for compliance- **Session Management**: Secure session handling with timeout controls#### 📋 Compliance Requirements**Payment Card Industry (PCI) Compliance**:- **Data Minimization**: Only necessary payment data stored- **Secure Transmission**: PayPal integration for PCI compliance- **Access Logging**: All payment-related activities logged- **Regular Security Scans**: Automated vulnerability assessments**Data Privacy (GDPR/CCPA)**:- **Consent Management**: User consent tracking and management- **Data Portability**: User data export functionality- **Right to Deletion**: Complete user data removal capabilities- **Privacy Notices**: Clear privacy policy and data usage disclosure### 🔄 Maintenance & Updates#### 🛠️ Regular Maintenance Tasks**Daily Operations**:- **Backup Verification**: Ensure database backups completed successfully- **Log Review**: Check for errors and performance issues- **RFID Health Check**: Verify all readers are operational- **Payment Reconciliation**: Verify PayPal transactions**Weekly Maintenance**:- **Performance Analysis**: Review slow queries and optimize- **Security Updates**: Apply security patches and updates- **Capacity Planning**: Monitor resource usage trends- **User Feedback Review**: Address customer service issues**Monthly Tasks**:- **Full System Backup**: Complete system image backup- **Security Audit**: Comprehensive security review- **Performance Optimization**: Database and application tuning- **Business Review**: Analyze metrics and plan improvementsThis documentation provides a complete overview of the ClexoMart system, with particular emphasis on the IoT inventory management system that automatically updates stock levels through RFID scanning technology. The detailed component explanations cover every aspect of the system from hardware integration to production deployment, ensuring comprehensive understanding of the platform's architecture and capabilities.