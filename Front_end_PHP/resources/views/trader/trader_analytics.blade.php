@extends('layouts.traderapp')

@section('title', 'Trader Analytics')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/vendor_dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/analytics.css') }}">
    <style>
        .sales-summary-card {
            min-height: 160px;
        }
        .summary-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .positive-change {
            color: #22c65b;
            font-weight: bold;
        }
        .negative-change {
            color: #ff3860;
            font-weight: bold;
        }
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        .dashboard-card {
            margin-bottom: 1.5rem;
        }
        .card-header {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .top-product-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .top-product-item:last-child {
            border-bottom: none;
        }
        .top-product-name {
            flex-grow: 1;
        }
        .top-product-sales {
            color: #485fc7;
            font-weight: bold;
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
                <a href="{{ route('trader.reviews') }}" class="sidebar-item {{ request()->routeIs('trader.reviews') ? 'is-active' : '' }}">
                    <span class="icon"><i class="fas fa-star"></i></span>
                    <span>Reviews</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="column is-10 is-gapless">
            <div class="dashboard-header">
                <center><h1 class="title is-3 has-text-black">Sales Analytics</h1></center>
            </div>

            <div class="container">
                @if(session('error'))
                <div class="notification is-danger">
                    {{ session('error') }}
                </div>
                @endif

                <!-- Statistics Summary Cards -->
                <div class="buttons has-addons is-centered mb-4">
                    <button class="button is-light is-link period-btn" data-period="D">Today</button>
                    <button class="button is-light is-link period-btn" data-period="W">This&nbsp;Week</button>
                    <button class="button is-light is-link period-btn" data-period="M">This&nbsp;Month</button>
                    <button class="button is-light is-link period-btn is-active" data-period="A">All&nbsp;Time</button>
                </div>

                <div class="columns is-multiline">
                    <!-- Revenue Statistics -->
                    <div class="column is-3">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Total Sales</p>
                            </div>
                            <div class="card-content">
                                <div class="has-text-centered">
                                    <p class="summary-value" id="period-sales">${{ number_format($analyticsData['total_sales_all_time'], 2) }}</p>
                                    <p class="summary-label" id="period-sales-label">All Time Revenue</p>
                                    <p id="period-sales-change" class="positive-change" style="display: none;"><span></span>% vs. previous</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Total Orders</p>
                            </div>
                            <div class="card-content">
                                <div class="has-text-centered">
                                    <p class="summary-value" id="period-orders">{{ number_format($analyticsData['total_orders_all_time']) }}</p>
                                    <p class="summary-label" id="period-orders-label">Orders Completed</p>
                                    <p id="period-orders-change" class="positive-change" style="display: none;"><span></span>% vs. previous</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Average Order</p>
                            </div>
                            <div class="card-content">
                                <div class="has-text-centered">
                                    <p class="summary-value" id="period-avg-order">${{ number_format($analyticsData['average_order_value'], 2) }}</p>
                                    <p class="summary-label">Average Order Value</p>
                                    <p id="period-avg-order-change" class="positive-change" style="display: none;"><span></span>% vs. previous</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Items Sold</p>
                            </div>
                            <div class="card-content">
                                <div class="has-text-centered">
                                    <p class="summary-value" id="period-items-sold">{{ number_format($analyticsData['total_items_sold']) }}</p>
                                    <p class="summary-label">Total Units</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Statistics -->
                    <div class="column is-3">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Customer Base</p>
                            </div>
                            <div class="card-content">
                                <div class="has-text-centered">
                                    <p class="summary-value" id="period-customers">{{ number_format($analyticsData['total_customers']) }}</p>
                                    <p class="summary-label">Unique Customers</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Order Composition</p>
                            </div>
                            <div class="card-content">
                                <div class="has-text-centered">
                                    <p class="summary-value" id="period-items-per-order">{{ number_format($analyticsData['average_items_per_order'], 1) }}</p>
                                    <p class="summary-label">Items Per Order</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column is-3">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Inventory Value</p>
                            </div>
                            <div class="card-content">
                                <div class="has-text-centered">
                                    <p class="summary-value">${{ number_format($analyticsData['inventory_value'], 2) }}</p>
                                    <p class="summary-label">Current Stock Value</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Divider -->
                <div class="section-divider has-text-centered my-5">
                    <hr style="height: 2px; background-color: #f0f0f0; margin-bottom: 1rem;">
                    <span class="has-text-grey-light is-size-7 px-3" style="position: relative; top: -2rem; background-color: white; padding: 0 15px;">
                        STATIC ANALYTICS BELOW
                    </span>
                </div>

                <div class="columns">
                    <!-- Monthly Comparison -->
                    <div class="column is-6">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Monthly Performance</p>
                            </div>
                            <div class="card-content">
                                <div class="columns">
                                    <div class="column">
                                        <p class="has-text-centered">This Month</p>
                                        <p class="has-text-centered has-text-weight-bold">${{ number_format($analyticsData['this_month_sales'], 2) }}</p>
                                    </div>
                                    <div class="column">
                                        <p class="has-text-centered">Last Month</p>
                                        <p class="has-text-centered has-text-weight-bold">${{ number_format($analyticsData['last_month_sales'], 2) }}</p>
                                    </div>
                                    <div class="column">
                                        @php
                                            $percentChange = 0;
                                            if ($analyticsData['last_month_sales'] > 0) {
                                                $percentChange = (($analyticsData['this_month_sales'] - $analyticsData['last_month_sales']) / $analyticsData['last_month_sales']) * 100;
                                            }
                                        @endphp
                                        <p class="has-text-centered">Change</p>
                                        <p class="has-text-centered has-text-weight-bold {{ $percentChange >= 0 ? 'positive-change' : 'negative-change' }}">
                                            {{ $percentChange >= 0 ? '+' : '' }}{{ number_format($percentChange, 1) }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status Distribution -->
                    <div class="column is-6">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Order Status</p>
                            </div>
                            <div class="card-content">
                                <div class="columns is-multiline">
                                    @php
                                        $statusColors = [
                                            'pending' => 'is-warning',
                                            'processing' => 'is-info',
                                            'completed' => 'is-success',
                                            'cancelled' => 'is-danger',
                                            'unknown' => 'is-light'
                                        ];
                                    @endphp
                                    @forelse($analyticsData['order_status_distribution'] as $status)
                                        <div class="column is-6">
                                            <div class="level">
                                                <div class="level-left">
                                                    <span class="tag {{ $statusColors[strtolower($status['STATUS'] ?? $status['status'] ?? 'unknown')] ?? 'is-light' }}">
                                                        {{ ucfirst(strtolower($status['STATUS'] ?? $status['status'] ?? 'Unknown')) }}
                                                    </span>
                                                </div>
                                                <div class="level-right">
                                                    <strong>{{ $status['STATUS_COUNT'] ?? $status['status_count'] ?? 0 }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="column">
                                            <p class="has-text-centered">No order status data available</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Trend Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <p class="has-text-weight-bold">Sales Trend (Last 6 Months)</p>
                    </div>
                    <div class="card-content">
                        <div class="chart-container">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="columns">
                    <!-- Revenue by Products -->
                    <div class="column is-6">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Revenue by Products</p>
                            </div>
                            <div class="card-content">
                                @php
                                    // Prepare product sales data from analyticsData with robust error handling
                                    $productSalesData = ['labels' => [], 'data' => []];
                                    $totalSales = 0;
                                    
                                    if (!empty($analyticsData['product_sales_distribution'])) {
                                        // Calculate total sales first
                                        foreach($analyticsData['product_sales_distribution'] as $product) {
                                            // Oracle DB might return uppercase or lowercase keys
                                            $sales = $product['PRODUCT_SALES'] ?? 
                                                    ($product['product_sales'] ?? 0);
                                            $totalSales += (float)$sales;
                                        }
                                        
                                        // Then calculate percentages for each product
                                        foreach($analyticsData['product_sales_distribution'] as $product) {
                                            $name = $product['PRODUCT_NAME'] ?? 
                                                   ($product['product_name'] ?? 'Unnamed Product');
                                            
                                            $sales = $product['PRODUCT_SALES'] ?? 
                                                   ($product['product_sales'] ?? 0);
                                            
                                            $productSalesData['labels'][] = $name;
                                            $productSalesData['data'][] = $totalSales > 0 
                                                ? round(((float)$sales / $totalSales) * 100, 1) 
                                                : 0;
                                        }
                                    }
                                    
                                    // If no data is available, provide a default placeholder
                                    if (empty($productSalesData['labels'])) {
                                        $productSalesData['labels'][] = 'No Data Available';
                                        $productSalesData['data'][] = 100;
                                    }
                                @endphp
                                @if(count($productSalesData['labels'] ?? []) > 0)
                                <div class="chart-container">
                                    <canvas id="categoryPieChart"></canvas>
                                </div>
                                @else
                                <div class="has-text-centered p-6">
                                    <p>No product sales data available yet.</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Top Selling Products -->
                    <div class="column is-6">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <p class="has-text-weight-bold">Top Selling Products</p>
                            </div>
                            <div class="card-content">
                                @if(!empty($analyticsData['product_sales_distribution']) && is_countable($analyticsData['product_sales_distribution']) && count($analyticsData['product_sales_distribution']) > 0)
                                <div class="top-products-list">
                                    @foreach($analyticsData['product_sales_distribution'] as $product)
                                    <div class="top-product-item">
                                        <div class="top-product-name">{{ $product['PRODUCT_NAME'] ?? ($product['product_name'] ?? 'Unnamed Product') }}</div>
                                        <div class="top-product-sales">${{ number_format($product['PRODUCT_SALES'] ?? ($product['product_sales'] ?? 0), 2) }}</div>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="has-text-centered p-6">
                                    <p>No top selling products data available yet.</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configure chart color scheme
    const colors = {
        primary: '#485fc7',
        success: '#48c78e',
        info: '#3e8ed0',
        warning: '#ffe08a',
        danger: '#f14668',
        primaryLight: 'rgba(72, 95, 199, 0.2)',
        successLight: 'rgba(72, 199, 142, 0.2)',
        infoLight: 'rgba(62, 142, 208, 0.2)',
        chartColors: [
            '#A8C686', // soft green
            '#FED549', // golden yellow
            '#CC561E', // terracotta
            '#F0355E', // raspberry
            '#485fc7', // blue
            '#8C52FF'  // purple
        ]
    };

    // Initialize sales trend chart
    const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
    
    // Prepare chart data from analytics data if monthlySales is not available
    const chartData = {
        labels: [],
        values: []
    };
    
    @if(isset($monthlySales) && !empty($monthlySales['labels']))
        // Use data from monthlySales
        chartData.labels = @json($monthlySales['labels']);
        chartData.values = @json($monthlySales['values']);
    @elseif(isset($analyticsData['monthly_sales_trend']) && !empty($analyticsData['monthly_sales_trend']))
        // Create from monthly_sales_trend data
        @foreach($analyticsData['monthly_sales_trend'] as $month)
            chartData.labels.push("{{ $month['month_label'] ?? $month['MONTH_LABEL'] ?? '' }}");
            chartData.values.push({{ floatval($month['monthly_sales'] ?? $month['MONTHLY_SALES'] ?? 0) }});
        @endforeach
    @else
        // Fallback empty data
        chartData.labels = ["No Data"];
        chartData.values = [0];
    @endif
    
    const salesTrendChart = new Chart(salesTrendCtx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Monthly Sales ($)',
                data: chartData.values,
                backgroundColor: colors.primaryLight,
                borderColor: colors.primary,
                borderWidth: 2,
                pointBackgroundColor: colors.primary,
                pointRadius: 4,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('en-US', { 
                                    style: 'currency', 
                                    currency: 'USD' 
                                }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });

    // Initialize product distribution pie chart
    @if(count($productSalesData['labels'] ?? []) > 0)
    const categoryPieCtx = document.getElementById('categoryPieChart').getContext('2d');
    
    // Make chart globally accessible by assigning it to the window object
    window.categoryPieChart = new Chart(categoryPieCtx, {
        type: 'pie',
        data: {
            labels: @json($productSalesData['labels'] ?? []),
            datasets: [{
                data: @json($productSalesData['data'] ?? []),
                backgroundColor: colors.chartColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            return label + ': ' + value + '%';
                        }
                    }
                }
            }
        }
    });
    @endif

    // Format currency for display
    function formatCurrency(value) {
        return new Intl.NumberFormat('en-US', { 
            style: 'currency', 
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    }
    
    // Function to update the top selling products table when time period changes
    function updateTopSellingProductsTable(products) {
        const tableBody = document.querySelector('.table.is-striped tbody');
        if (!tableBody || !products) return;
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        if (products.length > 0) {
            // Add new rows
            products.forEach(product => {
                const row = document.createElement('tr');
                
                const nameCell = document.createElement('td');
                nameCell.textContent = product.product_name || 'Unnamed Product';
                row.appendChild(nameCell);
                
                const quantityCell = document.createElement('td');
                quantityCell.textContent = product.total_quantity_sold || '0';
                row.appendChild(quantityCell);
                
                const stockCell = document.createElement('td');
                stockCell.textContent = product.current_stock || '0';
                row.appendChild(stockCell);
                
                tableBody.appendChild(row);
            });
        } else {
            // No data message
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.setAttribute('colspan', '3');
            cell.textContent = 'No product quantity data available for this period.';
            cell.className = 'has-text-centered';
            row.appendChild(cell);
            tableBody.appendChild(row);
        }
    }
    
    // Function to update product distribution pie chart when time period changes
    function updateProductDistributionChart(productData) {
        console.log('Updating product distribution chart with data:', productData);
        
        // Check if we have the pie chart instantiated
        if (window.categoryPieChart && productData && productData.length > 0) {
            // Format data for pie chart
            const labels = [];
            const data = [];
            let totalSales = 0;
            
            // Calculate total sales first
            productData.forEach(product => {
                // Handle both uppercase and lowercase keys from Oracle DB
                const sales = parseFloat(product.PRODUCT_SALES || product.product_sales || 0);
                totalSales += sales;
            });
            
            // Then calculate percentages for each product
            productData.forEach(product => {
                // Handle both uppercase and lowercase keys from Oracle DB
                const name = product.PRODUCT_NAME || product.product_name || 'Unnamed Product';
                const sales = parseFloat(product.PRODUCT_SALES || product.product_sales || 0);
                
                labels.push(name);
                data.push(totalSales > 0 ? parseFloat(((sales / totalSales) * 100).toFixed(1)) : 0);
            });
            
            // Update chart data
            window.categoryPieChart.data.labels = labels;
            window.categoryPieChart.data.datasets[0].data = data;
            window.categoryPieChart.update();
            
            console.log('Pie chart updated with labels:', labels, 'and data:', data);
        } else if (window.categoryPieChart) {
            // If no data, set a placeholder
            window.categoryPieChart.data.labels = ['No Data Available'];
            window.categoryPieChart.data.datasets[0].data = [100];
            window.categoryPieChart.update();
            console.log('No product data available, chart updated with placeholder');
        } else {
            console.warn('Product pie chart not found or not initialized');
        }
        
        // Also update the top products list in the side panel
        updateTopProductsList(productData);
    }
    
    // Helper function to update the top selling products list in the side panel
    function updateTopProductsList(productData) {
        if (!productData || productData.length === 0) return;
        
        const topProductsList = document.querySelector('.top-products-list');
        if (!topProductsList) return;
        
        // Clear existing products
        topProductsList.innerHTML = '';
        
        // Sort by sales in descending order and take top 5
        const sortedProducts = [...productData]
            .sort((a, b) => {
                const aSales = parseFloat(a.PRODUCT_SALES || a.product_sales || 0);
                const bSales = parseFloat(b.PRODUCT_SALES || b.product_sales || 0);
                return bSales - aSales;
            })
            .slice(0, 5);
        
        // Add new products
        sortedProducts.forEach(product => {
            const name = product.PRODUCT_NAME || product.product_name || 'Unnamed Product';
            const sales = parseFloat(product.PRODUCT_SALES || product.product_sales || 0);
            
            const productItem = document.createElement('div');
            productItem.className = 'top-product-item';
            productItem.innerHTML = `
                <div class="top-product-name">${name}</div>
                <div class="top-product-sales">${formatCurrency(sales)}</div>
            `;
            topProductsList.appendChild(productItem);
        });
    }
    
    // Handle error in AJAX response
    function handleAjaxError(message) {
        // Show error notification
        const notification = document.createElement('div');
        notification.className = 'notification is-danger is-light';
        notification.innerHTML = `<button class="delete"></button>${message || 'Error loading analytics data'}`;
        
        // Add notification to container
        document.querySelector('.container').insertBefore(notification, document.querySelector('.dashboard-card'));
        
        // Add event listener to delete button
        notification.querySelector('.delete').addEventListener('click', () => {
            notification.remove();
        });
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Function to update summary cards with period data
    function updateSummaryCards(data) {
        if (data.error) {
            handleAjaxError(data.error);
            return;
        }
        
        // Update card labels based on period
        const periodLabels = {
            'D': 'Today',
            'W': 'This Week',
            'M': 'This Month',
            'A': 'All Time'
        };
        
        // Update sales card
        document.getElementById('period-sales').textContent = formatCurrency(data.sales);
        document.getElementById('period-sales-label').textContent = `${periodLabels[data.period]} Revenue`;
        
        // Only show change indicators for non-All Time views
        if (data.period !== 'A') {
            updateChangeIndicator('period-sales-change', data.salesChange);
            updateChangeIndicator('period-orders-change', data.ordersChange);
            updateChangeIndicator('period-avg-order-change', data.avgOrderChange);
        } else {
            // Hide change indicators for All Time view
            document.getElementById('period-sales-change').style.display = 'none';
            document.getElementById('period-orders-change').style.display = 'none';
            document.getElementById('period-avg-order-change').style.display = 'none';
        }
        
        // Update orders card
        document.getElementById('period-orders').textContent = data.orders.toLocaleString();
        document.getElementById('period-orders-label').textContent = `${periodLabels[data.period]} Orders`;
        
        // Update average order value card
        document.getElementById('period-avg-order').textContent = formatCurrency(data.avgOrderValue);
        
        // Update items sold
        if (data.itemsSold !== undefined) {
            document.getElementById('period-items-sold').textContent = data.itemsSold.toLocaleString();
        }
        
        // Update customers
        if (data.uniqueCustomers !== undefined) {
            document.getElementById('period-customers').textContent = data.uniqueCustomers.toLocaleString();
        }
        
        // Update items per order
        if (data.itemsPerOrder !== undefined) {
            document.getElementById('period-items-per-order').textContent = data.itemsPerOrder.toLocaleString();
        }
    }
    
    // Helper function to update change indicators (the percentage numbers)
    function updateChangeIndicator(elementId, changeValue) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        // Show the change indicator
        element.style.display = 'block';
        
        // Update the value
        const span = element.querySelector('span');
        if (span) {
            span.textContent = changeValue >= 0 ? `+${changeValue}` : changeValue;
        }
        
        // Update the class for color
        element.className = changeValue >= 0 ? 'positive-change' : 'negative-change';
    }
    
    // Add event listeners to period buttons
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active state
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            
            // Make AJAX request to get data for the selected period
            fetch(`/trader/analytics/period/${btn.dataset.period}`)
                .then(response => response.json())
                .then(updateSummaryCards)
                .catch(error => {
                    console.error('Error fetching period data:', error);
                    handleAjaxError('Failed to load period data. Please try again.');
                });
        });
    });
    
    // Trigger default (All Time) once DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.period-btn[data-period="A"]').click();
    });
});
</script>
@endpush 