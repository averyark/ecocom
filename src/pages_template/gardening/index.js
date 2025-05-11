document.addEventListener('DOMContentLoaded', function() {
    // Post button click event
    const postBtn = document.getElementById('post-btn');
    const postContent = document.getElementById('post-content');
    const exchangeableCheck = document.getElementById('exchangeable-check');
    const feedContainer = document.querySelector('.feed-container');

    // Post image upload elements
    const photoBtn = document.getElementById('photo-btn');
    const postImageInput = document.getElementById('post-image-input');
    const postImagePreviewContainer = document.getElementById('post-image-preview-container');
    const postImagePreview = document.getElementById('post-image-preview');
    const removePostImageBtn = document.getElementById('remove-post-image-btn');

    // Variable to store the current post image
    let currentPostImage = null;

    // Modal elements
    const exchangeModal = document.getElementById('exchange-modal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelExchangeBtn = document.getElementById('cancel-exchange-btn');
    const exchangeForm = document.getElementById('exchange-form');
    const exchangeMessage = document.getElementById('exchange-message');
    const exchangeImage = document.getElementById('exchange-image');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const exchangeImagePreview = document.getElementById('exchange-image-preview');
    const removeImageBtn = document.getElementById('remove-image-btn');

    // Current post being exchanged
    let currentExchangePost = null;

    if (postBtn) {
        postBtn.addEventListener('click', function() {
            const content = postContent.value.trim();
            if (content) {
                // Create a new post with content, image (if any), and exchangeable status
                createNewPost(content, currentPostImage, exchangeableCheck.checked);

                // Clear the input and image
                postContent.value = '';
                exchangeableCheck.checked = false;
                currentPostImage = null;
                postImagePreviewContainer.classList.add('hidden');
                postImagePreview.src = '#';
            }
        });
    }

    // Photo button click event
    if (photoBtn && postImageInput) {
        photoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            postImageInput.click(); // Trigger the file input click
        });

        // Handle file selection
        postImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentPostImage = e.target.result;
                    postImagePreview.src = e.target.result;
                    postImagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Remove post image button
    if (removePostImageBtn) {
        removePostImageBtn.addEventListener('click', function() {
            postImageInput.value = '';
            currentPostImage = null;
            postImagePreviewContainer.classList.add('hidden');
        });
    }

    // Function to open the exchange modal
    function openExchangeModal(postCard) {
        currentExchangePost = postCard;
        exchangeModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open

        // Clear previous form data
        exchangeForm.reset();
        imagePreviewContainer.classList.add('hidden');
        exchangeImagePreview.src = '#';
    }

    // Function to close the exchange modal
    function closeExchangeModal() {
        exchangeModal.style.display = 'none';
        document.body.style.overflow = '';
        currentExchangePost = null;
    }

    // Close modal when clicking the X button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeExchangeModal);
    }

    // Close modal when clicking the Cancel button
    if (cancelExchangeBtn) {
        cancelExchangeBtn.addEventListener('click', closeExchangeModal);
    }

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target === exchangeModal) {
            closeExchangeModal();
        }
    });

    // Handle image upload preview
    if (exchangeImage) {
        exchangeImage.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    exchangeImagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Remove image preview when clicking the remove button
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            exchangeImage.value = '';
            imagePreviewContainer.classList.add('hidden');
        });
    }

    // Handle exchange form submission
    if (exchangeForm) {
        exchangeForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const message = exchangeMessage.value.trim();
            if (!message) {
                alert('Please enter a message for your exchange request.');
                return;
            }

            // Here you would typically send the data to the server
            // For now, we'll just show a success message
            alert('Exchange request sent successfully!');

            // Close the modal
            closeExchangeModal();
        });
    }

    // Add event listeners to exchange buttons
    const exchangeButtons = document.querySelectorAll('.exchange-btn');
    exchangeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postCard = this.closest('.post-card');
            openExchangeModal(postCard);
        });
    });

    // Add event listeners to edit buttons
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postCard = this.closest('.post-card');
            const postContent = postCard.querySelector('.post-content p');

            // Create a temporary textarea to edit the content
            const currentContent = postContent.textContent;
            const textarea = document.createElement('textarea');
            textarea.value = currentContent;
            textarea.className = 'edit-textarea';
            textarea.style.width = '100%';
            textarea.style.minHeight = '100px';
            textarea.style.padding = '10px';
            textarea.style.marginBottom = '10px';
            textarea.style.borderRadius = '4px';
            textarea.style.border = '1px solid #ddd';

            // Replace the paragraph with the textarea
            postContent.replaceWith(textarea);
            textarea.focus();

            // Create save button
            const saveBtn = document.createElement('button');
            saveBtn.textContent = 'Save Changes';
            saveBtn.className = 'button button-primary';
            saveBtn.style.marginBottom = '15px';

            // Insert save button after textarea
            textarea.parentNode.insertBefore(saveBtn, textarea.nextSibling);

            // Save button event listener
            saveBtn.addEventListener('click', function() {
                const newContent = textarea.value.trim();
                if (newContent) {
                    // Create new paragraph with edited content
                    const newParagraph = document.createElement('p');
                    newParagraph.textContent = newContent;

                    // Replace textarea with new paragraph
                    textarea.replaceWith(newParagraph);
                    saveBtn.remove();
                }
            });
        });
    });

    // Add event listeners to delete buttons
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postCard = this.closest('.post-card');

            // Ask for confirmation before deleting
            if (confirm('Are you sure you want to delete this post?')) {
                // Add a fade-out animation
                postCard.style.transition = 'opacity 0.3s ease';
                postCard.style.opacity = '0';

                // Remove the post after the animation completes
                setTimeout(() => {
                    postCard.remove();
                }, 300);
            }
        });
    });

    // Function to create a new post
    function createNewPost(content, imageDataUrl, isExchangeable) {
        const newPost = document.createElement('div');
        newPost.className = 'post-card';

        // Get current date and time
        const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        let postHTML = `
            <div class="post-header">
                <div class="post-user-info">
                    <img src="/src/frontend/images/profile-placeholder.png" alt="User Profile" class="user-avatar">
                    <div class="post-user-details">
                        <h4>Current User</h4>
                        <p class="post-time">Just now (${timestamp})</p>
                    </div>
                </div>
                <div class="post-actions-top">
                    <button class="action-btn-top exchange-btn" title="Request Exchange">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button class="action-btn-top edit-btn" title="Edit Post">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn-top delete-btn" title="Delete Post">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="post-content">
                <p>${content}</p>
                ${imageDataUrl ? `<img src="${imageDataUrl}" alt="Post Image" class="post-image">` : ''}
            </div>
        `;

        // Add exchangeable tag if checked
        if (isExchangeable) {
            postHTML += `
                <div class="exchangeable-tag">
                    <i class="fas fa-exchange-alt"></i> Exchangeable
                </div>
            `;
        }

        newPost.innerHTML = postHTML;

        // Insert the new post at the top of the feed
        const firstPost = feedContainer.querySelector('.post-card');
        if (firstPost) {
            feedContainer.insertBefore(newPost, firstPost);
        } else {
            feedContainer.appendChild(newPost);
        }

        // Add event listeners to the new post's buttons
        const exchangeBtn = newPost.querySelector('.exchange-btn');
        exchangeBtn.addEventListener('click', function() {
            const postCard = this.closest('.post-card');
            openExchangeModal(postCard);
        });

        const editBtn = newPost.querySelector('.edit-btn');
        editBtn.addEventListener('click', function() {
            const postContent = newPost.querySelector('.post-content p');

            // Create a temporary textarea to edit the content
            const currentContent = postContent.textContent;
            const textarea = document.createElement('textarea');
            textarea.value = currentContent;
            textarea.className = 'edit-textarea';
            textarea.style.width = '100%';
            textarea.style.minHeight = '100px';
            textarea.style.padding = '10px';
            textarea.style.marginBottom = '10px';
            textarea.style.borderRadius = '4px';
            textarea.style.border = '1px solid #ddd';

            // Replace the paragraph with the textarea
            postContent.replaceWith(textarea);
            textarea.focus();

            // Create save button
            const saveBtn = document.createElement('button');
            saveBtn.textContent = 'Save Changes';
            saveBtn.className = 'button button-primary';
            saveBtn.style.marginBottom = '15px';

            // Insert save button after textarea
            textarea.parentNode.insertBefore(saveBtn, textarea.nextSibling);

            // Save button event listener
            saveBtn.addEventListener('click', function() {
                const newContent = textarea.value.trim();
                if (newContent) {
                    // Create new paragraph with edited content
                    const newParagraph = document.createElement('p');
                    newParagraph.textContent = newContent;

                    // Replace textarea with new paragraph
                    textarea.replaceWith(newParagraph);
                    saveBtn.remove();
                }
            });
        });

        const deleteBtn = newPost.querySelector('.delete-btn');
        deleteBtn.addEventListener('click', function() {
            // Ask for confirmation before deleting
            if (confirm('Are you sure you want to delete this post?')) {
                // Add a fade-out animation
                newPost.style.transition = 'opacity 0.3s ease';
                newPost.style.opacity = '0';

                // Remove the post after the animation completes
                setTimeout(() => {
                    newPost.remove();
                }, 300);
            }
        });
    }
});