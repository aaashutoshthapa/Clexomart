@extends('layouts.traderapp')

@section('title', 'Trader Reviews')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/vendor_dashboard.css') }}">
    <style>
        .review-card {
            margin-bottom: 1.5rem;
            border-left: 4px solid #FED549;
            background-color: #fff;
            box-shadow: 0 2px 3px rgba(10, 10, 10, 0.1);
            padding: 1.25rem;
            border-radius: 0 4px 4px 0;
        }
        
        .review-product {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .review-date {
            font-size: 0.9rem;
            color: #888;
        }
        
        .review-text {
            margin: 0.75rem 0;
            font-style: italic;
            color: #444;
            line-height: 1.5;
        }
        
        .review-author {
            text-align: right;
            font-size: 0.9rem;
            color: #666;
            font-weight: 600;
        }
        
        .no-reviews {
            padding: 2rem;
            text-align: center;
            color: #888;
        }
    </style>
@endpush

@section('content')
<div class="columns">
    <!-- Sidebar -->
    <div class="column is-2 sidebar is-gapless">
        <div class="sidebar-menu">
            <a href="{{ route('trader') }}" class="sidebar-item {{ request()->routeIs('trader') ? 'is-active' : '' }}">
                <span class="icon"><i class="fas fa-tachometer-alt"></i></span>
                <span>Shop Information</span>
            </a>
            <a href="{{ route('Trader Product') }}" class="sidebar-item {{ request()->routeIs('Trader Product') ? 'is-active' : '' }}">
                <span class="icon"><i class="fas fa-box-open"></i></span>
                <span>Products</span>
            </a>
            <a href="{{ route('Trader Order') }}" class="sidebar-item {{ request()->routeIs('Trader Order') ? 'is-active' : '' }}">
                <span class="icon"><i class="fas fa-shopping-bag"></i></span>
                <span>Orders</span>
            </a>
            <a href="{{ route('Trader Analytics') }}" class="sidebar-item {{ request()->routeIs('Trader Analytics') || request()->routeIs('trader.sales') ? 'is-active' : '' }}">
                <span class="icon"><i class="fas fa-chart-line"></i></span>
                <span>Sales Analytics</span>
            </a>
            <a href="{{ route('trader.reviews') }}" class="sidebar-item is-active">
                <span class="icon"><i class="fas fa-star"></i></span>
                <span>Reviews</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="column is-10 is-gapless">
        <div class="dashboard-header">
            <center><h1 class="title is-3 has-text-black">Customer Reviews</h1></center>
        </div>
        <div class="container p-4">
            @if(isset($error))
                <div class="notification is-warning">
                    <p>{{ $error }}</p>
                </div>
            @endif
            
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="level">
                        <div class="level-left">
                            <p class="has-text-weight-bold">Recent Reviews ({{ count($reviews ?? []) }})</p>
                        </div>
                        <div class="level-right">
                            <a href="{{ route('Trader Product') }}" class="button is-small is-primary">
                                <span class="icon"><i class="fas fa-box-open"></i></span>
                                <span>Manage Products</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-content">
                    @if(count($reviews ?? []) > 0)
                        @foreach($reviews as $review)
                            <div class="review-card">
                                <div class="review-product">
                                    <span class="icon"><i class="fas fa-box"></i></span>
                                    <span>{{ $review['PRODUCT_NAME'] ?? $review['product_name'] ?? 'Unknown Product' }}</span>
                                </div>
                                <div class="review-date">
                                    <span class="icon is-small"><i class="fas fa-calendar-day"></i></span>
                                    <span>{{ $review['REVIEW_DATE'] ?? $review['review_date'] ?? 'Unknown Date' }}</span>
                                </div>
                                <div class="review-text">
                                    "{{ $review['REVIEW_DESCRIPTION'] ?? $review['review_description'] ?? 'No review text available' }}"
                                </div>
                                <div class="review-author">
                                    <span class="icon is-small"><i class="fas fa-user"></i></span>
                                    <span>{{ $review['REVIEWER'] ?? $review['reviewer'] ?? 'Anonymous' }}</span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="no-reviews">
                            <p>No reviews have been submitted for your products yet.</p>
                            <p class="mt-4">When customers review your products, they will appear here.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
