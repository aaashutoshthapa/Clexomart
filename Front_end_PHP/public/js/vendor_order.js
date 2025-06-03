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
            const orderBoxes = document.querySelectorAll('.order-box');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Update active tab
                    tabs.forEach(t => t.classList.remove('is-active'));
                    tab.classList.add('is-active');
                    
                    // Filter orders
                    const tabName = tab.getAttribute('data-tab');
                    orderBoxes.forEach(box => {
                        if (tabName === 'all-orders') {
                            box.style.display = 'block';
                        } else {
                            const status = box.getAttribute('data-status');
                            if (tabName.includes(status)) {
                                box.style.display = 'block';
                            } else {
                                box.style.display = 'none';
                            }
                        }
                    });
                });
            });

            // Confirm before cancelling order
            document.querySelectorAll('.button.is-danger').forEach(button => {
                button.addEventListener('click', (e) => {
                    if (!confirm('Are you sure you want to cancel this order?')) {
                        e.preventDefault();
                    }
                });
            });
        });