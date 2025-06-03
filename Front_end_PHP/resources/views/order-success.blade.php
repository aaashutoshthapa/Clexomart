@extends('layouts.app')

@section('title', 'Order Confirmation')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/cartproduct.css') }}">
    <style>
        /* Custom styling to match navbar color scheme */
        .success-notification {
            background-color: #A8C686 !important; /* Main navbar green */
            color: white !important;
            border: none !important;
        }
        
        .success-notification .icon {
            color: #FED549 !important; /* Navbar yellow for icon */
        }
        
        .custom-box {
            border: 2px solid #A8C686;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(168, 198, 134, 0.2);
        }
        
        .custom-title {
            color: #A8C686 !important;
            border-bottom: 2px solid #FED549;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem !important;
        }
        
        .custom-button-primary {
            background-color: #A8C686 !important;
            border-color: #A8C686 !important;
            color: white !important;
        }
        
        .custom-button-primary:hover {
            background-color: #96b574 !important;
            border-color: #96b574 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(168, 198, 134, 0.3);
        }
        
        .custom-button-secondary {
            background-color: #FED549 !important;
            border-color: #FED549 !important;
            color: #333 !important;
        }
        
        .custom-button-secondary:hover {
            background-color: #Fbc02d !important;
            border-color: #Fbc02d !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(254, 213, 73, 0.3);
        }
        
        .status-tag {
            background-color: #FED549 !important;
            color: #333 !important;
        }
        
        .item-tag {
            background-color: #A8C686 !important;
            color: white !important;
        }
        
        .paypal-icon {
            color: #CC561E !important; /* Navbar orange for PayPal */
        }
        
        .highlight-amount {
            color: #CC561E !important;
            font-weight: bold;
        }
        
        .info-box {
            background-color: #f8fdf5 !important; /* Very light green background */
            border-left: 4px solid #A8C686;
            padding: 1.5rem;
            border-radius: 8px;
        }
        
        .info-box strong {
            color: #A8C686 !important;
        }
        
        .order-item-row {
            border-bottom: 1px solid #A8C686;
            padding: 1rem 0;
        }
        
        .order-item-row:last-child {
            border-bottom: none;
        }
        
        .container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
    </style>
@endpush

@section('content')
    <div class="container">
        {{-- Success Message --}}
        <div class="columns is-centered">
            <div class="column is-8">
                <div class="notification success-notification">
                    <div class="has-text-centered">
                        <span class="icon is-large">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </span>
                        <h1 class="title is-3 mt-3" style="color: white !important;">Order Placed Successfully!</h1>
                        <p class="subtitle" style="color: white !important;">Thank you for your purchase. Your order has been confirmed and an invoice has been sent to your email.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Details --}}
        <div class="columns is-centered">
            <div class="column is-8">
                <div class="box custom-box">
                    <h2 class="title is-4 custom-title">Order Details</h2>
                    
                    <div class="columns">
                        <div class="column is-half">
                            <table class="table is-borderless">
                                <tbody>
                                    <tr>
                                        <td><strong>Order ID:</strong></td>
                                        <td><span class="highlight-amount">{{ $order->order_id }}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Order Date:</strong></td>
                                        <td>{{ date('F j, Y', strtotime($order->order_date)) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <span class="tag status-tag">{{ ucfirst($order->status) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Amount:</strong></td>
                                        <td><span class="highlight-amount">${{ number_format($order->payment_amount, 2) }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="column is-half">
                            <table class="table is-borderless">
                                <tbody>
                                    @if($order->pickup_date)
                                    <tr>
                                        <td><strong>Pickup Date:</strong></td>
                                        <td>
                                            {{ date('l, F j, Y', strtotime($order->pickup_date)) }}
                                            <span class="tag is-small status-tag ml-2">Confirmed</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pickup Time Slot:</strong></td>
                                        <td>
                                            @if($order->pickup_time)
                                                @php
                                                    $hour = date('H', strtotime($order->pickup_time));
                                                    $timeSlot = '';
                                                    if ($hour == 10) {
                                                        $timeSlot = '10:00 AM - 1:00 PM';
                                                    } elseif ($hour == 13) {
                                                        $timeSlot = '1:00 PM - 4:00 PM';
                                                    } elseif ($hour == 16) {
                                                        $timeSlot = '4:00 PM - 7:00 PM';
                                                    } else {
                                                        $timeSlot = date('g:i A', strtotime($order->pickup_time));
                                                    }
                                                @endphp
                                                <strong class="highlight-amount">{{ $timeSlot }}</strong>
                                            @else
                                                <span class="has-text-grey">Time not specified</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td><strong>Pickup Date:</strong></td>
                                        <td>
                                            <span class="has-text-grey">To be scheduled</span>
                                            <span class="tag is-small is-warning ml-2">Pending</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Pickup Time Slot:</strong></td>
                                        <td><span class="has-text-grey">To be scheduled</span></td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td><strong>Payment Method:</strong></td>
                                        <td>
                                            <span class="icon paypal-icon">
                                                <i class="fab fa-paypal"></i>
                                            </span>
                                            PayPal
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Items --}}
        <div class="columns is-centered">
            <div class="column is-8">
                <div class="box custom-box">
                    <h3 class="title is-5 custom-title">Order Items</h3>
                    
                    @foreach($orderItems as $item)
                        <div class="columns is-vcentered mb-3 order-item-row">
                            <div class="column is-2">
                                <figure class="image is-64x64">
                                    <img src="{{ route('trader.product.image', $item->product_id) }}" 
                                         alt="{{ $item->product_name }}" 
                                         class="is-rounded"
                                         onerror="this.src='{{ asset('images/default.png') }}'">
                                </figure>
                            </div>
                            <div class="column is-6">
                                <h4 class="title is-6">{{ $item->product_name }}</h4>
                                <p class="subtitle is-7">${{ number_format($item->unit_price, 2) }} each</p>
                            </div>
                            <div class="column is-2 has-text-centered">
                                <span class="tag item-tag">Qty: {{ $item->quantity }}</span>
                            </div>
                            <div class="column is-2 has-text-right">
                                <span class="highlight-amount">${{ number_format($item->unit_price * $item->quantity, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Important Information --}}
        <div class="columns is-centered">
            <div class="column is-8">
                <div class="box custom-box">
                    <h3 class="title is-5 custom-title">Important Information</h3>
                    <div class="info-box">
                        <ul>
                            <li><strong>Order Confirmation:</strong> You will receive an email confirmation with invoice details shortly.</li>
                            <li><strong>Pickup Location:</strong> Please collect your order from our store during your selected time slot.</li>
                            <li><strong>Pickup Requirements:</strong> Please bring a valid ID and your order number.</li>
                            <li><strong>Late Pickup:</strong> If you're unable to pickup during your slot, please contact us immediately.</li>
                            <li><strong>Contact:</strong> For any questions, please contact our customer service at support@clexomart.com.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="columns is-centered">
            <div class="column is-8">
                <div class="field is-grouped is-grouped-centered">
                    <div class="control">
                        <a href="{{ route('home') }}" class="button is-medium custom-button-primary">
                            <span class="icon">
                                <i class="fas fa-home"></i>
                            </span>
                            <span>Continue Shopping</span>
                        </a>
                    </div>
                    <div class="control">
                        <button class="button is-medium custom-button-secondary" onclick="window.print()">
                            <span class="icon">
                                <i class="fas fa-print"></i>
                            </span>
                            <span>Print Order</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    @media print {
        .navbar, .footer, .field.is-grouped {
            display: none !important;
        }
    }
</style>
@endpush 