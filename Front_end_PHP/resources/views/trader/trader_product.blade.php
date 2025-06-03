@extends('layouts.traderapp')

@section('title', 'Trader Product')

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
                <center><h1 class="title is-3 has-text-black">Product Management</h1></center>
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
                
                <!-- Add New Product Form -->
                <div class="dashboard-card add-product-form">
                    <div class="card-header">
                        <p class="has-text-weight-bold">{{ isset($product) ? 'Edit Product' : 'Add New Product' }}</p>
                    </div>
                    <div class="card-content">
                        @if(isset($product))
                            <form action="{{ route('trader.products.update', $product->product_id) }}" method="POST" enctype="multipart/form-data">
                            @method('PUT')
                        @else
                            <form action="{{ route('trader.products.store') }}" method="POST" enctype="multipart/form-data">
                        @endif
                            @csrf
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
                                                    No file selected
                                                </span>
                                            </label>
                                        </div>
                                        <div class="has-text-centered mt-3">
                                            @php
                                                $previewSrc = '';
                                                $imageClass = 'product-image-preview is-hidden';
                                                
                                                if (isset($product) && $product->product_id) {
                                                    $previewSrc = route('trader.product.image', $product->product_id);
                                                    $imageClass = 'product-image-preview';
                                                }
                                                
                                                $fallbackSrc = asset('images/default.png');
                                            @endphp
                                            
                                            <img id="image-preview" src="{{ $previewSrc }}" 
                                                 alt="{{ isset($product) ? $product->product_name : 'Preview will appear here' }}" 
                                                 class="{{ $imageClass }}" 
                                                 onerror="if(this.src != '{{ $fallbackSrc }}' && this.src != '') this.src='{{ $fallbackSrc }}';">
                                        </div>
                                    </div>
                                </div>
                                <div class="column is-8">
                                    <div class="field">
                                        <label class="label">Product Name</label>
                                        <div class="control">
                                            <input class="input" type="text" name="product_name" placeholder="Enter product name" value="{{ isset($product) ? $product->product_name : old('product_name') }}" required>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label class="label">Description</label>
                                        <div class="control">
                                            <textarea class="textarea" name="description" placeholder="Enter product description" required>{{ isset($product) ? $product->description : old('description') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="columns">
                                        <div class="column is-6">
                                            <div class="field">
                                                <label class="label">Price ($)</label>
                                                <div class="control">
                                                    <input class="input" type="number" name="unit_price" placeholder="0.00" step="0.01" value="{{ isset($product) ? $product->unit_price : old('unit_price') }}" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="column is-6">
                                            <div class="field">
                                                <label class="label">Stock Quantity</label>
                                                <div class="control">
                                                    <input class="input" type="number" name="stock" placeholder="0" value="{{ isset($product) ? $product->stock : old('stock') }}" required>
                                                </div>
                                            </div>
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
                                                {{ isset($product) ? 'Update Product' : 'Add Product' }}
                                            </button>
                                        </div>
                                        @if(isset($product))
                                            <div class="control">
                                                <a href="{{ route('Trader Product') }}" class="button is-light">Cancel</a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- IOT Inventory Section -->
                <div class="dashboard-card mb-5">
                    <div class="card-header">
                        <p class="has-text-weight-bold">IOT Inventory Management</p>
                    </div>
                    <div class="card-content">
                        <div class="content">
                            <p class="mb-3">Use RFID scanning to quickly manage your inventory:</p>
                            
                            <div class="columns">
                                <div class="column is-6">
                                    <div class="box has-background-light">
                                        <h4 class="title is-5">Add Stock</h4>
                                        <p class="mb-4">Scan RFID tags to add stock to existing products.</p>
                                        <button class="button is-info is-fullwidth" id="add-stock-btn">
                                            <span class="icon">
                                                <i class="fas fa-plus-circle"></i>
                                            </span>
                                            <span>Add Stock via RFID</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="column is-6">
                                    <div class="box has-background-light">
                                        <h4 class="title is-5">Add Product</h4>
                                        <p class="mb-4">Scan RFID tags to create new products quickly.</p>
                                        <button class="button is-success is-fullwidth" id="add-product-btn">
                                            <span class="icon">
                                                <i class="fas fa-tag"></i>
                                            </span>
                                            <span>Add Product via RFID</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product List -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="level">
                            <div class="level-left">
                                <p class="has-text-weight-bold">Your Products ({{ count($products) }})</p>
                            </div>
                            <div class="level-right">
                                <div class="field">
                                    <div class="control has-icons-left">
                                        <input class="input" type="text" id="product-filter" placeholder="Filter products...">
                                        <span class="icon is-small is-left">
                                            <i class="fas fa-filter"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <table class="table is-fullwidth is-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>RFID UID</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $prod)
                                <tr class="product-row" data-name="{{ strtolower($prod->product_name) }}">
                                    <td>
                                        <div class="media">
                                            <div class="media-left">
                                                @php
                                                    $hasImage = false;
                                                    
                                                    // Use direct image URL instead of inline base64
                                                    $imageSrc = route('trader.product.image', $prod->product_id);
                                                    
                                                    // Fallback path
                                                    $fallbackSrc = asset('images/default.png');
                                                @endphp
                                                
                                                <img src="{{ $imageSrc }}" class="product-image" alt="{{ $prod->product_name }}" onerror="this.src='{{ $fallbackSrc }}'">
                                            </div>
                                            <div class="media-content">
                                                <p><strong>{{ $prod->product_name }}</strong></p>
                                                <p class="is-size-7">{{ Str::limit($prod->description, 50) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>${{ number_format($prod->unit_price, 2) }}</td>
                                    <td>{{ $prod->stock }}</td>
                                    <td>
                                        @if($prod->stock > 0)
                                            <span class="tag is-success">In Stock</span>
                                        @else
                                            <span class="tag is-danger">Out of Stock</span>
                                        @endif
                                    </td>
                                    <td>{{ $prod->rfid_uid ?? 'Not assigned' }}</td>
                                    <td class="product-actions">
                                        <div class="buttons are-small">
                                            <a href="{{ route('trader.products.edit', $prod->product_id) }}" class="button is-info" title="Edit">
                                                <span class="icon">
                                                    <i class="fas fa-edit"></i>
                                                </span>
                                            </a>
                                            {{-- <a href="{{ route('trader.image.fix') }}?debug_id={{ $prod->product_id }}" class="button is-small is-warning" title="Fix image"> --}}
                                            {{--     <span class="icon"> --}}
                                            {{--         <i class="fas fa-tools"></i> --}}
                                            {{--     </span> --}}
                                            {{-- </a> --}}
                                            <form action="{{ route('trader.products.delete', $prod->product_id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="button is-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                                    <span class="icon">
                                                        <i class="fas fa-trash"></i>
                                                    </span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="has-text-centered">No products found. Add your first product above!</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                    imagePreview.classList.add('is-hidden');
                }
            });
            
            // Product filtering functionality
            const filterInput = document.querySelector('#product-filter');
            const productRows = document.querySelectorAll('.product-row');
            
            filterInput.addEventListener('keyup', function() {
                const searchText = filterInput.value.toLowerCase();
                
                productRows.forEach(row => {
                    const productName = row.getAttribute('data-name');
                    if (productName.includes(searchText)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // Basic click handlers for IOT Inventory buttons
            const addStockBtn = document.querySelector('#add-stock-btn');
            const addProductBtn = document.querySelector('#add-product-btn');
            
            // Simple click handlers without popups
            if (addStockBtn) {
                addStockBtn.addEventListener('click', function() {
                    console.log('Add Stock via RFID clicked');
                    
                    // Create and show the RFID scanning modal
                    const modalHtml = `
                        <div class="modal is-active" id="rfid-scan-modal">
                            <div class="modal-background"></div>
                            <div class="modal-card">
                                <header class="modal-card-head">
                                    <p class="modal-card-title">RFID Scanner - Add Stock</p>
                                    <button class="delete" aria-label="close"></button>
                                </header>
                                <section class="modal-card-body">
                                    <div class="content">
                                        <p>Scan your RFID tags to add stock. The relay script is running in the background.</p>
                                        <div class="notification is-info is-light mb-4">
                                            <p><strong>Status:</strong> <span id="rfid-status">Starting scanner...</span></p>
                                        </div>
                                        
                                        <h5 class="title is-5 mb-2">Scanned Tags</h5>
                                        <div class="tags-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px;">
                                            <div id="scanned-tags">
                                                <p class="has-text-grey">No tags scanned yet</p>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                                <footer class="modal-card-foot">
                                    <button class="button is-danger" id="stop-scanning">Stop Scanning</button>
                                    <button class="button" id="close-modal">Close</button>
                                </footer>
                            </div>
                        </div>
                    `;
                    
                    // Add modal to the DOM
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    
                    // Get modal elements
                    const modal = document.getElementById('rfid-scan-modal');
                    const closeBtn = modal.querySelector('.delete');
                    const closeModalBtn = document.getElementById('close-modal');
                    const stopScanningBtn = document.getElementById('stop-scanning');
                    const rfidStatus = document.getElementById('rfid-status');
                    const scannedTags = document.getElementById('scanned-tags');
                    
                    // Function to close the modal
                    const closeModal = () => {
                        modal.remove();
                        // Stop polling when modal is closed
                        clearInterval(pollingInterval);
                    };
                    
                    // Add event listeners for closing the modal
                    closeBtn.addEventListener('click', closeModal);
                    closeModalBtn.addEventListener('click', closeModal);
                    
                    // Call server endpoint to run relay.py
                    fetch('/trader/rfid/run-relay', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            rfidStatus.textContent = 'Scanner running. Ready to scan tags.';
                            rfidStatus.parentElement.classList.remove('is-info');
                            rfidStatus.parentElement.classList.add('is-success');
                        } else {
                            rfidStatus.textContent = 'Error: ' + data.message;
                            rfidStatus.parentElement.classList.remove('is-info');
                            rfidStatus.parentElement.classList.add('is-danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        rfidStatus.textContent = 'Error starting scanner';
                        rfidStatus.parentElement.classList.remove('is-info');
                        rfidStatus.parentElement.classList.add('is-danger');
                    });
                    
                    // Keep track of scanned UIDs to avoid duplicates in the UI
                    const scannedUids = new Set();
                    
                    // Poll for new RFID scans
                    let lastTimestamp = new Date().toISOString();
                    const pollingInterval = setInterval(() => {
                        fetch('/api/rfid/recent?since=' + encodeURIComponent(lastTimestamp))
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.scans && data.scans.length > 0) {
                                    // Update the last timestamp
                                    if (data.scans.length > 0) {
                                        lastTimestamp = data.scans[data.scans.length - 1].time;
                                    }
                                    
                                    // Clear the "No tags scanned yet" message if this is the first scan
                                    if (scannedTags.innerHTML.includes('No tags scanned yet')) {
                                        scannedTags.innerHTML = '';
                                    }
                                    
                                    // Add new scans to the UI - show all scans including duplicates
                                    data.scans.forEach(scan => {
                                        // Create a new element for each scan, even duplicates
                                        const tagElement = document.createElement('div');
                                        tagElement.className = 'notification is-light is-success mb-2';
                                        
                                        // Check if this is a duplicate scan
                                        const isDuplicate = scannedUids.has(scan.rfid);
                                        if (isDuplicate) {
                                            // Mark duplicates with a different style
                                            tagElement.classList.remove('is-success');
                                            tagElement.classList.add('is-info');
                                        } else {
                                            // Add to set of seen UIDs
                                            scannedUids.add(scan.rfid);
                                        }
                                        
                                        // Fetch product info for this RFID
                                        fetch(`/api/rfid/${scan.rfid}/product`)
                                            .then(response => response.json())
                                            .then(productData => {
                                                const productName = productData.success ? productData.product_name : 'Unknown Product';
                                                
                                                tagElement.innerHTML = `
                                                    <div class="columns is-mobile">
                                                        <div class="column">
                                                            <p><strong>UID:</strong> ${scan.rfid}</p>
                                                            <p><strong>Product:</strong> ${productName}</p>
                                                            <p><small>Scanned at: ${new Date(scan.time).toLocaleTimeString()}</small></p>
                                                        </div>
                                                        <div class="column is-narrow">
                                                            ${isDuplicate ? 
                                                                '<span class="tag is-info is-medium">+1 Stock</span>' : 
                                                                '<span class="tag is-success is-medium">New Item</span>'
                                                            }
                                                        </div>
                                                    </div>
                                                `;
                                            })
                                            .catch(error => {
                                                console.error('Error fetching product info:', error);
                                                tagElement.innerHTML = `
                                                    <div class="columns is-mobile">
                                                        <div class="column">
                                                            <p><strong>UID:</strong> ${scan.rfid}</p>
                                                            <p><strong>Product:</strong> Unable to fetch product info</p>
                                                            <p><small>Scanned at: ${new Date(scan.time).toLocaleTimeString()}</small></p>
                                                        </div>
                                                        <div class="column is-narrow">
                                                            ${isDuplicate ? 
                                                                '<span class="tag is-info is-medium">+1 Stock</span>' : 
                                                                '<span class="tag is-success is-medium">New Item</span>'
                                                            }
                                                        </div>
                                                    </div>
                                                `;
                                            });
                                        
                                        scannedTags.appendChild(tagElement);
                                        
                                        // Scroll to the bottom to show the latest scan
                                        scannedTags.parentElement.scrollTop = scannedTags.parentElement.scrollHeight;
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error polling for RFID scans:', error);
                            });
                    }, 1000); // Poll every second
                    
                    // Stop scanning button handler
                    stopScanningBtn.addEventListener('click', function() {
                        fetch('/trader/rfid/stop-relay', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            clearInterval(pollingInterval);
                            rfidStatus.textContent = 'Scanner stopped';
                            rfidStatus.parentElement.classList.remove('is-success');
                            rfidStatus.parentElement.classList.add('is-warning');
                            stopScanningBtn.disabled = true;
                        })
                        .catch(error => {
                            console.error('Error stopping scanner:', error);
                        });
                    });
                });
            }
            
            if (addProductBtn) {
                addProductBtn.addEventListener('click', function() {
                    console.log('Add Product via RFID clicked');
                });
            }
        });
    </script>
@endpush