document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const activeItemsGrid = document.getElementById('active-items-grid');
    const expiredItemsGrid = document.getElementById('expired-items-grid');
    const noActiveItemsMessage = document.getElementById('no-active-items');
    const noExpiredItemsMessage = document.getElementById('no-expired-items');

    // Function to switch tabs
    function switchTab(tabId) {
        // Update tab buttons
        tabButtons.forEach(btn => {
            if (btn.dataset.tab === tabId) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update tab content
        tabContents.forEach(content => {
            if (content.id === `${tabId}-tab`) {
                content.classList.add('active');
            } else {
                content.classList.remove('active');
            }
        });
    }

    // Add click event listeners to tab buttons
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });

    // Function to fetch user's favourite items from the backend
    function fetchfavouriteItems() {
        // Show loading state for active items
        activeItemsGrid.innerHTML = '<div class="loading-items">Loading your favourites...</div>';

        // Show loading state for expired items
        expiredItemsGrid.innerHTML = '<div class="loading-items">Loading expired items...</div>';

        // In a production environment, this would be an AJAX call to the backend
        // For development/demo purposes, we'll use dummy data for now

        // Simulate API delay
        setTimeout(() => {
            // Dummy data for active items
            const activeItems = [
                {
                    id: 101,
                    name: 'Bamboo Toothbrush',
                    image: '/src/frontend/images/Toothpaste.png',
                    description: 'Eco-friendly bamboo toothbrush with soft bristles'
                },
                {
                    id: 102,
                    name: 'Shampoo Bar - Lavender',
                    image: '/src/frontend/images/Toothpaste.png',
                    description: 'Zero-waste shampoo bar with natural lavender scent'
                },
                {
                    id: 103,
                    name: 'Reusable Cotton Rounds',
                    image: '/src/frontend/images/Toothpaste.png',
                    description: 'Pack of 10 reusable cotton rounds for makeup removal'
                }
            ];

            // Dummy data for expired items
            const expiredItems = [
                {
                    id: 104,
                    name: 'Compostable Floss - Mint',
                    image: '/src/frontend/images/Toothpaste.png',
                    description: 'Biodegradable dental floss with mint flavor',
                    expired: true
                },
                {
                    id: 105,
                    name: 'Beeswax Food Wraps',
                    image: '/src/frontend/images/Toothpaste.png',
                    description: 'Set of 3 reusable food wraps made with beeswax',
                    expired: true
                }
            ];

            // Populate active items grid
            populateItemsGrid(activeItemsGrid, activeItems, noActiveItemsMessage);

            // Populate expired items grid
            populateItemsGrid(expiredItemsGrid, expiredItems, noExpiredItemsMessage, true);
        }, 1000);
    }

    // Function to populate the grid with items
    function populateItemsGrid(gridElement, items, noItemsMessage, isExpired = false) {
        // Clear the grid
        gridElement.innerHTML = '';

        // If no items, show the no items message
        if (!items || items.length === 0) {
            gridElement.innerHTML = '';
            noItemsMessage.classList.remove('hidden');
            return;
        }

        // Hide the no items message if there are items
        noItemsMessage.classList.add('hidden');

        // Add each item as a card
        items.forEach(item => {
            const itemCard = document.createElement('div');
            itemCard.className = isExpired ? 'item-card expired' : 'item-card';
            itemCard.dataset.itemId = item.id;

            itemCard.innerHTML = `
                <img src="${item.image}" alt="${item.name}">
                <div class="item-info">
                    <h3>${item.name}</h3>
                    <p>${item.description}</p>
                    <div class="item-actions">
                        <button class="view-btn" onclick="window.location.href='/src/frontend/components/swaps_inspect/index.html?id=${item.id}'">View Item</button>
                        <button class="remove-btn" data-id="${item.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            gridElement.appendChild(itemCard);
        });

        // Add event listeners to remove buttons
        const removeButtons = gridElement.querySelectorAll('.remove-btn');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.dataset.id;
                removeFromfavourites(itemId, this.closest('.item-card'));
            });
        });
    }

    // Function to remove an item from favourites
    function removeFromfavourites(itemId, itemElement) {
        // Confirm before removing
        if (confirm('Are you sure you want to remove this item from your favourites?')) {
            // Add a fade-out animation
            itemElement.style.transition = 'opacity 0.3s ease';
            itemElement.style.opacity = '0';

            // In a production environment, this would be an AJAX call to the backend
            // For now, just remove the element from the DOM after animation
            setTimeout(() => {
                itemElement.remove();

                // Check if the grid is now empty
                const gridElement = itemElement.parentElement;
                if (gridElement.children.length === 0) {
                    // Show the appropriate no items message
                    if (gridElement.id === 'active-items-grid') {
                        noActiveItemsMessage.classList.remove('hidden');
                    } else if (gridElement.id === 'expired-items-grid') {
                        noExpiredItemsMessage.classList.remove('hidden');
                    }
                }
            }, 300);
        }
    }

    // Initialize the page
    fetchfavouriteItems();
});