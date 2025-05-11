const imageInput = document.getElementById('image-input');
const previewImage = document.getElementById('preview-image');
const imageUploadRequired = document.getElementById('image-upload-required');
const form = document.querySelector('form');
let imageUploaded = false;

// Handle image preview when file is selected
imageInput.addEventListener('change', function (event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImage.src = e.target.result;
            imageUploaded = true;
            // Hide error message if it was previously shown
            imageUploadRequired.style.display = 'none';
            // Ensure the image fills the container
            previewImage.style.width = '100%';
            previewImage.style.height = '100%';
            previewImage.style.objectFit = 'cover';
        }
        reader.readAsDataURL(file);
    }
});

// Form submission validation
form.addEventListener('submit', function (event) {
    console.log(previewImage.src)
    const imageIsRequired = previewImage.src.endsWith('../../images/placeholder.png');
    // If image is required but not uploaded, show error and prevent form submission
    if (imageIsRequired && !imageInput.files.length) {
        event.preventDefault();
        imageUploadRequired.style.display = 'block';
    }
});