// Mobile navbar burger menu toggle
        document.addEventListener('DOMContentLoaded', () => {
            const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
            
            if ($navbarBurgers.length > 0) {
                $navbarBurgers.forEach( el => {
                    el.addEventListener('click', () => {
                        const target = el.dataset.target;
                        const $target = document.getElementById(target);
                        
                        el.classList.toggle('is-active');
                        $target.classList.toggle('is-active');
                    });
                });
            }

            // Tab functionality
            const tabs = document.querySelectorAll('.tabs li');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Update active tab
                    tabs.forEach(t => t.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    
                    // Here you would typically load data for the selected time period
                    const period = tab.getAttribute('data-period');
                    console.log(`Loading ${period} data...`);
                    // You would make an AJAX call or update charts here
                });
            });

            // Initialize charts
            // Sales Trend Chart (Line Chart)
            const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
            const salesTrendChart = new Chart(salesTrendCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Sales ($)',
                        data: [320, 450, 280, 510, 490, 620, 550],
                        backgroundColor: 'rgba(168, 198, 134, 0.2)',
                        borderColor: 'rgba(168, 198, 134, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Revenue by Category (Pie Chart)
            const categoryPieCtx = document.getElementById('categoryPieChart').getContext('2d');
            const categoryPieChart = new Chart(categoryPieCtx, {
                type: 'pie',
                data: {
                    labels: ['Fruits', 'Vegetables', 'Dairy', 'Meat', 'Bakery'],
                    datasets: [{
                        data: [35, 25, 20, 15, 5],
                        backgroundColor: [
                            '#A8C686',
                            '#FED549',
                            '#CC561E',
                            '#F0355E',
                            '#485fc7'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });

            // Top Selling Products (Bar Chart)
            const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
            const topProductsChart = new Chart(topProductsCtx, {
                type: 'bar',
                data: {
                    labels: ['Organic Apples', 'Chicken Breast', 'Organic Cheese', 'Organic Potatoes', 'Fresh Croissants'],
                    datasets: [{
                        label: 'Units Sold',
                        data: [120, 85, 76, 64, 42],
                        backgroundColor: '#A8C686',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });