@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <!-- Hero Banner Section -->
    <section class="hero">
        <img src="{{ asset('images/home_banner.png') }}" alt="ClexoMart Banner" loading="lazy">
    </section>
    
    <!-- Top Traders Section (Carousel) -->
    <section class="section">
        <div class="top-traders-container">
            <div class="top-traders-label">Top Traders</div>
            <!-- Carousel -->
            <div class="carousel-wrapper">
                <div class="carousel" id="traderCarousel">
                    <div class="trader-logo">
                        <a href="{{ route('categories.show', 'Bakery') }}" aria-label="Visit Bakery Section">
                            <img src="{{ asset('images/traders category icon pack/Bakery.png') }}" alt="Bakery">
                        </a>
                    </div>
                    <div class="trader-logo">
                        <a href="{{ route('categories.show', 'Butchers') }}" aria-label="Visit Butchers Section">
                            <img src="{{ asset('images/traders category icon pack/Butchers.png') }}" alt="Butcher">
                        </a>
                    </div>
                    <div class="trader-logo">
                        <a href="{{ route('categories.show', 'Delicatessen') }}" aria-label="Visit Delicatessen Section">
                            <img src="{{ asset('images/traders category icon pack/Delicatessen.png') }}" alt="Delicatessen">
                        </a>
                    </div>
                    <div class="trader-logo">
                        <a href="{{ route('categories.show', 'Fishmonger') }}" aria-label="Visit Fishmonger Section">
                            <img src="{{ asset('images/traders category icon pack/Fishmonger.png') }}" alt="Fishmonger">
                        </a>
                    </div>
                    <div class="trader-logo">
                        <a href="{{ route('categories.show', 'Greengrocer') }}" aria-label="Visit Greengrocer Section">
                            <img src="{{ asset('images/traders category icon pack/Greengrocer.png') }}" alt="Green Grocer">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Today's Deal Section -->
    <section class="section">
        <div class="container">
            <h2 class="subtitle has-text-centered">Today's Deal</h2>
            <div class="product-grid">
                @forelse($products as $product)
                    <div class="product-card-wrapper">
                        <a href="{{ route('product.detail', $product->product_id) }}" class="product-link">
                            <div class="card">
                                <div class="card-image">
                                    <figure class="image is-4by3">
                                        @php
                                            // Use direct image URL instead of inline base64
                                            $imageSrc = route('trader.product.image', $product->product_id);
                                            
                                            // Fallback path
                                            $fallbackSrc = asset('images/default.png');
                                        @endphp
                                        
                                        <img src="{{ $imageSrc }}" 
                                             alt="{{ $product->product_name }}" 
                                             onerror="this.src='{{ $fallbackSrc }}'"
                                             class="responsive-image"
                                             loading="lazy">
                                    </figure>
                                </div>
                                <div class="card-content">
                                    <p class="title is-5">{{ $product->product_name }}</p>
                                    <p class="subtitle is-6">
                                        <span class="has-text-weight-bold has-text-success">
                                            ${{ number_format($product->price_after_discount ?? $product->unit_price, 2) }}
                                        </span>
                                    </p>
                                    <p class="content">
                                        {{ Str::limit($product->description, 100) }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="has-text-centered">
                        <div class="notification is-info is-light">
                            <h3 class="title is-4">No deals available today</h3>
                            <p>Check back later for exciting offers!</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Interesting Facts Section -->
    <section class="section facts-section">
        <div class="container">
            <h2 class="subtitle has-text-centered">Interesting Facts</h2>
            <div class="columns is-vcentered">
                <div class="column is-4">
                    <figure class="image">
                        <img src="{{ asset('images/salmon.jpg') }}" 
                             alt="Fresh Salmon" 
                             class="responsive-image"
                             loading="lazy">
                    </figure>
                </div>
                <div class="column is-8">
                    <div class="content">
                        <p class="is-size-6-mobile is-size-5-tablet">
                            Salmon is one of the best natural sources of omega-3 fatty acids, which are essential for brain health and can even improve your mood! Studies have shown that regular consumption of salmon can help reduce symptoms of depression and anxiety. So not only is it tasty — it's brain food too!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    // Touch-friendly carousel navigation for mobile
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('traderCarousel');
        if (carousel) {
            let isDown = false;
            let startX;
            let scrollLeft;

            carousel.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - carousel.offsetLeft;
                scrollLeft = carousel.scrollLeft;
            });

            carousel.addEventListener('mouseleave', () => {
                isDown = false;
            });

            carousel.addEventListener('mouseup', () => {
                isDown = false;
            });

            carousel.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - carousel.offsetLeft;
                const walk = (x - startX) * 2;
                carousel.scrollLeft = scrollLeft - walk;
            });
        }
    });
</script>
@endpush