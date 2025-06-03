<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Invoice - ClexoMart</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fdf5;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(168, 198, 134, 0.2);
        }
        .header {
            background: linear-gradient(135deg, #A8C686 0%, #96b574 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .invoice-number {
            background-color: #FED549;
            color: #333;
            padding: 15px 20px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }
        .content {
            padding: 30px 20px;
        }
        .order-details {
            background-color: #f8fdf5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #A8C686;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .detail-row:last-child {
            margin-bottom: 0;
            border-top: 2px solid #A8C686;
            padding-top: 15px;
            margin-top: 15px;
            font-weight: bold;
            font-size: 18px;
        }
        .detail-label {
            font-weight: bold;
            color: #A8C686;
        }
        .detail-value {
            color: #333;
        }
        .items-section {
            margin-bottom: 25px;
        }
        .section-title {
            color: #A8C686;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 2px solid #FED549;
            padding-bottom: 5px;
        }
        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .item:last-child {
            border-bottom: none;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: bold;
            font-size: 16px;
            color: #333;
            margin-bottom: 5px;
        }
        .item-price {
            color: #666;
            font-size: 14px;
        }
        .item-quantity {
            background-color: #A8C686;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin: 0 15px;
        }
        .item-total {
            font-weight: bold;
            color: #CC561E;
            font-size: 16px;
        }
        .pickup-info {
            background-color: #fff3cd;
            border: 1px solid #FED549;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .pickup-info h3 {
            color: #CC561E;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .pickup-info ul {
            margin: 0;
            padding-left: 20px;
        }
        .pickup-info li {
            margin-bottom: 8px;
            color: #333;
        }
        .footer {
            background-color: #A8C686;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #FED549;
            text-decoration: none;
        }
        .total-amount {
            color: #CC561E;
            font-weight: bold;
            font-size: 20px;
        }
        .status-badge {
            background-color: #FED549;
            color: #333;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 8px;
            }
            .content {
                padding: 20px 15px;
            }
            .header {
                padding: 20px 15px;
            }
            .item {
                flex-direction: column;
                align-items: flex-start;
            }
            .item-quantity {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ClexoMart</h1>
            <p>Thank you for your order!</p>
        </div>
        
        <div class="invoice-number">
            Invoice #{{ $order->order_id }}
        </div>
        
        <div class="content">
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Order Date:</span>
                    <span class="detail-value">{{ date('F j, Y', strtotime($order->order_date)) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value">PayPal</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge">{{ ucfirst($order->status) }}</span>
                </div>
                @if($order->pickup_date ?? false)
                <div class="detail-row">
                    <span class="detail-label">Pickup Date:</span>
                    <span class="detail-value">{{ date('l, F j, Y', strtotime($order->pickup_date)) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Pickup Time Slot:</span>
                    <span class="detail-value">
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
                            <strong style="color: #CC561E;">{{ $timeSlot }}</strong>
                        @else
                            Time not specified
                        @endif
                    </span>
                </div>
                @else
                <div class="detail-row">
                    <span class="detail-label">Pickup Schedule:</span>
                    <span class="detail-value" style="color: #666;">To be scheduled - we will contact you</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value total-amount">${{ number_format($order->payment_amount, 2) }}</span>
                </div>
            </div>
            
            <div class="items-section">
                <h2 class="section-title">Order Items</h2>
                @foreach($orderItems as $item)
                <div class="item">
                    <div class="item-details">
                        <div class="item-name">{{ $item->product_name }}</div>
                        <div class="item-price">${{ number_format($item->unit_price, 2) }} each</div>
                    </div>
                    <div class="item-quantity">Qty: {{ $item->quantity }}</div>
                    <div class="item-total">${{ number_format($item->unit_price * $item->quantity, 2) }}</div>
                </div>
                @endforeach
            </div>
            
            <div class="pickup-info">
                <h3>📍 Pickup Information</h3>
                <ul>
                    <li><strong>Location:</strong> ClexoMart Store, Clekhuddersfax, UK</li>
                    <li><strong>Requirements:</strong> Please bring a valid ID and show this order number: <strong>{{ $order->order_id }}</strong></li>
                    @if($order->pickup_date ?? false)
                        @php
                            $hour = $order->pickup_time ? date('H', strtotime($order->pickup_time)) : null;
                            $timeSlot = '';
                            $timeDescription = '';
                            if ($hour == 10) {
                                $timeSlot = '10:00 AM - 1:00 PM';
                                $timeDescription = 'morning slot';
                            } elseif ($hour == 13) {
                                $timeSlot = '1:00 PM - 4:00 PM';
                                $timeDescription = 'afternoon slot';
                            } elseif ($hour == 16) {
                                $timeSlot = '4:00 PM - 7:00 PM';
                                $timeDescription = 'evening slot';
                            } else {
                                $timeSlot = $order->pickup_time ? date('g:i A', strtotime($order->pickup_time)) : 'Time TBD';
                                $timeDescription = 'scheduled time';
                            }
                        @endphp
                        <li><strong>Scheduled Pickup:</strong> {{ date('l, F j, Y', strtotime($order->pickup_date)) }}</li>
                        <li><strong>Time Slot:</strong> <span style="color: #CC561E; font-weight: bold;">{{ $timeSlot }}</span> ({{ $timeDescription }})</li>
                        <li><strong>Pickup Window:</strong> Please arrive within your scheduled {{ $timeDescription }}</li>
                    @else
                        <li><strong>Scheduling:</strong> We will contact you within 24 hours to schedule your pickup time</li>
                        <li><strong>Available Days:</strong> Wednesday, Thursday, and Friday only</li>
                        <li><strong>Available Slots:</strong> 10:00 AM-1:00 PM, 1:00 PM-4:00 PM, 4:00 PM-7:00 PM</li>
                    @endif
                    <li><strong>Late Pickup:</strong> If you cannot collect during your scheduled time, please contact us immediately at <a href="mailto:support@clexomart.com" style="color: #CC561E;">support@clexomart.com</a></li>
                    <li><strong>Store Hours:</strong> Wed-Fri: 10:00 AM - 7:00 PM (Pickup only during scheduled slots)</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>ClexoMart</strong> - The ultimate store for all your needs</p>
            <p>📧 <a href="mailto:support@clexomart.com">support@clexomart.com</a> | 📞 +44-9800000000</p>
            <p>📍 Clekhuddersfax, UK</p>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
                © {{ date('Y') }} ClexoMart. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html> 