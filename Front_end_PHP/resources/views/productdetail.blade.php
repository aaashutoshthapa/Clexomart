@extends('layouts.app')

@section('title', '{{ $product->product_name }}')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/product_detail.css') }}">
@endpush

@section('content')
    <main class="container">
        <section class="section product-detail">
            <h1 class="title">{{ $product->product_name }}</h1>
          
            <div class="columns is-variable is-8 is-multiline">
                <!-- Product Image Section -->
                <div class="column is-6">
                    <div class="columns is-vcentered is-mobile">
                        <div class="column is-2 has-text-centered">
                            <button class="nav-btn" aria-label="Previous image">❮</button>
                        </div>
                        <div class="column is-8">
                            <div class="product-card-image">
                                @php
                                    // Use direct image URL instead of inline base64
                                    $imageSrc = route('trader.product.image', $product->product_id);
                                    
                                    // Fallback path
                                    $fallbackSrc = asset('images/default.png');
                                @endphp
                                
                                <img src="{{ $imageSrc }}" alt="{{ $product->product_name }}" onerror="this.src='{{ $fallbackSrc }}'">
                            </div>
                        </div>
                        <div class="column is-2 has-text-centered">
                            <button class="nav-btn" aria-label="Next image">❯</button>
                        </div>
                    </div>
                </div>
          
                <!-- Product Details Section -->
                <div class="column is-6">
                    <h2 class="product-title">{{ $product->product_name }}</h2>
                    <h3 class="product-price subtitle is-4">${{ number_format($product->price_after_discount ?? $product->unit_price, 2) }}</h3>
          
                    <p class="product-description mb-4">
                        {{ $product->description }}
                    </p>
                    <div>
                        <p class="review-stars" aria-label="Rating">★★★★★ ({{ $reviews->avg('rating') ?? 'N/A' }})</p>
                    </div>
                    <div class="columns is-mobile is-vcentered is-gapless">
                        <!-- Quantity Input -->
                        <div class="column is-half">
                            <div class="control has-text-left">
                                <input type="number" class="input quantity-input" value="1" min="1" max="{{ $product->stock }}" aria-label="Quantity">
                            </div>
                        </div>
                        <!-- Stock Status -->
                        <div class="column is-half has-text-left">
                            <button class="button is-small stock-status {{ $product->stock > 0 ? 'is-success' : 'is-danger' }}">
                                {{ $product->stock > 0 ? 'In Stock' : 'Out of Stock' }}
                            </button>
                        </div>
                    </div>
                    <!-- Add to Cart Button -->
              <!-- Add to Cart Button -->
<!-- Add to Cart Button -->
<div class="mt-2">
    <form action="{{ route('cart.add') }}" method="POST">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->product_id }}">
        <input type="hidden" name="quantity" class="quantity-value" value="1">
        <button type="submit" class="button is-fullwidth {{ $product->stock > 0 ? '' : 'is-disabled' }}" {{ $product->stock > 0 ? '' : 'disabled' }}>
            <span class="icon">
                <i class="fas fa-shopping-cart"></i>
            </span>
            <span>Add to Cart</span>
        </button>
    </form>
</div>
                    <!-- Add to Wishlist Button -->
                    <div class="mt-2">
                        <form action="" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                            <button type="submit" class="button is-fullwidth">
                                <span class="icon">
                                    <i class="fas fa-heart"></i>
                                </span>
                                <span>Add to Wishlist</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
          
        <section class="section review-section">
            <h2 class="title is-4 review-title">Customer Reviews</h2>
          
            <!-- Overall Rating -->
            <div class="level">
                <div class="level-item">
                    <div class="overall-rating">
                        <p class="heading">Overall Rating</p>
                        <p class="review-stars is-size-4">★★★★★ ({{ $reviews->avg('rating') ?? 'N/A' }})</p>
                    </div>
                </div>
                <!-- Add Review Button -->
                <div class="level-item">
                    <a href="" class="button is-medium add-review-button">
                        Add a Review
                    </a>
                </div>
            </div>
          
            <!-- Reviews Grid -->
            <div class="columns is-multiline">
                @forelse($reviews as $review)
                    <div class="column is-6">
                        <div class="box review-card">
                            <p class="review-stars" aria-label="{{ $review->rating }} star rating">
                                @for($i = 1; $i <= 5; $i++)
                                    {{ $i <= $review->rating ? '★' : '☆' }}
                                @endfor
                            </p>
                            <p class="review-text mt-2">
                                {{ $review->review_description }}
                            </p>
                            <div class="review-content">
                                <div class="review-image">
                                    @php
                                        $hasUserImage = $review->user && $review->user->user_image;
                                        $userImageMimeType = $review->user && $review->user->USER_IMAGE_MIMETYPE ? $review->user->USER_IMAGE_MIMETYPE : 'image/png';
                                    @endphp
                                    <img src="{{ $hasUserImage ? 'data:' . $userImageMimeType . ';base64,' . base64_encode($review->user->user_image) : asset('images/avt.png') }}"
                                         alt="User Avatar" style="max-width: 50px; border-radius: 50%;">
                                </div>
                                <div>
                                    <p class="review-user has-text-weight-semibold">{{ $review->user ? $review->user->first_name : 'Anonymous' }}</p>
                                    <p class="review-date is-size-7 has-text-grey">{{ $review->review_date ? $review->review_date->format('F j, Y') : 'No date' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="column">
                        <p>No reviews yet.</p>
                    </div>
                @endforelse
            </div>
        </section>
          
        <section class="section">
            <h2 class="recommend-title">
                Recommended Products
                @if($product->category)
                    <a href="{{ route('categories.show', $product->category->category_name) }}" class="is-text">View all</a>
                @endif
            </h2>
            <div class="columns is-multiline">
                @forelse($recommendedProducts as $recProduct)
                    <div class="column is-3">
                        <div class="product-card">
                            <div class="product-card-image">
                                @php
                                    // Use direct image URL instead of inline base64
                                    $recImageSrc = route('trader.product.image', $recProduct->product_id);
                                    
                                    // Fallback path
                                    $recFallbackSrc = asset('images/default.png');
                                @endphp
                                
                                <img src="{{ $recImageSrc }}" alt="{{ $recProduct->product_name }}" onerror="this.src='{{ $recFallbackSrc }}'">
                            </div>
                            <h3 class="product-card-name">{{ $recProduct->product_name }}</h3>
                            <p>{{ $recProduct->shop ? $recProduct->shop->shop_name : 'Unknown Shop' }}</p>
                            <p class="product-card-price">${{ number_format($recProduct->price_after_discount ?? $recProduct->unit_price, 2) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="column">
                        <p>No recommended products available.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/script.js') }}"></script>
    <script>
        // Update hidden quantity input when user changes quantity
        document.querySelector('.quantity-input').addEventListener('input', function() {
            document.querySelector('.quantity-value').value = this.value;
        });
    </script>
@endpush