/**
 * Client-side Form Validation
 * Prevents invalid forms from submitting and provides instant error feedback.
 */

document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Generic Form Validator
    const forms = document.querySelectorAll(".validated-form");
    
    forms.forEach(form => {
        form.addEventListener("submit", function(event) {
            let isValid = true;
            
            // Remove previous error messages
            form.querySelectorAll(".error-message").forEach(el => el.remove());
            form.querySelectorAll(".form-control").forEach(el => el.style.borderColor = "");
            
            // Validate text/email/number fields
            const inputs = form.querySelectorAll("[required]");
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    showError(input, `${getLabelName(input)} is required.`);
                    isValid = false;
                } else {
                    // Specific Email Check
                    if (input.type === "email") {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(input.value.trim())) {
                            showError(input, "Please enter a valid email address.");
                            isValid = false;
                        }
                    }
                    
                    // Specific Numbers Check (No negative prices or stocks)
                    if (input.type === "number") {
                        const val = parseFloat(input.value);
                        const min = input.getAttribute("min") !== null ? parseFloat(input.getAttribute("min")) : null;
                        
                        if (isNaN(val)) {
                            showError(input, "Please enter a valid number.");
                            isValid = false;
                        } else if (min !== null && val < min) {
                            showError(input, `${getLabelName(input)} must be at least ${min}.`);
                            isValid = false;
                        }
                    }
                }
            });
            
            // Validate File Uploads (Type & Size limit)
            const fileInputs = form.querySelectorAll("input[type='file']");
            fileInputs.forEach(fileInput => {
                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                    const maxSize = 2 * 1024 * 1024; // 2MB
                    
                    if (!allowedTypes.includes(file.type)) {
                        showError(fileInput, "Invalid file format. Only JPG, PNG, WEBP are allowed.");
                        isValid = false;
                    } else if (file.size > maxSize) {
                        showError(fileInput, "File size exceeds 2MB limit.");
                        isValid = false;
                    }
                } else if (fileInput.hasAttribute("required")) {
                    showError(fileInput, "An image file is required.");
                    isValid = false;
                }
            });
            
            if (!isValid) {
                event.preventDefault(); // Stop submission
            }
        });
    });
    
    // Helper to extract a friendly field label name
    function getLabelName(input) {
        const id = input.getAttribute("id");
        if (id) {
            const label = document.querySelector(`label[for="${id}"]`);
            if (label) return label.textContent.replace(":", "").trim();
        }
        const placeholder = input.getAttribute("placeholder");
        if (placeholder) return placeholder;
        
        return input.getAttribute("name") || "Field";
    }
    
    // Helper to render error text directly under the input field
    function showError(input, message) {
        input.style.borderColor = "#ef4444";
        
        const errorDiv = document.createElement("div");
        errorDiv.className = "error-message";
        errorDiv.style.color = "#f87171";
        errorDiv.style.fontSize = "0.8rem";
        errorDiv.style.marginTop = "0.25rem";
        errorDiv.style.fontWeight = "500";
        errorDiv.innerText = message;
        
        // Handle input group styling structure if applicable
        input.parentNode.appendChild(errorDiv);
    }
    
    // Interactive File Upload Preview Handler
    const fileSelector = document.querySelector("input[type='file']");
    if (fileSelector) {
        fileSelector.addEventListener("change", function() {
            const previewContainer = document.querySelector(".file-upload-preview");
            if (previewContainer && this.files.length > 0) {
                const file = this.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewContainer.innerHTML = `<img src="${e.target.result}" style="max-height: 100px; border-radius: 4px;" />
                    <span style="font-size:0.8rem; margin-top:5px; display:block;">${file.name} (${(file.size/1024).toFixed(1)} KB)</span>`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
