@extends('layouts.traderapp')

@section('title', 'Vendor Reviews')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/vendor_dashboard.css') }}">
@endpush

@section('content')
<div class="columns">
    <!-- Sidebar -->
    <div class="column is-2 sidebar is-gapless">
        <div class="sidebar-menu">
            <a href="{{ route('trader') }}" class="sidebar-item {{ request()->routeIs('trader') ? 'is-active' : '' }}">
                <span class="icon"><i class="fas fa-tachometer-alt"></i></span>
                <span>Dashboard</span>
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
            <a href="{{ route('trader.reviews') }}" class="sidebar-item {{ request()->routeIs('trader.reviews') ? 'is-active' : '' }}">
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
            <div class="notification is-info">
                <p>Customer reviews for your products will be displayed here.</p>
            </div>
            <div class="dashboard-card">
                <div class="card-header">
                    <p class="has-text-weight-bold">Recent Reviews</p>
                </div>
                <div class="card-content">
                    <p>This is a placeholder for the reviews page. You'll be able to see and respond to customer reviews here.</p>
                    <div class="buttons mt-4">
                        <a href="{{ route('trader') }}" class="button is-primary">
                            <span class="icon"><i class="fas fa-tachometer-alt"></i></span>
                            <span>Return to Dashboard</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 