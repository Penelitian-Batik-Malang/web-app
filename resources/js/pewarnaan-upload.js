/**
 * Pewarnaan Upload Handler
 * Manages file uploads for color and batik images with drag-drop functionality
 */

class PewarnaanUploadHandler {
    constructor() {
        this.fileInput = document.getElementById("color-file-input");
        this.dropzone = document.getElementById("color-dropzone");
        this.filePreview = document.getElementById("file-preview");
        this.pewarnoanForm = document.getElementById("pewarnaan-form");

        this.batikFileInput = document.getElementById("batik-file-input");
        this.batikDropzone = document.getElementById("batik-dropzone");
        this.batikFilePreview = document.getElementById("batik-file-preview");

        this.dragCounter = 0;
        // Validate required elements exist
        if (!this.fileInput || !this.dropzone || !this.filePreview) {
            console.error(
                "PewarnaanUploadHandler: Missing required color upload elements",
            );
            return;
        }
        this.init();
    }

    init() {
        this.setupFormValidation();
        this.setupBatikUploadHandlers();
        this.setupColorUploadHandlers();
    }

    /**
     * Setup form submission validation
     */
    setupFormValidation() {
        this.pewarnoanForm.addEventListener("submit", (e) => {
            // Check if batik is selected via radio button
            const batikIdElement = document.querySelector(
                'input[name="batik_id"]:checked',
            );
            const batikIdValue = batikIdElement ? batikIdElement.value : "";

            // Check if color image is uploaded via file input
            const colorImageElement =
                document.getElementById("color-file-base64");
            const colorImageValue = colorImageElement
                ? colorImageElement.value
                : "";

            console.log("Form submitted", {
                hasBatikId: batikIdValue ? "ADA" : "TIDAK ADA",
                hasColorImage: colorImageValue ? "ADA" : "TIDAK ADA",
            });

            // Validate batik selection
            if (!batikIdValue) {
                e.preventDefault();
                alert("Pilih gambar batik terlebih dahulu!");
                return false;
            }

            // Validate color image upload
            if (!colorImageValue) {
                e.preventDefault();
                alert("Upload gambar warna terlebih dahulu!");
                return false;
            }
        });
    }

    /**
     * Setup batik file upload handlers
     */
    setupBatikUploadHandlers() {
        // Skip if batik elements don't exist (they're commented in template)
        if (!this.batikDropzone || !this.batikFileInput) {
            console.warn(
                "PewarnaanUploadHandler: Batik upload elements not found, skipping",
            );
            return;
        }

        ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
            this.batikDropzone.addEventListener(eventName, (e) =>
                this.preventDefaults(e),
            );
        });

        ["dragenter", "dragover"].forEach((eventName) => {
            this.batikDropzone.addEventListener(eventName, () => {
                this.batikDropzone.classList.add(
                    "border-primary",
                    "bg-primary/5",
                );
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            this.batikDropzone.addEventListener(eventName, () => {
                this.batikDropzone.classList.remove(
                    "border-primary",
                    "bg-primary/5",
                );
            });
        });

        this.batikDropzone.addEventListener("drop", (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                this.batikFileInput.files = files;
                this.handleBatikFileSelect({ files });
            }
        });

        // Track drag counter for proper drag-leave handling
        this.batikDropzone.addEventListener(
            "dragenter",
            () => this.dragCounter++,
        );
        this.batikDropzone.addEventListener(
            "dragleave",
            () => this.dragCounter--,
        );
    }

    /**
     * Setup color file upload handlers
     */
    setupColorUploadHandlers() {
        ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
            this.dropzone.addEventListener(eventName, (e) =>
                this.preventDefaults(e),
            );
        });

        ["dragenter", "dragover"].forEach((eventName) => {
            this.dropzone.addEventListener(eventName, () => {
                this.dropzone.classList.add("border-primary", "bg-primary/5");
            });
        });

        ["dragleave", "drop"].forEach((eventName) => {
            this.dropzone.addEventListener(eventName, () => {
                this.dropzone.classList.remove(
                    "border-primary",
                    "bg-primary/5",
                );
            });
        });

        this.dropzone.addEventListener("drop", (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                this.fileInput.files = files;
                this.handleFileSelect({ files });
            }
        });

        // Track drag counter for proper drag-leave handling
        this.dropzone.addEventListener("dragenter", () => this.dragCounter++);
        this.dropzone.addEventListener("dragleave", () => this.dragCounter--);
    }

    /**
     * Prevent default drag-drop behavior
     */
    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    /**
     * Handle batik file selection
     */
    handleBatikFileSelect(input) {
        if (!this.batikDropzone || !this.batikFilePreview) {
            return;
        }

        const file = input.files ? input.files[0] : null;
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
            alert("File terlalu besar. Maksimal 10MB.");
            return;
        }

        if (!file.type.startsWith("image/")) {
            alert("File harus berupa gambar.");
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById("batik-file-img").src = e.target.result;
            document.getElementById("batik-file-name").textContent = file.name;
            document.getElementById("batik-file-size").textContent =
                this.formatBytes(file.size);
            document.getElementById("batik-file-base64").value =
                e.target.result;

            this.batikDropzone.classList.add("hidden");
            this.batikFilePreview.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
    }

    /**
     * Reset batik file selection
     */
    resetBatikFile() {
        if (
            !this.batikFileInput ||
            !this.batikDropzone ||
            !this.batikFilePreview
        ) {
            return;
        }
        this.batikFileInput.value = "";
        this.batikDropzone.classList.remove("hidden");
        this.batikFilePreview.classList.add("hidden");
        document.getElementById("batik-file-base64").value = "";
    }

    /**
     * Handle color file selection
     */
    handleFileSelect(input) {
        const file = input.files ? input.files[0] : null;
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
            alert("File terlalu besar. Maksimal 1MB.");
            return;
        }

        if (!file.type.startsWith("image/")) {
            alert("File harus berupa gambar.");
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById("file-img").src = e.target.result;
            document.getElementById("file-name").textContent = file.name;
            document.getElementById("file-size").textContent = this.formatBytes(
                file.size,
            );
            document.getElementById("color-file-base64").value =
                e.target.result;

            this.dropzone.classList.add("hidden");
            this.filePreview.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
    }

    /**
     * Reset color file selection
     */
    resetFile() {
        this.fileInput.value = "";
        this.dropzone.classList.remove("hidden");
        this.filePreview.classList.add("hidden");
        document.getElementById("color-file-base64").value = "";
    }

    /**
     * Reset entire form and files
     */
    resetFormAndFile() {
        document.getElementById("pewarnaan-form").reset();
        this.resetBatikFile();
        this.resetFile();
    }

    /**
     * Format bytes to human readable format
     */
    formatBytes(bytes) {
        if (!bytes) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return (
            Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i]
        );
    }
}

// Export global functions for onclick handlers in template
// Define these BEFORE initialization to ensure they're available
window.handleFileSelect = function (input) {
    if (window.pewarnaanUpload) {
        window.pewarnaanUpload.handleFileSelect(input);
    }
};

window.handleBatikFileSelect = function (input) {
    if (window.pewarnaanUpload) {
        window.pewarnaanUpload.handleBatikFileSelect(input);
    }
};

window.resetFile = function () {
    if (window.pewarnaanUpload) {
        window.pewarnaanUpload.resetFile();
    }
};

window.resetBatikFile = function () {
    if (window.pewarnaanUpload) {
        window.pewarnaanUpload.resetBatikFile();
    }
};

window.resetFormAndFile = function () {
    if (window.pewarnaanUpload) {
        window.pewarnaanUpload.resetFormAndFile();
    }
};

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    window.pewarnaanUpload = new PewarnaanUploadHandler();
});
