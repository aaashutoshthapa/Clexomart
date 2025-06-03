@extends('layouts.app')

@section('title', 'Category Page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/category_page.css') }}">
@endpush

@section('content')
    <!-- Category Section -->
    <section class="section">
        <div class="category-carusel-container">
            <div class="category-shop-label">Shops</div>
            <!-- Carousel -->
            <div class="category-carousel-wrapper">
                <div class="category-carousel">
                    @foreach($categories as $cat)
                        <div class="category-logo">
                            <a href="{{ route('categories.show', $cat->category_name) }}">
                                <img src="{{ asset('images/traders category icon pack/' . $cat->category_name . '.png') }}" alt="{{ $cat->category_name }}">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Shop Items Section -->
    <section class="section">
        <div class="container">
            <div class="level">
                <div class="level-left">
                    <div class="level-item">
                        <!-- Filters Dropdown -->
                        <div class="dropdown filter-dropdown">
                            <div class="dropdown-trigger">
                                <button class="button is-light filter-button" aria-haspopup="true" aria-controls="filterDropdownMenu">
                                    <span>Filters</span>
                                    <span class="icon is-small">
                                        <i class="fas fa-angle-down" aria-hidden="true"></i>
                                    </span>
                                </button>
                            </div>
                            <div class="dropdown-menu" id="filterDropdownMenu" role="menu">
                                <div class="dropdown-content">
                                    <!-- Filter by Shop -->
                                    <div class="dropdown-item">
                                        <p class="has-text-weight-bold">Filter by Shop</p>
                                        <div class="select is-small is-fullwidth">
                                            <select class="filter-shop">
                                                <option value="all">All Shops</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ strtolower($cat->category_name) }}">{{ $cat->category_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <hr class="dropdown-divider">
                                    <!-- Filter by Price Range -->
                                    <div class="dropdown-item">
                                        <p class="has-text-weight-bold">Price Range</p>
                                        <div class="field is-horizontal">
                                            <div class="field-body">
                                                <div class="field">
                                                    <div class="control">
                                                        <input class="input is-small filter-price-min" type="number" placeholder="Min" min="0">
                                                    </div>
                                                </div>
                                                <div class="field">
                                                    <div class="control">
                                                        <input class="input is-small filter-price-max" type="number" placeholder="Max" min="0">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="dropdown-divider">
                                    <a href="#" class="dropdown-item apply-filters">Apply Filters</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="level-right">
                    <div class="level-item">
                        <!-- Sort Dropdown -->
                        <div class="dropdown sort-dropdown">
                            <div class="dropdown-trigger">
                                <button class="button is-light sort-button" aria-haspopup="true" aria-controls="sortDropdownMenu">
                                    <span>Sort</span>
                                    <span class="icon is-small">
                                        <i class="fas fa-angle-down" aria-hidden="true"></i>
                                    </span>
                                </button>
                            </div>
                            <div class="dropdown-menu" id="sortDropdownMenu" role="menu">
                                <div class="dropdown-content">
                                    <a href="#" class="dropdown-item sort-option" data-sort="rating">Sort by Rating</a>
                                    <a href="#" class="dropdown-item sort-option" data-sort="price-low-high">Price: Low to High</a>
                                    <a href="#" class="dropdown-item sort-option" data-sort="price-high-low">Price: High to Low</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="columns is-multiline items-container">
                @forelse($products as $product)
                    <div class="column is-3 item" data-shop="{{ strtolower($product->category->category_name) }}" 
                         data-price="{{ $product->price_after_discount ?? $product->unit_price }}" 
                         data-rating="4.5">
                        <a href="{{ route('product.detail', $product->product_id) }}">
                            <div class="card">
                                <div class="card-image">
                                    <figure class="image is-4by3">
                                        @if($product->PRODUCT_image)
                                            <img src="data:{{ $product->PRODUCT_IMAGE_MIMETYPE }};base64,{{ base64_encode($product->PRODUCT_image) }}"
                                                 alt="{{ $product->product_name }}">
                                        @else
                                            <img src="{{ asset('images/default.png') }}" alt="No image available">
                                        @endif
                                    </figure>
                                </div>
                                <div class="card-content">
                                    <p class="title is-5">{{ $product->product_name }}</p>
                                    <p class="subtitle is-6">
                                        ${{ number_format($product->price_after_discount ?? $product->unit_price, 2) }}<br>
                                        {{ $product->description }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="column">
                        <p>No products available for this category.</p>
                    </div>
                @endforelse
            </div>
            <!-- Pagination -->
            <nav class="pagination is-centered" role="navigation" aria-label="pagination">
                {{ $products->links() }}
            </nav>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/category_page.js') }}"></script>
@endpush