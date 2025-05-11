/**
 * Component Loader
 * This script provides functionality to load HTML components and their associated JavaScript
 */

// Global object to track loaded components
window.loadedComponents = window.loadedComponents || {};

/**
 * Load an HTML component into a container
 * @param {string} containerId - The ID of the container element
 * @param {string} componentPath - Path to the HTML component file
 * @param {Object} options - Additional options
 * @param {boolean} options.loadScript - Whether to load the associated JS file
 * @param {Function} options.callback - Function to call after component is loaded
 */
function loadComponent(containerId, componentPath, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Container with ID "${containerId}" not found.`);
        return;
    }

    // Default options
    const defaultOptions = {
        loadScript: true,
        callback: null
    };

    // Merge options
    const settings = {...defaultOptions, ...options};

    // Extract component name from path
    const pathParts = componentPath.split('/');
    const componentName = pathParts[pathParts.length - 1].split('.')[0];

    // Check if component is already loaded
    if (window.loadedComponents[componentName]) {
        console.log(`Component ${componentName} already loaded.`);
        if (typeof settings.callback === 'function') {
            settings.callback();
        }
        return;
    }

    // Fetch the HTML component
    fetch(componentPath)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Failed to load component: ${response.status} ${response.statusText}`);
            }
            return response.text();
        })
        .then(html => {
            // Insert the HTML into the container
            container.innerHTML = html;

            // Mark component as loaded
            window.loadedComponents[componentName] = true;

            // Load the associated JavaScript if requested
            if (settings.loadScript) {
                const scriptPath = componentPath.replace('.html', '.js');
                loadComponentScript(scriptPath, () => {
                    // Initialize component if it has an init function
                    const initFunctionName = `initialize${componentName.charAt(0).toUpperCase() + componentName.slice(1)}`;
                    if (typeof window[initFunctionName] === 'function') {
                        window[initFunctionName]();
                    }

                    // Call the callback if provided
                    if (typeof settings.callback === 'function') {
                        settings.callback();
                    }
                });
            } else if (typeof settings.callback === 'function') {
                settings.callback();
            }
        })
        .catch(error => {
            console.error('Error loading component:', error);
        });
}

/**
 * Load a JavaScript file dynamically
 * @param {string} scriptPath - Path to the JavaScript file
 * @param {Function} callback - Function to call after script is loaded
 */
function loadComponentScript(scriptPath, callback) {
    // Check if script is already loaded
    const existingScript = document.querySelector(`script[src="${scriptPath}"]`);
    if (existingScript) {
        if (typeof callback === 'function') {
            callback();
        }
        return;
    }

    // Create script element
    const script = document.createElement('script');
    script.src = scriptPath;
    script.async = true;

    // Set up callback
    if (typeof callback === 'function') {
        script.onload = callback;
    }

    // Add script to document
    document.head.appendChild(script);
}

// Initialize components when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load header component if header-container exists
    const headerContainer = document.getElementById('header-container');
    if (headerContainer) {
        loadComponent('header-container', '/src/frontend/components/common/header.html');
    }

    // Load footer component if footer-container exists
    const footerContainer = document.getElementById('footer-container');
    if (footerContainer) {
        loadComponent('footer-container', '/src/frontend/components/common/footer.html');
    }

    // Add other common components here as needed
});
