<!DOCTYPE html>
<html>
<head>
    <title>Debug Product Images</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .product {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .product-image {
            width: 200px;
            height: 200px;
            object-fit: contain;
            border: 1px solid #ddd;
            padding: 5px;
            margin-bottom: 10px;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow: auto;
            max-height: 300px;
        }
    </style>
</head>
<body>
    <h1>Debug Product Images</h1>
    
    @foreach($products as $product)
    <div class="product">
        <h2>{{ $product->product_name }}</h2>
        
        <h3>Database Field Debug:</h3>
        <ul>
            <li>All properties: {{ implode(', ', array_keys($product->getAttributes())) }}</li>
            <li>Has direct PRODUCT_image field: {{ isset($product->PRODUCT_image) ? 'Yes' : 'No' }}</li>
            <li>PRODUCT_image is null: {{ is_null($product->PRODUCT_image) ? 'Yes' : 'No' }}</li>
            <li>PRODUCT_image is empty: {{ empty($product->PRODUCT_image) ? 'Yes' : 'No' }}</li>
            <li>PRODUCT_image length: {{ isset($product->PRODUCT_image) ? strlen($product->PRODUCT_image) : 'N/A' }} bytes</li>
            <li>PRODUCT_IMAGE_MIMETYPE: {{ $product->PRODUCT_IMAGE_MIMETYPE ?? 'Not set' }}</li>
        </ul>
        
        <h3>Image Display Attempt:</h3>
        @if($product->PRODUCT_image)
            <img src="data:{{ $product->PRODUCT_IMAGE_MIMETYPE ?? 'image/jpeg' }};base64,{{ base64_encode($product->PRODUCT_image) }}" class="product-image" alt="{{ $product->product_name }}">
        @else
            <p>No image data available</p>
        @endif
        
        <h3>Raw Data (first 100 chars):</h3>
        @if($product->PRODUCT_image)
            <pre>{{ substr(bin2hex($product->PRODUCT_image), 0, 100) }}</pre>
        @else
            <p>No image data available</p>
        @endif
        
        <hr>
        <p>Product ID: {{ $product->product_id }}</p>
    </div>
    @endforeach
    
    <p><a href="{{ route('trader') }}">Back to Dashboard</a></p>
</body>
</html> 