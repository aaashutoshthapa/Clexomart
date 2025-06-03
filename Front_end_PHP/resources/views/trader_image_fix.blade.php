<!DOCTYPE html>
<html>
<head>
    <title>Product Image Fix</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <style>
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px;
        }
        .product-image {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
        }
        .section { 
            padding: 1.5rem; 
            margin-bottom: 1.5rem;
            border: 1px solid #eee;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title is-2">Product Image Fix Tool</h1>
        
        <div class="notification is-info">
            This tool helps diagnose and fix product image issues.
        </div>
        
        @if(session('success'))
            <div class="notification is-success">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="notification is-danger">
                {{ session('error') }}
            </div>
        @endif
        
        <div class="section">
            <h2 class="title is-4">Select Product</h2>
            <form action="{{ route('trader.image.fix.product') }}" method="POST">
                @csrf
                <div class="field">
                    <label class="label">Product</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="product_id">
                                @foreach($products as $prod)
                                    <option value="{{ $prod->product_id }}" {{ $selectedProduct && $selectedProduct->product_id == $prod->product_id ? 'selected' : '' }}>
                                        {{ $prod->product_name }} (ID: {{ $prod->product_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <div class="control">
                        <button type="submit" class="button is-info">Load Product</button>
                    </div>
                </div>
            </form>
        </div>
        
        @if($selectedProduct)
            <div class="section">
                <h2 class="title is-4">Product Details</h2>
                <div class="columns">
                    <div class="column is-half">
                        <p><strong>ID:</strong> {{ $selectedProduct->product_id }}</p>
                        <p><strong>Name:</strong> {{ $selectedProduct->product_name }}</p>
                        <p><strong>PRODUCT_image exists:</strong> {{ $selectedProduct->PRODUCT_image ? 'Yes' : 'No' }}</p>
                        <p><strong>PRODUCT_image length:</strong> {{ $selectedProduct->PRODUCT_image ? strlen($selectedProduct->PRODUCT_image) : 0 }} bytes</p>
                        <p><strong>PRODUCT_IMAGE_MIMETYPE:</strong> {{ $selectedProduct->PRODUCT_IMAGE_MIMETYPE ?? 'Not set' }}</p>
                        <p><strong>PRODUCT_IMAGE_FILENAME:</strong> {{ $selectedProduct->PRODUCT_IMAGE_FILENAME ?? 'Not set' }}</p>
                    </div>
                    <div class="column is-half">
                        <h3 class="title is-5">Current Image</h3>
                        @if($selectedProduct->PRODUCT_image && strlen($selectedProduct->PRODUCT_image) > 10)
                            <img src="data:{{ $selectedProduct->PRODUCT_IMAGE_MIMETYPE ?? 'image/jpeg' }};base64,{{ base64_encode($selectedProduct->PRODUCT_image) }}" class="product-image" alt="{{ $selectedProduct->product_name }}">
                        @else
                            <p>No image data available</p>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2 class="title is-4">Upload New Image</h2>
                <form action="{{ route('trader.image.fix.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $selectedProduct->product_id }}">
                    
                    <div class="field">
                        <label class="label">Select Image</label>
                        <div class="file has-name">
                            <label class="file-label">
                                <input class="file-input" type="file" name="product_image" id="product-image">
                                <span class="file-cta">
                                    <span class="file-icon">
                                        <i class="fas fa-upload"></i>
                                    </span>
                                    <span class="file-label">
                                        Choose a file…
                                    </span>
                                </span>
                                <span class="file-name" id="file-name">
                                    No file selected
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="field">
                        <div class="control">
                            <button type="submit" class="button is-primary">Upload Image</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="section">
                <h2 class="title is-4">Apply Default Image</h2>
                <form action="{{ route('trader.image.fix.default') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $selectedProduct->product_id }}">
                    <div class="field">
                        <div class="control">
                            <button type="submit" class="button is-warning">Apply Default Image</button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
        
        <div class="has-text-centered mt-5">
            <a href="{{ route('Trader Product') }}" class="button is-link">Return to Product Management</a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.querySelector('#product-image');
            const fileName = document.querySelector('#file-name');
            
            fileInput?.addEventListener('change', function() {
                if (fileInput.files.length > 0) {
                    fileName.textContent = fileInput.files[0].name;
                } else {
                    fileName.textContent = 'No file selected';
                }
            });
        });
    </script>
</body>
</html> 