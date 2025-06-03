<?php
// Database connection settings
$host = 'localhost';
$port = '1521';
$sid = 'orcl';
$username = 'your_username';
$password = 'your_password';

// Try to get connection settings from Laravel config
$laravelConfig = __DIR__ . '/../config/database.php';
if (file_exists($laravelConfig)) {
    $config = require $laravelConfig;
    $oracleConfig = $config['connections']['oracle'] ?? null;
    if ($oracleConfig) {
        $host = $oracleConfig['host'] ?? $host;
        $port = $oracleConfig['port'] ?? $port;
        $sid = $oracleConfig['database'] ?? $sid;
        $username = $oracleConfig['username'] ?? $username;
        $password = $oracleConfig['password'] ?? $password;
    }
}

// Build connection string
$connectionString = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SID=$sid)))";

// Connect to Oracle
try {
    $conn = oci_connect($username, $password, $connectionString);
    if (!$conn) {
        $e = oci_error();
        throw new Exception($e['message']);
    }
    
    echo "<h1>Product Image Diagnostics</h1>";
    
    // Query for product data
    $sql = "SELECT product_id, product_name, dbms_lob.getlength(PRODUCT_image) as img_length, 
            PRODUCT_IMAGE_MIMETYPE, PRODUCT_IMAGE_FILENAME FROM PRODUCT";
    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);
    
    echo "<h2>Product Image Data</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Image Length</th><th>MIME Type</th><th>Filename</th><th>Test Image</th></tr>";
    
    $count = 0;
    while (($row = oci_fetch_assoc($stmt)) !== false) {
        echo "<tr>";
        echo "<td>{$row['PRODUCT_ID']}</td>";
        echo "<td>{$row['PRODUCT_NAME']}</td>";
        echo "<td>{$row['IMG_LENGTH']}</td>";
        echo "<td>{$row['PRODUCT_IMAGE_MIMETYPE']}</td>";
        echo "<td>{$row['PRODUCT_IMAGE_FILENAME']}</td>";
        
        // Fetch the actual image
        if ($row['IMG_LENGTH'] > 0) {
            $img_sql = "SELECT PRODUCT_image FROM PRODUCT WHERE product_id = :id";
            $img_stmt = oci_parse($conn, $img_sql);
            oci_bind_by_name($img_stmt, ":id", $row['PRODUCT_ID']);
            oci_execute($img_stmt);
            
            if (($img_row = oci_fetch_assoc($img_stmt)) !== false) {
                $lob = $img_row['PRODUCT_IMAGE']->load();
                $image_data = base64_encode($lob);
                $mime = $row['PRODUCT_IMAGE_MIMETYPE'] ?: 'image/jpeg';
                echo "<td><img src='data:{$mime};base64,{$image_data}' width='64' height='64' style='object-fit:cover;'/></td>";
            } else {
                echo "<td>Error fetching LOB</td>";
            }
        } else {
            echo "<td>No image</td>";
        }
        
        echo "</tr>";
        $count++;
        
        // Limit to 10 rows
        if ($count >= 10) break;
    }
    
    echo "</table>";
    
    // Test direct DB access for the first product
    echo "<h2>Testing Direct DB Access</h2>";
    
    $test_sql = "SELECT product_id FROM PRODUCT WHERE ROWNUM <= 1";
    $test_stmt = oci_parse($conn, $test_sql);
    oci_execute($test_stmt);
    
    if (($test_row = oci_fetch_assoc($test_stmt)) !== false) {
        $product_id = $test_row['PRODUCT_ID'];
        
        echo "<p>Testing image fetch for product ID: {$product_id}</p>";
        
        $img_sql = "SELECT PRODUCT_image FROM PRODUCT WHERE product_id = :id";
        $img_stmt = oci_parse($conn, $img_sql);
        oci_bind_by_name($img_stmt, ":id", $product_id);
        oci_execute($img_stmt);
        
        if (($img_row = oci_fetch_assoc($img_stmt)) !== false) {
            $lob = $img_row['PRODUCT_IMAGE'];
            
            echo "<p>LOB object type: " . gettype($lob) . "</p>";
            
            if (is_object($lob)) {
                $image_data = $lob->load();
                $size = strlen($image_data);
                echo "<p>Image size: {$size} bytes</p>";
                
                if ($size > 0) {
                    $encoded = base64_encode($image_data);
                    echo "<p><img src='data:image/jpeg;base64,{$encoded}' width='100' height='100' style='object-fit:contain;'/></p>";
                } else {
                    echo "<p>Image data is empty</p>";
                }
            } else {
                echo "<p>LOB is not an object</p>";
            }
        } else {
            echo "<p>No image data found</p>";
        }
    }
    
    // Close the connection
    oci_close($conn);
    
} catch (Exception $e) {
    echo "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
}
?> 