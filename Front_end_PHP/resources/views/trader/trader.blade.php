@extends('layouts.traderapp')

@section('title', 'Trader Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/vendor.css') }}">
    <style>
        /* Make sure sidebar alignment matches with trader_product page */
        .sidebar {
            margin-top: 2rem;
            background-color: #ffffff;
            max-height: 80vh;
            padding: 4px;
        }
        
        .sidebar-menu {
            margin-top: 3rem;
            padding: 1rem;
            background-color: #ffffff;
        }
        
        .sidebar-item {
            margin-top: 10px;
            padding: 1rem 1rem;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            color: #333333;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: background-color 0.3s ease;
        }
        
        .sidebar-item:hover {
            background-color: #e0e0e0;
        }
        
        .sidebar-item.is-active {
            background-color: #A8C686;
            color: white;
        }
        
        .sidebar-item .icon {
            margin-right: 0.5rem;
        }
        
        /* Dashboard cards styling */
        .dashboard-card {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background-color: #ffffff;
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-3px);
        }
        
        .card-header {
            background-color: #FED549;
            padding: 1rem;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        
        .card-content {
            padding: 1.5rem;
        }
        
        /* Product image styling */
        .product-image {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 50%; /* Make images circular */
            border: 2px solid #e0e0e0;
        }
        
        /* Shop logo styling */
        .shop-logo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%; /* Make shop logo circular */
            border: 3px solid #FED549;
            padding: 3px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 0 auto;
            display: block;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .mb-3 {
            margin-bottom: 1rem;
        }
        
        /* Order status tag styling */
        .status-tag {
            margin-left: 5px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        /* Divider styling */
        .dropdown-divider {
            margin: 0.5rem 0;
            border-top: 1px solid #f0f0f0;
        }
        
        /* Media styling */
        .media {
            align-items: center;
            padding: 0.75rem 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .media:hover {
            background-color: #f9f9f9;
        }
        
        .media-left {
            margin-right: 1rem;
        }
        
        /* Button styling */
        .mt-4 {
            margin-top: 1rem;
        }
        
        .button.is-primary.is-light {
            background-color: #e8f5e9;
            color: #388e3c;
            border-color: transparent;
        }
        
        .button.is-primary.is-light:hover {
            background-color: #c8e6c9;
        }
        
        .button.is-success {
            background-color: #A8C686;
            border-color: transparent;
        }
        
        .button.is-success:hover {
            background-color: #97b875;
        }
        
        /* Order status colors */
        .tag.is-success {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .tag.is-warning {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .tag.is-info {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .dashboard-title {
            margin-bottom: 1.5rem;
            color: #333333;
        }
        
        .notification {
            margin-bottom: 1rem;
        }
        
        /* Improved shop details layout */
        .shop-details-form {
            padding: 0 1rem;
        }
        
        .field-label {
            font-weight: 600;
            color: #555;
        }
        
        .input, .textarea, .select select {
            box-shadow: none;
            border: 1px solid #e0e0e0;
            transition: border-color 0.2s ease;
        }
        
        .input:focus, .textarea:focus, .select select:focus {
            border-color: #FED549;
            box-shadow: 0 0 0 2px rgba(254, 213, 73, 0.25);
        }
        
        .file-cta {
            background-color: #f5f5f5;
            border-color: #e0e0e0;
        }
        
        .file-label {
            color: #666;
        }
        
        /* Shop logo placeholder */
        .shop-logo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 3px solid #FED549;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: #888;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }
        
        .shop-logo-placeholder:before {
            content: "Shop Logo";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Fix for container alignment */
        .column.is-10.is-gapless .container {
            padding: 0 1rem;
        }
    </style>
@endpush

@section('content')
    <!-- Navbar content is handled by app.blade.php, no need to repeat here -->

    <div class="columns">
        <!-- Sidebar -->
        <div class="column is-2 sidebar is-gapless">
            <div class="sidebar-menu">
                <a href="{{ route('trader') }}" class="sidebar-item is-active">
                    <span class="icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </span>
                    <span>Shop Information</span>
                </a>
                <a href="{{ route('Trader Product') }}" class="sidebar-item">
                    <span class="icon">
                        <i class="fas fa-box-open"></i>
                    </span>
                    <span>Products</span>
                </a>
                <a href="{{ route('Trader Order') }}" class="sidebar-item">
                    <span class="icon">
                        <i class="fas fa-shopping-bag"></i>
                    </span>
                    <span>Orders</span>
                </a>
                <a href="{{ route('Trader Analytics') }}" class="sidebar-item">
                    <span class="icon">
                        <i class="fas fa-chart-line"></i>
                    </span>
                    <span>Sales Analytics</span>
                </a>
                <a href="{{ route('trader.reviews') }}" class="sidebar-item">
                    <span class="icon">
                        <i class="fas fa-star"></i>
                    </span>
                    <span>Reviews</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="column is-10 is-gapless">
            <div class="dashboard-header">
                <center><h1 class="title is-3 has-text-black">Shop Information</h1></center>
            </div>

            <div class="container">
                <!-- Success and Error Notifications -->
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

                <div class="columns is-multiline">
                    <!-- Left column for shop details and recent products -->
                    <div class="column is-8">
                        <!-- Shop Details Card -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Shop Details</p>
                            </div>
                            <div class="card-content">
                                <form action="{{ route('trader.updateShop') }}" method="POST" enctype="multipart/form-data" class="shop-details-form">
                                    @csrf
                                    
                                    <!-- Display current shop logo in centered circle -->
                                    @if($shop)
                                    <div class="logo-container">
                                        <figure class="mb-3">
                                            @if(isset($shop->logo) && $shop->logo)
                                                <img src="data:{{ $shop->shop_logo_mimetype ?? 'image/jpeg' }};base64,{{ base64_encode($shop->logo) }}" 
                                                     alt="{{ $shop->shop_name }} Logo" class="shop-logo">
                                            @else
                                                <div class="shop-logo-placeholder"></div>
                                            @endif
                                        </figure>
                                        <p class="is-size-6 has-text-centered has-text-grey">{{ $shop->shop_name }}</p>
                                    </div>
                                    @endif
                                    
                                    <div class="columns is-multiline">
                                        <div class="column is-6">
                                            <div class="field">
                                                <label class="label field-label">Shop Name</label>
                                                <div class="control">
                                                    <input class="input" type="text" name="shop_name" value="{{ $shop ? $shop->shop_name : '' }}" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="column is-6">
                                            <div class="field">
                                                <label class="label field-label">Shop Category</label>
                                                <div class="control">
                                                    <div class="select is-fullwidth">
                                                        <select name="category_id">
                                                            <option value="">Select a category</option>
                                                            @foreach($categories as $category)
                                                                <option value="{{ $category->category_id }}" {{ $shop && $shop->category_id == $category->category_id ? 'selected' : '' }}>
                                                                    {{ $category->category_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="column is-12">
                                            <div class="field">
                                                <label class="label field-label">Shop Description</label>
                                                <div class="control">
                                                    <textarea class="textarea" name="shop_description" rows="3">{{ $shop ? $shop->shop_description : '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="column is-12">
                                            <div class="field">
                                                <label class="label field-label">Update Shop Logo</label>
                                                <div class="file has-name is-fullwidth">
                                                    <label class="file-label">
                                                        <input class="file-input" type="file" name="shop_logo">
                                                        <span class="file-cta">
                                                            <span class="file-icon">
                                                                <i class="fas fa-upload"></i>
                                                            </span>
                                                            <span class="file-label">
                                                                Choose a file…
                                                            </span>
                                                        </span>
                                                        <span class="file-name">
                                                            No file selected
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="field mt-4 has-text-centered">
                                        <div class="control">
                                            <button type="submit" class="button is-success">Update Shop Details</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Recent Products Card -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Recent Products</p>
                            </div>
                            <div class="card-content">
                                @forelse($recentProducts as $product)
                                    <div class="media">
                                        <div class="media-left">
                                            <figure class="image is-64x64">
                                                @if(isset($product->product_id))
                                                    <img src="{{ route('trader.product.image', $product->product_id) }}" 
                                                         alt="{{ $product->product_name }}" class="product-image">
                                                @elseif(isset($product->product_image_filename) && $product->product_image_filename)
                                                    <img src="{{ asset('images/products/' . $product->product_image_filename) }}" 
                                                         alt="{{ $product->product_name }}" class="product-image">
                                                @else
                                                    <img src="{{ asset('images/default-product.jpg') }}" 
                                                         alt="Default product image" class="product-image">
                                                @endif
                                            </figure>
                                        </div>
                                        <div class="media-content">
                                            <p class="title is-6">{{ $product->product_name }}</p>
                                            <p class="subtitle is-6">
                                                ${{ number_format((float)$product->unit_price, 2) }} • 
                                                Stock: {{ $product->stock }}
                                            </p>
                                        </div>
                                    </div>
                                    <hr class="dropdown-divider">
                                @empty
                                    <p class="has-text-centered is-italic">No recent products to display</p>
                                @endforelse
                                
                                @if(count($recentProducts) > 0)
                                    <div class="has-text-centered mt-4">
                                        <a href="{{ route('Trader Product') }}" class="button is-small is-primary is-light">
                                            <span class="icon">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                            <span>View All Products</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right column for recent orders -->
                    <div class="column is-4">
                        <!-- Recent Orders Card -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Recent Orders</p>
                            </div>
                            <div class="card-content">
                                @forelse($recentOrders as $order)
                                    <div class="media">
                                        <div class="media-content">
                                            <p class="title is-6">
                                                Order #{{ $order->order_id ?? 'Unknown' }}
                                                <span class="tag status-tag {{ strtolower($order->order_status) === 'completed' ? 'is-success' : (strtolower($order->order_status) === 'pending' ? 'is-warning' : 'is-info') }}">
                                                    {{ ucfirst($order->order_status ?? 'Pending') }}
                                                </span>
                                            </p>
                                            <p class="subtitle is-6">
                                                Date: {{ \Carbon\Carbon::parse($order->order_date)->format('M d, Y') }}
                                            </p>
                                            <p class="subtitle is-6">
                                                Amount: ${{ number_format((float)$order->payment_amount, 2) }} • Items: {{ $order->item_count }}
                                            </p>
                                        </div>
                                    </div>
                                    <hr class="dropdown-divider">
                                @empty
                                    <p class="has-text-centered is-italic">No recent orders to display</p>
                                @endforelse
                                
                                @if(count($recentOrders) > 0)
                                    <div class="has-text-centered mt-4">
                                        <a href="{{ route('Trader Order') }}" class="button is-small is-primary is-light">
                                            <span class="icon">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                            <span>View All Orders</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/trader.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add a class to the body when fully loaded
            document.body.classList.add('page-loaded');
            
            // Apply styling to product images
            document.querySelectorAll('.product-image, .shop-logo').forEach(img => {
                // Apply better styling to images
                img.style.objectFit = 'cover';
                img.style.border = '2px solid #eee';
                img.style.padding = '3px';
                img.style.backgroundColor = '#f9f9f9';
                
                // Handle image loading errors with improved error handling
                img.addEventListener('error', function() {
                    console.log('Image failed to load:', this.src);
                    
                    if (this.classList.contains('shop-logo')) {
                        // Replace with default shop logo
                        this.src = '{{ asset("images/default-shop.jpg") }}';
                        // If that fails too, create an SVG placeholder
                        this.onerror = function() {
                            this.outerHTML = '<div class="shop-logo-placeholder"></div>';
                        };
                    } else {
                        // Replace with default product image
                        this.src = '{{ asset("images/default-product.jpg") }}';
                        // If that fails too, create an SVG placeholder
                        this.onerror = function() {
                            const svgPlaceholder = document.createElement('div');
                            svgPlaceholder.className = 'product-image';
                            svgPlaceholder.style.display = 'flex';
                            svgPlaceholder.style.alignItems = 'center';
                            svgPlaceholder.style.justifyContent = 'center';
                            svgPlaceholder.style.backgroundColor = '#f0f0f0';
                            svgPlaceholder.style.color = '#999';
                            svgPlaceholder.style.fontSize = '10px';
                            svgPlaceholder.textContent = 'No Image';
                            this.parentNode.replaceChild(svgPlaceholder, this);
                        };
                    }
                });
            });
            
            // File input name display
            const fileInput = document.querySelector('.file-input');
            const fileName = document.querySelector('.file-name');
            
            if (fileInput && fileName) {
                fileInput.addEventListener('change', function() {
                    if (fileInput.files.length > 0) {
                        fileName.textContent = fileInput.files[0].name;
                        
                        // Show preview if it's an image
                        const fileType = fileInput.files[0].type;
                        if (fileType.startsWith('image/')) {
                            console.log('Selected image file:', fileInput.files[0].name);
                            
                            // Optional: Create a preview if needed
                            // This will be useful for the user to see what they've selected
                            // But requires saving the form to see the actual update
                        }
                    } else {
                        fileName.textContent = 'No file selected';
                    }
                });
            }
            
            // Add hover effects
            document.querySelectorAll('.dashboard-card .media').forEach(media => {
                media.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f9f9f9';
                    this.style.borderRadius = '4px';
                });
                
                media.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'transparent';
                });
            });
        });
    </script>
@endpush