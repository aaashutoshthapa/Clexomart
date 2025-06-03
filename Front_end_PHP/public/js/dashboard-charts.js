document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesPieChart').getContext('2d');

    // Example data, replace with your actual values
    const totalSalesThisMonth = 8020; // e.g., $320
    const totalProductWorth = 1248;  // e.g., $1248

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Sales This Month', 'Total Product Worth'],
            datasets: [{
                data: [totalSalesThisMonth, totalProductWorth],
                backgroundColor: ['#F0355E ', '#FED549'],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
    });
});