@extends('layouts.traderapp')

@section('title', 'Trader Order')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/vendor_dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/vendor_order.css') }}">
    <style>
        .order-box {
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            border-left: 4px solid #e0e0e0;
        }
        
        .order-box.pending {
            border-left-color: #ff9800;
        }
        
        .order-box.processing {
            border-left-color: #2196F3;
        }
        
        .order-box.completed {
            border-left-color: #4caf50;
        }
        
        .order-box:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .order-status {
            font-weight: bold;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            display: inline-block;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #ff9800;
        }
        
        .status-processing {
            background-color: #e3f2fd;
            color: #2196F3;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .order-details {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #ddd;
            font-weight: bold;
        }
        
        .no-orders {
            padding: 3rem;
            text-align: center;
            color: #666;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        hr {
            background-color: #e0e0e0;
            height: 1px;
        }
        
        /* Tab styling improvements */
        .tabs.is-boxed li.is-active a {
            background-color: #4a89dc;
            border-color: #4a89dc;
            color: white;
        }
        
        .tabs.is-boxed li a:hover {
            background-color: #f5f5f5;
        }
        
        /* Order item table styling */
        .order-details .columns {
            margin: 0;
        }
        
        .order-details .columns:not(:last-child) {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-details .columns:hover:not(:first-child):not(:last-child) {
            background-color: #f5faff;
        }
        

        
        /* Order Detail Modal */
        .modal-card {
            max-width: 900px;
            width: 95%;
            border-radius: 8px;
        }
        
        .modal-card-head {
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            background-color: #f8f9fa;
        }
        
        .modal-card-foot {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            background-color: #f8f9fa;
        }
        
        /* Status badge in modal */
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Action buttons */
        .action-buttons .button {
            margin-left: 0.5rem;
            font-weight: 500;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        /* Customer info box */
        .customer-info {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        /* Order Summary box */
        .order-summary-box {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
            background-color: #f9f9f9;
        }
        
        /* Pulse animation for processing status */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(33, 150, 243, 0.4);
            }
            70% {
                box-shadow: 0 0 0 6px rgba(33, 150, 243, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(33, 150, 243, 0);
            }
        }
        
        .status-processing {
            animation: pulse 2s infinite;
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
                <a href="{{ route('Trader Product') }}" class="sidebar-item">
                    <span class="icon">
                        <i class="fas fa-box-open"></i>
                    </span>
                    <span>Products</span>
                </a>
                <a href="{{ route('Trader Order') }}" class="sidebar-item is-active">
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
                <center><h1 class="title is-3 has-text-black">Order Management</h1></center>
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

                @if(isset($message))
                    <div class="notification is-info">
                        {{ $message }}
                    </div>
                @endif
                


                <!-- Order Status Tabs -->
                <div class="tabs is-boxed">
                    <ul>
                        <li class="is-active" data-tab="all-orders">
                            <a>
                                <span class="icon is-small"><i class="fas fa-list" aria-hidden="true"></i></span>
                                <span>All Orders</span>
                            </a>
                        </li>
                        <li data-tab="pending-orders">
                            <a>
                                <span class="icon is-small"><i class="fas fa-clock" aria-hidden="true"></i></span>
                                <span>Pending</span>
                            </a>
                        </li>
                        <li data-tab="processing-orders">
                            <a>
                                <span class="icon is-small"><i class="fas fa-spinner fa-pulse" aria-hidden="true"></i></span>
                                <span>Processing</span>
                            </a>
                        </li>
                        <li data-tab="completed-orders">
                            <a>
                                <span class="icon is-small"><i class="fas fa-check-circle" aria-hidden="true"></i></span>
                                <span>Completed</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Orders List -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="level">
                            <div class="level-left">
                                <p class="has-text-weight-bold">Your Orders ({{ count($orders) }})</p>
                            </div>
                            <div class="level-right">
                                <div class="field">
                                    <div class="control has-icons-left">
                                        <div class="select">
                                            <select id="order-time-filter">
                                                <option value="all">All Time</option>
                                                <option value="today">Today</option>
                                                <option value="week">This Week</option>
                                                <option value="month">This Month</option>
                                            </select>
                                        </div>
                                        <div class="icon is-small is-left">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        @if(count($orders) > 0)


                            @foreach($orders as $order)
                                <div class="box order-box {{ $order['status'] }}" 
                                     data-status="{{ $order['status'] }}" 
                                     data-timestamp="{{ $order['timestamp'] }}"
                                     data-order-id="{{ $order['id'] }}"
                                     onclick="openOrderDetails('{{ $order['id'] }}')">
                                    <div class="columns">
                                        <!-- Order Header -->
                                        <div class="column is-12">
                                            <div class="level is-mobile">
                                                <div class="level-left">
                                                    <div class="level-item">
                                                        @if($order['status'] == 'pending')
                                                            <span class="icon has-text-warning mr-3">
                                                                <i class="fas fa-clock fa-lg"></i>
                                                            </span>
                                                        @elseif($order['status'] == 'processing')
                                                            <span class="icon has-text-info mr-3">
                                                                <i class="fas fa-spinner fa-pulse fa-lg"></i>
                                                            </span>
                                                        @elseif($order['status'] == 'completed')
                                                            <span class="icon has-text-success mr-3">
                                                                <i class="fas fa-check-circle fa-lg"></i>
                                                            </span>
                                                        @endif
                                                        <div>
                                                            <p class="has-text-weight-bold is-size-5">Order #{{ $order['id'] }}</p>
                                                            <p>
                                                                <span class="order-status status-{{ $order['status'] }}">{{ ucfirst($order['status']) }}</span> · 
                                                                <span>{{ $order['date'] }}</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="level-right">
                                                    <div class="level-item">
                                                        <div class="has-text-right">
                                                            <p class="has-text-weight-bold">{{ $order['customer'] }}</p>
                                                            <p>{{ $order['email'] }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Order Summary -->
                                        <div class="column is-12">
                                            <hr class="my-2">
                                            <div class="level is-mobile">
                                                <div class="level-right" style="margin-left: auto;">
                                                    <div class="level-item action-buttons">
                                                        @if($order['status'] == 'pending')
                                                            <form action="{{ route('trader.orders.update-status', $order['id']) }}" method="POST" class="is-inline" onclick="event.stopPropagation();">
                                                                @csrf
                                                                <input type="hidden" name="status" value="processing">
                                                                <button type="submit" class="button is-small is-info">
                                                                    <span class="icon">
                                                                        <i class="fas fa-spinner"></i>
                                                                    </span>
                                                                    <span>Process</span>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        
                                                        @if($order['status'] == 'processing')
                                                            <form action="{{ route('trader.orders.update-status', $order['id']) }}" method="POST" class="is-inline" onclick="event.stopPropagation();">
                                                                @csrf
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" class="button is-small is-success">
                                                                    <span class="icon">
                                                                        <i class="fas fa-check-circle"></i>
                                                                    </span>
                                                                    <span>Complete</span>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        
                                                        <a href="#" class="button is-small is-info is-light" onclick="event.stopPropagation(); showOrderDetails('{{ $order['id'] }}')">
                                                            <span class="icon">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                            <span>Details</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="no-orders has-text-centered py-5">
                                <span class="icon is-large">
                                    <i class="fas fa-shopping-bag fa-3x has-text-grey-light"></i>
                                </span>
                                <p class="is-size-4 mt-3 mb-2">No orders found.</p>
                                @if(empty($message))
                                    <p class="has-text-grey">Orders will appear here when customers place them.</p>
                                @else
                                    <p class="has-text-grey">{{ $message }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal" id="orderDetailsModal">
        <div class="modal-background"></div>
        <div class="modal-card">
            <header class="modal-card-head">
                <p class="modal-card-title">Order Details <span id="modalOrderId"></span></p>
                <button class="delete" aria-label="close" onclick="closeModal()"></button>
            </header>
            <section class="modal-card-body">
                <div class="columns">
                    <div class="column is-6">
                        <div class="customer-info">
                            <p class="is-size-5 has-text-weight-bold mb-2">Customer Information</p>
                            <p><strong>Name:</strong> <span id="modalCustomerName"></span></p>
                            <p><strong>Email:</strong> <span id="modalCustomerEmail"></span></p>
                            <p><strong>Order Date:</strong> <span id="modalOrderDate"></span></p>
                            <p class="mt-2"><strong>Status:</strong> <span id="modalOrderStatus" class="status-badge"></span></p>
                        </div>
                    </div>
                    <div class="column is-6">
                        <div class="order-summary-box">
                            <p class="is-size-5 has-text-weight-bold mb-2">Order Summary</p>
                            <div class="columns">
                                <div class="column is-6">
                                    <p><strong>Items:</strong> <span id="modalItemCount"></span></p>
                                    <p><strong>Your Total:</strong> $<span id="modalYourTotal"></span></p>
                                </div>
                                <div class="column is-6">
                                    <p><strong>Order Total:</strong> $<span id="modalOrderTotal"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <p class="is-size-5 has-text-weight-bold mb-2">Order Items</p>
                    <table class="table is-fullwidth is-striped is-hoverable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="has-text-centered">Quantity</th>
                                <th class="has-text-centered">Unit Price</th>
                                <th class="has-text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="modalOrderItems">
                            <!-- Order items will be inserted here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="has-text-right">Your Items Total:</th>
                                <th class="has-text-right">$<span id="modalItemsTotal"></span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
            <footer class="modal-card-foot">
                <div class="level is-mobile" style="width: 100%">
                    <div class="level-left">
                        <div id="modalOrderActions">
                            <!-- Order action buttons will be inserted here -->
                        </div>
                    </div>
                    <div class="level-right">
                        <button class="button" onclick="closeModal()">Close</button>
                    </div>
                </div>
            </footer>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Store the orders data for easy access in JavaScript
        const ordersData = {
            @foreach($orders as $order)
                "{{ $order['id'] }}": {
                    id: "{{ $order['id'] }}",
                    date: "{{ $order['date'] }}",
                    timestamp: "{{ $order['timestamp'] }}",
                    customer: "{{ $order['customer'] }}",
                    email: "{{ $order['email'] }}",
                    total_amount: "{{ $order['total_amount'] }}",
                    trader_amount: "{{ $order['trader_amount'] }}",
                    status: "{{ $order['status'] }}",
                    items: @json($order['items'])
                },
            @endforeach
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const tabs = document.querySelectorAll('.tabs li');
            const orderBoxes = document.querySelectorAll('.order-box');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('is-active'));
                    
                    // Add active class to clicked tab
                    tab.classList.add('is-active');
                    
                    // Get the tab data attribute
                    const tabName = tab.getAttribute('data-tab');
                    
                    // Filter orders based on tab
                    filterOrders();
                });
            });
            
            // Time filter
            const timeFilter = document.getElementById('order-time-filter');
            timeFilter.addEventListener('change', filterOrders);
            
            // Initial filter
            filterOrders();
        });
        
        function filterOrders() {
            const orderBoxes = document.querySelectorAll('.order-box');
            const activeTab = document.querySelector('.tabs li.is-active').getAttribute('data-tab');
            const timeFilter = document.getElementById('order-time-filter').value;
            const now = new Date();
            
            orderBoxes.forEach(box => {
                const status = box.getAttribute('data-status');
                const timestamp = new Date(box.getAttribute('data-timestamp'));
                
                // First filter by tab
                let showByTab = false;
                if (activeTab === 'all-orders') {
                    showByTab = true;
                } else if (activeTab === 'pending-orders' && status === 'pending') {
                    showByTab = true;
                } else if (activeTab === 'processing-orders' && status === 'processing') {
                    showByTab = true;
                } else if (activeTab === 'completed-orders' && status === 'completed') {
                    showByTab = true;
                }
                
                // Then filter by time
                let showByTime = true;
                if (timeFilter !== 'all') {
                    if (timeFilter === 'today') {
                        // Compare just the date part (YYYY-MM-DD)
                        const today = new Date();
                        showByTime = timestamp.toISOString().slice(0, 10) === today.toISOString().slice(0, 10);
                    } else if (timeFilter === 'week') {
                        // Current week (Sunday to Saturday)
                        const currentDay = now.getDay(); // 0 = Sunday, 6 = Saturday
                        const firstDayOfWeek = new Date(now);
                        firstDayOfWeek.setDate(now.getDate() - currentDay); // Go back to Sunday
                        firstDayOfWeek.setHours(0, 0, 0, 0);
                        
                        const lastDayOfWeek = new Date(firstDayOfWeek);
                        lastDayOfWeek.setDate(firstDayOfWeek.getDate() + 6); // Go to Saturday
                        lastDayOfWeek.setHours(23, 59, 59, 999);
                        
                        showByTime = timestamp >= firstDayOfWeek && timestamp <= lastDayOfWeek;
                    } else if (timeFilter === 'month') {
                        // Current month
                        const firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
                        const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                        lastDayOfMonth.setHours(23, 59, 59, 999);
                        
                        showByTime = timestamp >= firstDayOfMonth && timestamp <= lastDayOfMonth;
                    }
                }
                
                // Show only if passes both filters
                box.style.display = (showByTab && showByTime) ? '' : 'none';
            });
            
            // Update the count in the header
            updateVisibleOrderCount();
        }
        
        function updateVisibleOrderCount() {
            const visibleOrders = document.querySelectorAll('.order-box[style="display: none;"]');
            const totalOrders = document.querySelectorAll('.order-box').length;
            const visibleCount = totalOrders - visibleOrders.length;
            
            document.querySelector('.card-header .has-text-weight-bold').textContent = 
                `Your Orders (${visibleCount})`;
        }
        
        function showOrderDetails(orderId) {
            const order = ordersData[orderId];
            if (!order) return;
            
            // Fetch the latest status from the server if there's been a status change
            // This ensures we display the current status after an update
            fetch(`/trader/orders/${orderId}/status-check`)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        // Update our local data with the latest status
                        order.status = data.status;
                        
                        // Update the order box status in the UI
                        const orderBox = document.querySelector(`.order-box[data-order-id="${orderId}"]`);
                        if (orderBox) {
                            // Update data attribute
                            orderBox.setAttribute('data-status', data.status);
                            // Update class
                            orderBox.classList.remove('pending', 'processing', 'completed');
                            orderBox.classList.add(data.status);
                        }
                    }
                    displayOrderModal(order);
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                    displayOrderModal(order); // Still show the modal with the data we have
                });
        }
        
        function displayOrderModal(order) {
            // Populate modal with order details
            document.getElementById('modalOrderId').textContent = `#${order.id}`;
            document.getElementById('modalCustomerName').textContent = order.customer;
            document.getElementById('modalCustomerEmail').textContent = order.email;
            document.getElementById('modalOrderDate').textContent = order.date;
            
            // Set status badge with correct color
            const statusBadge = document.getElementById('modalOrderStatus');
            statusBadge.textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
            statusBadge.className = 'status-badge'; // Reset classes
            statusBadge.classList.add(`status-${order.status}`);
            
            // Set order summary info
            document.getElementById('modalItemCount').textContent = order.items.length;
            document.getElementById('modalYourTotal').textContent = parseFloat(order.trader_amount).toFixed(2);
            document.getElementById('modalOrderTotal').textContent = parseFloat(order.total_amount).toFixed(2);
            document.getElementById('modalItemsTotal').textContent = parseFloat(order.trader_amount).toFixed(2);
            
            // Populate order items
            const itemsContainer = document.getElementById('modalOrderItems');
            itemsContainer.innerHTML = '';
            
            if (order.items && order.items.length > 0) {
                order.items.forEach(item => {
                    const row = document.createElement('tr');
                    
                    const nameCell = document.createElement('td');
                    nameCell.textContent = item.PRODUCT_NAME;
                    
                    const quantityCell = document.createElement('td');
                    quantityCell.className = 'has-text-centered';
                    quantityCell.textContent = item.QUANTITY;
                    
                    const priceCell = document.createElement('td');
                    priceCell.className = 'has-text-centered';
                    priceCell.textContent = `$${parseFloat(item.UNIT_PRICE).toFixed(2)}`;
                    
                    const totalCell = document.createElement('td');
                    totalCell.className = 'has-text-right';
                    totalCell.textContent = `$${parseFloat(item.ITEM_TOTAL).toFixed(2)}`;
                    
                    row.appendChild(nameCell);
                    row.appendChild(quantityCell);
                    row.appendChild(priceCell);
                    row.appendChild(totalCell);
                    
                    itemsContainer.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 4;
                cell.className = 'has-text-centered';
                cell.textContent = 'No item details available';
                row.appendChild(cell);
                itemsContainer.appendChild(row);
            }
            
            // Populate action buttons
            const actionsContainer = document.getElementById('modalOrderActions');
            actionsContainer.innerHTML = '';
            
            if (order.status === 'pending') {
                // Add processing button
                const processingForm = document.createElement('form');
                processingForm.action = `/trader/orders/${order.id}/update-status`;
                processingForm.method = 'POST';
                processingForm.className = 'is-inline mr-2';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = 'processing';
                
                const button = document.createElement('button');
                button.type = 'submit';
                button.className = 'button is-info';
                button.innerHTML = '<span class="icon"><i class="fas fa-spinner"></i></span><span>Mark as Processing</span>';
                
                processingForm.appendChild(csrfInput);
                processingForm.appendChild(statusInput);
                processingForm.appendChild(button);
                
                actionsContainer.appendChild(processingForm);
            }
            
            if (order.status === 'processing') {
                // Add completed button
                const completedForm = document.createElement('form');
                completedForm.action = `/trader/orders/${order.id}/update-status`;
                completedForm.method = 'POST';
                completedForm.className = 'is-inline mr-2';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = 'completed';
                
                const button = document.createElement('button');
                button.type = 'submit';
                button.className = 'button is-success';
                button.innerHTML = '<span class="icon"><i class="fas fa-check-circle"></i></span><span>Mark as Completed</span>';
                
                completedForm.appendChild(csrfInput);
                completedForm.appendChild(statusInput);
                completedForm.appendChild(button);
                
                actionsContainer.appendChild(completedForm);
            }
            
            // Add contact customer button
            const contactLink = document.createElement('a');
            contactLink.href = `mailto:${order.email}`;
            contactLink.className = 'button is-info is-light';
            contactLink.innerHTML = '<span class="icon"><i class="fas fa-envelope"></i></span><span>Contact Customer</span>';
            
            actionsContainer.appendChild(contactLink);
            
            // Show the modal
            document.getElementById('orderDetailsModal').classList.add('is-active');
        }
        
        function openOrderDetails(orderId) {
            showOrderDetails(orderId);
        }
        
        function closeModal() {
            document.getElementById('orderDetailsModal').classList.remove('is-active');
        }

        // Add event listeners to forms inside the order cards to handle successful status update
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.order-box form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const formData = new FormData(this);
                    const orderId = this.action.split('/').pop();
                    const newStatus = formData.get('status');
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            // Update local data structure
                            if (ordersData[orderId]) {
                                ordersData[orderId].status = newStatus;
                            }
                            
                            // Update UI
                            const orderBox = document.querySelector(`.order-box[data-order-id="${orderId}"]`);
                            if (orderBox) {
                                // Update status attribute
                                orderBox.setAttribute('data-status', newStatus);
                                
                                // Update CSS classes
                                orderBox.classList.remove('pending', 'processing', 'completed');
                                orderBox.classList.add(newStatus);
                                
                                // Update status indicator
                                const statusIcon = orderBox.querySelector('.icon.mr-3');
                                if (statusIcon) {
                                    statusIcon.className = 'icon mr-3';
                                    
                                    if (newStatus === 'pending') {
                                        statusIcon.classList.add('has-text-warning');
                                        statusIcon.innerHTML = '<i class="fas fa-clock fa-lg"></i>';
                                    } else if (newStatus === 'processing') {
                                        statusIcon.classList.add('has-text-info');
                                        statusIcon.innerHTML = '<i class="fas fa-spinner fa-pulse fa-lg"></i>';
                                    } else if (newStatus === 'completed') {
                                        statusIcon.classList.add('has-text-success');
                                        statusIcon.innerHTML = '<i class="fas fa-check-circle fa-lg"></i>';
                                    }
                                }
                                
                                // Update status text
                                const statusText = orderBox.querySelector('.order-status');
                                if (statusText) {
                                    statusText.className = 'order-status';
                                    statusText.classList.add(`status-${newStatus}`);
                                    statusText.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                                }
                                
                                // Update buttons
                                const actionsContainer = orderBox.querySelector('.action-buttons');
                                if (actionsContainer) {
                                    // Remove all current forms
                                    actionsContainer.querySelectorAll('form').forEach(el => el.remove());
                                    
                                    // Add appropriate buttons based on new status
                                    if (newStatus === 'pending') {
                                        const processForm = document.createElement('form');
                                        processForm.action = `/trader/orders/${orderId}/update-status`;
                                        processForm.method = 'POST';
                                        processForm.className = 'is-inline';
                                        processForm.setAttribute('onclick', 'event.stopPropagation()');
                                        
                                        const csrfInput = document.createElement('input');
                                        csrfInput.type = 'hidden';
                                        csrfInput.name = '_token';
                                        csrfInput.value = '{{ csrf_token() }}';
                                        
                                        const statusInput = document.createElement('input');
                                        statusInput.type = 'hidden';
                                        statusInput.name = 'status';
                                        statusInput.value = 'processing';
                                        
                                        const button = document.createElement('button');
                                        button.type = 'submit';
                                        button.className = 'button is-small is-info';
                                        button.innerHTML = '<span class="icon"><i class="fas fa-spinner"></i></span><span>Process</span>';
                                        
                                        processForm.appendChild(csrfInput);
                                        processForm.appendChild(statusInput);
                                        processForm.appendChild(button);
                                        
                                        // Add event listener to the new form
                                        processForm.addEventListener('submit', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            
                                            const formData = new FormData(this);
                                            fetch(this.action, {
                                                method: 'POST',
                                                body: formData,
                                                headers: {'X-Requested-With': 'XMLHttpRequest'}
                                            })
                                            .then(response => {
                                                if (response.ok) {
                                                    location.reload();
                                                }
                                            });
                                        });
                                        
                                        actionsContainer.prepend(processForm);
                                    } else if (newStatus === 'processing') {
                                        const completeForm = document.createElement('form');
                                        completeForm.action = `/trader/orders/${orderId}/update-status`;
                                        completeForm.method = 'POST';
                                        completeForm.className = 'is-inline';
                                        completeForm.setAttribute('onclick', 'event.stopPropagation()');
                                        
                                        const csrfInput = document.createElement('input');
                                        csrfInput.type = 'hidden';
                                        csrfInput.name = '_token';
                                        csrfInput.value = '{{ csrf_token() }}';
                                        
                                        const statusInput = document.createElement('input');
                                        statusInput.type = 'hidden';
                                        statusInput.name = 'status';
                                        statusInput.value = 'completed';
                                        
                                        const button = document.createElement('button');
                                        button.type = 'submit';
                                        button.className = 'button is-small is-success';
                                        button.innerHTML = '<span class="icon"><i class="fas fa-check-circle"></i></span><span>Complete</span>';
                                        
                                        completeForm.appendChild(csrfInput);
                                        completeForm.appendChild(statusInput);
                                        completeForm.appendChild(button);
                                        
                                        // Add event listener to the new form
                                        completeForm.addEventListener('submit', function(e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            
                                            const formData = new FormData(this);
                                            fetch(this.action, {
                                                method: 'POST',
                                                body: formData,
                                                headers: {'X-Requested-With': 'XMLHttpRequest'}
                                            })
                                            .then(response => {
                                                if (response.ok) {
                                                    location.reload();
                                                }
                                            });
                                        });
                                        
                                        actionsContainer.prepend(completeForm);
                                    }
                                }
                                
                                // Re-filter orders to move to correct tab
                                filterOrders();
                            }
                            
                            // Show success message
                            const notification = document.createElement('div');
                            notification.className = 'notification is-success';
                            notification.innerHTML = `Order #${orderId} has been marked as ${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}`;
                            document.querySelector('.dashboard-card').before(notification);
                            
                            // Auto-dismiss the notification
                            setTimeout(() => {
                                notification.remove();
                            }, 5000);
                        }
                    });
                });
            });
        });
    </script>
@endpush