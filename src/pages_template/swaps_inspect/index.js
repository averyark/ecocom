document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const swapModal = document.getElementById('swap-request-modal');
    const closeModalBtn = document.querySelector('.close-modal');
    const swapForm = document.getElementById('swap-request-form');
    const swapBtn = document.querySelector('.swap-btn');
    const favouriteBtn = document.getElementById('favourite-btn');
    const cancelSwapBtn = document.getElementById('cancel-swap-btn');
    const confirmSwapBtn = document.getElementById('confirm-swap-btn');
    const userItemsGrid = document.getElementById('user-items-grid');
    const selectedItemInput = document.getElementById('selected-item-id');

    // Get the current item ID from the URL or page data
    // This is a placeholder - in a real implementation, you would get this from the URL or page data
    const currentItemId = getItemIdFromUrl() || 'xxx';

    // Function to get item ID from URL (placeholder implementation)
    function getItemIdFromUrl() {
        // Example implementation - extract item ID from URL query parameters
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id');
    }

    // Function to fetch user's items from the backend
    function fetchUserItems() {
        // Show loading state
        userItemsGrid.innerHTML = '<div class="loading-items">Loading your items...</div>';

        // In a production environment, this would be an AJAX call to the backend
        // For development/demo purposes, we'll use both approaches:

        // 1. Try to fetch from the backend first
        fetch('/src/backend/swaps/request_swap.php?action=get_items')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.items && data.items.length > 0) {
                    // Backend returned items successfully
                    populateItemsGrid(data.items.map(item => ({
                        id: item.id,
                        name: item.title || item.name,
                        image: item.image_url || '/src/frontend/images/Toothpaste.png' // Default image if none provided
                    })));
                } else {
                    // Backend returned no items or error, fall back to dummy data
                    useDummyData();
                }
            })
            .catch(error => {
                console.error('Error fetching user items:', error);
                // If there's an error with the fetch, fall back to dummy data
                useDummyData();
            });
    }

    // Function to populate the grid with items
    function populateItemsGrid(items) {
        // Clear the grid
        userItemsGrid.innerHTML = '';

        // If no items, show a message
        if (!items || items.length === 0) {
            userItemsGrid.innerHTML = `
                <div class="no-items-message">
                    <p>You don't have any items to swap.</p>
                    <p>Create a new item first to be able to request a swap.</p>
                </div>
            `;
            return;
        }

        // Add each item as a card
        items.forEach(item => {
            const itemCard = document.createElement('div');
            itemCard.className = 'item-card';
            itemCard.dataset.itemId = item.id;

            itemCard.innerHTML = `
                <img src="${item.image}" alt="${item.name}">
                <h4>${item.name}</h4>
            `;

            // Add click event to select this item
            itemCard.addEventListener('click', function() {
                // Remove selected class from all items
                document.querySelectorAll('.item-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add selected class to this item
                this.classList.add('selected');

                // Update the hidden input with the selected item ID
                selectedItemInput.value = this.dataset.itemId;

                // Enable the confirm button
                confirmSwapBtn.disabled = false;
            });

            userItemsGrid.appendChild(itemCard);
        });
    }

    // Function to use dummy data when backend is not available
    function useDummyData() {
        // This would normally come from an API response
        const userItems = [
            { id: 101, name: 'Bamboo Toothbrush', image: '/src/frontend/images/Toothpaste.png' },
            { id: 102, name: 'Shampoo Bar - Lavender', image: '/src/frontend/images/Toothpaste.png' },
            { id: 103, name: 'Reusable Cotton Rounds (Pack of 10)', image: '/src/frontend/images/Toothpaste.png' },
            { id: 104, name: 'Compostable Floss - Mint', image: '/src/frontend/images/Toothpaste.png' }
        ];

        populateItemsGrid(userItems);
    }

    // Function to open the swap request modal
    function openSwapModal() {
        // Set the requested item ID in the hidden input
        const requestedItemInput = document.querySelector('input[name="requested_item_id"]');
        if (requestedItemInput) {
            requestedItemInput.value = currentItemId;
        }

        // Fetch user's items for the dropdown
        fetchUserItems();

        // Display the modal
        swapModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }

    // Function to close the swap request modal
    function closeSwapModal() {
        swapModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Open modal when clicking the REQUEST SWAP button
    if (swapBtn) {
        swapBtn.addEventListener('click', openSwapModal);
    }

    // Close modal when clicking the X button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeSwapModal);
    }

    // Close modal when clicking the Cancel button
    if (cancelSwapBtn) {
        cancelSwapBtn.addEventListener('click', closeSwapModal);
    }

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target === swapModal) {
            closeSwapModal();
        }
    });

    // Handle favourite button click
    if (favouriteBtn) {
        // Check if item is already in favourites (using localStorage for demo)
        const checkfavouriteStatus = () => {
            const favourites = JSON.parse(localStorage.getItem('favourites') || '[]');
            const isfavourite = favourites.some(item => item.id === currentItemId);

            if (isfavourite) {
                favouriteBtn.classList.add('active');
                favouriteBtn.textContent = 'FAVOURITED';
            } else {
                favouriteBtn.classList.remove('active');
                favouriteBtn.textContent = 'FAVOURITE';
            }
        };

        // Initial check
        checkfavouriteStatus();

        // Toggle favourite status on click
        favouriteBtn.addEventListener('click', function() {
            const favourites = JSON.parse(localStorage.getItem('favourites') || '[]');
            const isfavourite = favourites.some(item => item.id === currentItemId);

            if (isfavourite) {
                // Remove from favourites
                const updatedfavourites = favourites.filter(item => item.id !== currentItemId);
                localStorage.setItem('favourites', JSON.stringify(updatedfavourites));
                favouriteBtn.classList.remove('active');
                favouriteBtn.textContent = 'FAVOURITE';

                // Show feedback
                showToast('Removed from favourites');
            } else {
                // Add to favourites
                const itemTitle = document.querySelector('.product-info h1').textContent;
                const itemImage = document.querySelector('.product-image img').src;
                const itemDescription = document.querySelector('.product-description').textContent.substring(0, 100) + '...';

                favourites.push({
                    id: currentItemId,
                    name: itemTitle,
                    image: itemImage,
                    description: itemDescription,
                    addedAt: new Date().toISOString()
                });

                localStorage.setItem('favourites', JSON.stringify(favourites));
                favouriteBtn.classList.add('active');
                favouriteBtn.textContent = 'FAVOURITED';

                // Show feedback
                showToast('Added to favourites');
            }
        });
    }

    // Function to show toast notification
    function showToast(message) {
        // Create toast element if it doesn't exist
        let toast = document.getElementById('toast-notification');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast-notification';
            document.body.appendChild(toast);

            // Add styles
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.left = '50%';
            toast.style.transform = 'translateX(-50%)';
            toast.style.backgroundColor = '#333';
            toast.style.color = '#fff';
            toast.style.padding = '12px 24px';
            toast.style.borderRadius = '4px';
            toast.style.zIndex = '1000';
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
        }

        // Set message and show
        toast.textContent = message;
        toast.style.opacity = '1';

        // Hide after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
        }, 3000);
    }

    // Handle image upload preview
    if (swapImage) {
        swapImage.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    swapImagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Remove image preview when clicking the remove button
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            swapImage.value = '';
            imagePreviewContainer.classList.add('hidden');
        });
    }

    // Handle swap request form submission
    if (swapForm) {
        swapForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const message = swapMessage.value.trim();
            const selectedItem = itemSelect.value;

            if (!message) {
                alert('Please enter a message for your swap request.');
                return;
            }

            if (!selectedItem || selectedItem === '') {
                alert('Please select an item to swap with.');
                return;
            }

            // Here you would typically send the data to the server
            // For now, we'll just show a success message
            alert('Swap request sent successfully!');

            // Close the modal
            closeSwapModal();
        });
    }
});
