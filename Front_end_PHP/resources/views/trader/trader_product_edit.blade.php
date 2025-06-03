@extends('layouts.traderapp')

@section('title', 'Edit Product')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/vendor_dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/vendor_product.css') }}">
    <style>
        .product-image {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .product-image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .notification {
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="columns">
        <!-- Sidebar -->
        <div class="column is-2 sidebar is-gapless">
            <div class="sidebar-menu">
                <a href="{{ route('trader') }}" class="sidebar-item">
                    <span class="icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </span>
                    <span>Shop Information</span>
                </a>
                <a href="{{ route('Trader Product') }}" class="sidebar-item is-active">
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
                <a href="{{ route('Trader Analytics') }}" class="sidebar-item {{ request()->routeIs('Trader Analytics') || request()->routeIs('trader.sales') ? 'is-active' : '' }}">
                    <span class="icon">
                        <i class="fas fa-chart-line"></i>
                    </span>
                    <span>Sales Analytics</span>
                </a>
                <a href="{{ route('trader.reviews') }}" class="sidebar-item {{ request()->routeIs('trader.reviews') ? 'is-active' : '' }}">
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
                <center><h1 class="title is-3 has-text-black">Edit Product</h1></center>
            </div>

            <div class="container">
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
                
                @if($errors->any())
                    <div class="notification is-danger">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <!-- Edit Product Form -->
                <div class="dashboard-card add-product-form">
                    <div class="card-header">
                        <p class="has-text-weight-bold">Edit Product: {{ $product['product_name'] }}</p>
                    </div>
                    <div class="card-content">
                        <form action="{{ route('trader.products.update', $product['product_id']) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="columns">
                                <div class="column is-4">
                                    <div class="field">
                                        <label class="label">Product Image</label>
                                        <div class="file has-name is-boxed">
                                            <label class="file-label">
                                                <input class="file-input" type="file" name="product_image" id="product-image">
                                                <span class="file-cta">
                                                    <span class="file-icon">
                                                        <i class="fas fa-upload"></i>
                                                    </span>
                                                    <span class="file-label">
                                                        Choose an image...
                                                    </span>
                                                </span>
                                                <span class="file-name" id="file-name">
                                                    {{ $product['product_image_filename'] ?? 'No file selected' }}
                                                </span>
                                            </label>
                                        </div>
                                        <div class="has-text-centered mt-3">
                                            @php
                                                $previewSrc = route('trader.product.image', $product['product_id']);
                                                $imageClass = 'product-image-preview';
                                                $fallbackSrc = asset('images/default.png');
                                            @endphp
                                            
                                            <img id="image-preview" src="{{ $previewSrc }}" 
                                                 alt="{{ $product['product_name'] }}" 
                                                 class="{{ $imageClass }}" 
                                                 onerror="if(this.src != '{{ $fallbackSrc }}' && this.src != '') this.src='{{ $fallbackSrc }}';">
                                        </div>
                                    </div>
                                </div>
                                <div class="column is-8">
                                    <div class="field">
                                        <label class="label">Product Name</label>
                                        <div class="control">
                                            <input class="input" type="text" name="product_name" placeholder="Enter product name" value="{{ $product['product_name'] }}" required>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label class="label">Description</label>
                                        <div class="control">
                                            <textarea class="textarea" name="description" placeholder="Enter product description" required>{{ $product['product_description'] }}</textarea>
                                        </div>
                                    </div>
                                    <div class="columns">
                                        <div class="column is-6">
                                            <div class="field">
                                                <label class="label">Price ($)</label>
                                                <div class="control">
                                                    <input class="input" type="number" name="unit_price" placeholder="0.00" step="0.01" value="{{ $product['unit_price'] }}" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="column is-6">
                                            <div class="field">
                                                <label class="label">Stock Quantity</label>
                                                <div class="control">
                                                    <input class="input" type="number" name="stock" placeholder="0" value="{{ $product['stock'] }}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label class="label">RFID UID <span class="has-text-grey is-size-7">(Product Identifier Tag)</span></label>
                                        <div class="control">
                                            <input class="input" type="text" name="rfid_uid" placeholder="Enter product RFID tag UID" value="{{ $product['rfid_uid'] ?? '' }}" maxlength="32">
                                            <p class="help">Enter the unique RFID tag identifier for inventory tracking</p>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label class="label">Category</label>
                                        <div class="control">
                                            @if($shop && $shop->category)
                                                <input type="text" class="input" value="{{ $shop->category->category_name }}" readonly>
                                                <input type="hidden" name="category_id" value="{{ $shop->category_id }}">
                                                <p class="help">Your shop is associated with this category</p>
                                            @else
                                                <div class="notification is-warning">
                                                    Please set up your shop category in the Shop Settings page first.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="field is-grouped">
                                        <div class="control">
                                            <button type="submit" class="button is-primary">
                                                Update Product
                                            </button>
                                        </div>
                                        <div class="control">
                                            <a href="{{ route('Trader Product') }}" class="button is-light">Cancel</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview functionality
            const fileInput = document.querySelector('#product-image');
            const fileName = document.querySelector('#file-name');
            const imagePreview = document.querySelector('#image-preview');
            
            fileInput.addEventListener('change', function() {
                if (fileInput.files.length > 0) {
                    fileName.textContent = fileInput.files[0].name;
                    
                    // Show image preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.remove('is-hidden');
                    };
                    reader.readAsDataURL(fileInput.files[0]);
                } else {
                    fileName.textContent = 'No file selected';
                    // Don't hide the preview if we already have a product image
                }
            });
        });
    </script>
@endpush 