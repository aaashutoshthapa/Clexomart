document.addEventListener('DOMContentLoaded', () => {
    // Dropdown Toggle for Filters and Sort
    const filterButton = document.querySelector('.filter-button');
    const sortButton = document.querySelector('.sort-button');
    const filterDropdown = document.querySelector('.filter-dropdown');
    const sortDropdown = document.querySelector('.sort-dropdown');

    filterButton.addEventListener('click', () => {
        filterDropdown.classList.toggle('is-active');
    });

    sortButton.addEventListener('click', () => {
        sortDropdown.classList.toggle('is-active');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!filterDropdown.contains(e.target)) {
            filterDropdown.classList.remove('is-active');
        }
        if (!sortDropdown.contains(e.target)) {
            sortDropdown.classList.remove('is-active');
        }
    });

    // Filter Logic
    const filterShop = document.querySelector('.filter-shop');
    const filterPriceMin = document.querySelector('.filter-price-min');
    const filterPriceMax = document.querySelector('.filter-price-max');
    const applyFilters = document.querySelector('.apply-filters');
    const items = document.querySelectorAll('.item');

    applyFilters.addEventListener('click', (e) => {
        e.preventDefault();
        const selectedShop = filterShop.value;
        const minPrice = parseFloat(filterPriceMin.value) || 0;
        const maxPrice = parseFloat(filterPriceMax.value) || Infinity;

        items.forEach(item => {
            const shop = item.dataset.shop;
            const price = parseFloat(item.dataset.price);

            const matchesShop = selectedShop === 'all' || shop === selectedShop;
            const matchesPrice = price >= minPrice && price <= maxPrice;

            if (matchesShop && matchesPrice) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });

        filterDropdown.classList.remove('is-active');
    });

    // Sort Logic
    const sortOptions = document.querySelectorAll('.sort-option');
    const itemsContainer = document.querySelector('.items-container');

    sortOptions.forEach(option => {
        option.addEventListener('click', (e) => {
            e.preventDefault();
            const sortBy = option.dataset.sort;
            const itemsArray = Array.from(items);

            if (sortBy === 'rating') {
                itemsArray.sort((a, b) => parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating));
            } else if (sortBy === 'price-low-high') {
                itemsArray.sort((a, b) => parseFloat(a.dataset.price) - parseFloat(b.dataset.price));
            } else if (sortBy === 'price-high-low') {
                itemsArray.sort((a, b) => parseFloat(b.dataset.price) - parseFloat(a.dataset.price));
            }

            // Re-append sorted items
            itemsArray.forEach(item => itemsContainer.appendChild(item));
            sortDropdown.classList.remove('is-active');
        });
    });
});