document.addEventListener('DOMContentLoaded', function() {
    // Post image upload elements
    const photoBtn = document.getElementById('photo-btn');
    const postImageInput = document.getElementById('post-image-input');
    const postImagePreviewContainer = document.getElementById('post-image-preview-container');
    const postImagePreview = document.getElementById('post-image-preview');
    const removePostImageBtn = document.getElementById('remove-post-image-btn');

    // Exchange modal elements
    const exchangeModal = document.getElementById('exchange-modal');
    const closeExchangeModalBtn = document.getElementById('close-exchange-modal');
    const cancelExchangeBtn = document.getElementById('cancel-exchange-btn');
    const exchangeForm = document.getElementById('exchange-form');
    const exchangePostIdInput = document.getElementById('exchange-post-id');
    const exchangeItemSelect = document.getElementById('exchange-item');
    const exchangeMessage = document.getElementById('exchange-message');

    // Photo button click event for post creation
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
            postImagePreviewContainer.classList.add('hidden');
        });
    }

    // Function to open the exchange modal
    function openExchangeModal(postId) {
        // Set form value
        exchangePostIdInput.value = postId;

        // Reset form
        exchangeForm.reset();

        // Show the modal
        exchangeModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }

    // Function to close the exchange modal
    function closeExchangeModal() {
        exchangeModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Close exchange modal when clicking the X button
    if (closeExchangeModalBtn) {
        closeExchangeModalBtn.addEventListener('click', closeExchangeModal);
    }

    // Close exchange modal when clicking the Cancel button
    if (cancelExchangeBtn) {
        cancelExchangeBtn.addEventListener('click', closeExchangeModal);
    }

    // Close exchange modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target === exchangeModal) {
            closeExchangeModal();
        }
    });

    // Add event listeners to exchange buttons
    const exchangeButtons = document.querySelectorAll('.exchange-btn');
    exchangeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            openExchangeModal(postId);
        });
    });

    // Add event listeners to edit buttons for inline editing
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const postCard = this.closest('.post-card');
            const postContent = postCard.querySelector('.post-content p');
            const isExchangeable = postCard.querySelector('.exchangeable-tag') !== null;

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

            // Create exchangeable checkbox
            const exchangeableDiv = document.createElement('div');
            exchangeableDiv.className = 'exchangeable-checkbox';
            exchangeableDiv.innerHTML = `
                <input type="checkbox" id="inline-exchangeable-${postId}" ${isExchangeable ? 'checked' : ''}>
                <label for="inline-exchangeable-${postId}">Mark as Exchangeable</label>
            `;

            // Create save button
            const saveBtn = document.createElement('button');
            saveBtn.textContent = 'Save Changes';
            saveBtn.className = 'button button-primary';
            saveBtn.style.marginBottom = '15px';

            // Create cancel button
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel';
            cancelBtn.className = 'button button-secondary';
            cancelBtn.style.marginBottom = '15px';
            cancelBtn.style.marginRight = '10px';

            // Create button container
            const buttonContainer = document.createElement('div');
            buttonContainer.style.display = 'flex';
            buttonContainer.style.gap = '10px';
            buttonContainer.appendChild(cancelBtn);
            buttonContainer.appendChild(saveBtn);

            // Insert elements after textarea
            textarea.parentNode.insertBefore(exchangeableDiv, textarea.nextSibling);
            textarea.parentNode.insertBefore(buttonContainer, exchangeableDiv.nextSibling);

            // Cancel button event listener
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Create new paragraph with original content
                const newParagraph = document.createElement('p');
                newParagraph.innerHTML = currentContent.replace(/\n/g, '<br>');

                // Replace textarea with new paragraph
                textarea.replaceWith(newParagraph);

                // Remove added elements
                exchangeableDiv.remove();
                buttonContainer.remove();
            });

            // Save button event listener
            saveBtn.addEventListener('click', function(e) {
                e.preventDefault();

                const newContent = textarea.value.trim();
                const newExchangeable = document.getElementById(`inline-exchangeable-${postId}`).checked;

                if (newContent) {
                    // Create a form to submit the data
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = window.location.href;
                    form.style.display = 'none';

                    // Add form fields
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'edit_post';

                    const postIdInput = document.createElement('input');
                    postIdInput.type = 'hidden';
                    postIdInput.name = 'post_id';
                    postIdInput.value = postId;

                    const contentInput = document.createElement('input');
                    contentInput.type = 'hidden';
                    contentInput.name = 'content';
                    contentInput.value = newContent;

                    const exchangeableInput = document.createElement('input');
                    exchangeableInput.type = 'hidden';
                    exchangeableInput.name = 'exchangeable';
                    exchangeableInput.value = newExchangeable ? '1' : '0';

                    // Append inputs to form
                    form.appendChild(actionInput);
                    form.appendChild(postIdInput);
                    form.appendChild(contentInput);

                    if (newExchangeable) {
                        form.appendChild(exchangeableInput);
                    }

                    // Append form to document and submit
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
});