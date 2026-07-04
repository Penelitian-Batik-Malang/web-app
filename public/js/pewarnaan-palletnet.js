/**
 * PewarnaanPalletNet - Color Palette Management & Image Colorization
 * Handles palette extraction, color picking, and colorization via API
 */
class PewarnaanPalletNet {
    constructor(
        palettesKmeans = [],
        palettesHistogram = [],
        paletteMedianCut = [],
    ) {
        this.palettesKmeans = palettesKmeans || [];
        this.palettesHistogram = palettesHistogram || [];
        this.paletteMedianCut = paletteMedianCut || [];

        // Color picker state
        this.currentPickerMethod = null;
        this.currentPickerIndex = null;
        this.currentHue = 0;
        this.currentSaturation = 100;
        this.currentBrightness = 100;

        // Endpoints from meta tags
        this.colorizeUrl =
            document.querySelector('meta[name="api-colorize-url"]')?.content ||
            "/api/colorize/palet";
        this.saveResultsUrl =
            document.querySelector('meta[name="api-save-results-url"]')
                ?.content || "/api/save-results";
        this.outputUrl =
            document.querySelector('meta[name="output-url"]')?.content ||
            "/pewarnaan/output-gambar";

        this.setupColorPickerListeners();
        this.setupBackdropListener();
    }

    /**
     * Setup color picker canvas listeners
     */
    setupColorPickerListeners() {
        const colorBox = document.getElementById("color-box-container");
        if (!colorBox) return;

        colorBox.addEventListener("click", (e) => this.selectColorFromBox(e));
        colorBox.addEventListener("mousemove", (e) => {
            if (e.buttons === 1) this.selectColorFromBox(e);
        });
    }

    /**
     * Setup backdrop click to close modal
     */
    setupBackdropListener() {
        const backdrop = document.getElementById("color-picker-backdrop");
        if (backdrop) {
            backdrop.addEventListener("click", () => this.closeColorPicker());
        }
    }

    /**
     * Open color picker modal
     */
    openColorPicker(method, index) {
        this.currentPickerMethod = method;
        this.currentPickerIndex = index;

        // Get current color
        const currentColor = this.getCurrentPaletteColor(method, index);
        this.hexToHsv(currentColor);

        // Show backdrop and modal
        const backdrop = document.getElementById("color-picker-backdrop");
        const modal = document.getElementById("color-picker-modal");
        if (backdrop) backdrop.classList.remove("hidden");
        if (modal) {
            modal.classList.remove("hidden");
            this.updateColorPreview();
            this.updateColorBoxGradient();
        }
    }

    /**
     * Close color picker modal
     */
    closeColorPicker() {
        const backdrop = document.getElementById("color-picker-backdrop");
        const modal = document.getElementById("color-picker-modal");
        if (backdrop) backdrop.classList.add("hidden");
        if (modal) {
            modal.classList.add("hidden");
        }
        this.currentPickerMethod = null;
        this.currentPickerIndex = null;
    }

    /**
     * Get current palette color
     */
    getCurrentPaletteColor(method, index) {
        let palette = [];
        switch (method) {
            case "kmeans":
                palette = this.palettesKmeans;
                break;
            case "histogram":
                palette = this.palettesHistogram;
                break;
            case "median":
                palette = this.paletteMedianCut;
                break;
        }
        return palette[index] || "#FF0000";
    }

    /**
     * Convert HEX to HSV
     */
    hexToHsv(hex) {
        // Remove # if present
        hex = hex.replace("#", "");

        // Convert to RGB
        const r = parseInt(hex.substring(0, 2), 16) / 255;
        const g = parseInt(hex.substring(2, 4), 16) / 255;
        const b = parseInt(hex.substring(4, 6), 16) / 255;

        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        const delta = max - min;

        // Hue
        let h = 0;
        if (delta !== 0) {
            if (max === r) h = ((g - b) / delta + (g < b ? 6 : 0)) / 6;
            else if (max === g) h = ((b - r) / delta + 2) / 6;
            else h = ((r - g) / delta + 4) / 6;
        }

        // Saturation
        const s = max === 0 ? 0 : delta / max;

        // Value
        const v = max;

        this.currentHue = h * 360;
        this.currentSaturation = s * 100;
        this.currentBrightness = v * 100;

        // Update slider
        const hueSlider = document.getElementById("hue-slider");
        if (hueSlider) {
            hueSlider.value = this.currentHue;
        }
    }

    /**
     * Convert HSV to HEX
     */
    hsvToHex() {
        const h = this.currentHue / 360;
        const s = this.currentSaturation / 100;
        const v = this.currentBrightness / 100;

        const c = v * s;
        const hh = (h * 6) % 6;
        const x = c * (1 - Math.abs((hh % 2) - 1));
        const m = v - c;

        let r, g, b;
        if (hh < 1) {
            r = c;
            g = x;
            b = 0;
        } else if (hh < 2) {
            r = x;
            g = c;
            b = 0;
        } else if (hh < 3) {
            r = 0;
            g = c;
            b = x;
        } else if (hh < 4) {
            r = 0;
            g = x;
            b = c;
        } else if (hh < 5) {
            r = x;
            g = 0;
            b = c;
        } else {
            r = c;
            g = 0;
            b = x;
        }

        const toHex = (val) => {
            const hex = Math.round((val + m) * 255).toString(16);
            return hex.length === 1 ? "0" + hex : hex;
        };

        return (
            "#" +
            toHex(r).toUpperCase() +
            toHex(g).toUpperCase() +
            toHex(b).toUpperCase()
        );
    }

    /**
     * Update hue from slider
     */
    updateHue() {
        const hueSlider = document.getElementById("hue-slider");
        if (hueSlider) {
            this.currentHue = parseInt(hueSlider.value);
        }
        this.updateColorBoxGradient();
        this.updateColorPreview();
    }

    /**
     * Update color preview
     */
    updateColorPreview() {
        const hex = this.hsvToHex();

        const preview = document.getElementById("color-preview");
        if (preview) {
            preview.style.backgroundColor = hex;
        }

        const hexInput = document.getElementById("color-hex-input");
        if (hexInput) {
            hexInput.value = hex;
        }
    }

    /**
     * Update color box gradient based on hue
     */
    updateColorBoxGradient() {
        const baseHue = this.currentHue;
        const hsvColor = `hsl(${baseHue}, 100%, 50%)`;

        const baseLayer = document.getElementById("base-color-layer");
        if (baseLayer) {
            baseLayer.style.backgroundColor = hsvColor;
        }
    }

    /**
     * Select color from color box (saturation/brightness box)
     */
    selectColorFromBox(e) {
        const box = document.getElementById("color-box-container");
        if (!box) return;

        const rect = box.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const saturation = (x / rect.width) * 100;
        const brightness = 100 - (y / rect.height) * 100;

        this.currentSaturation = Math.max(0, Math.min(100, saturation));
        this.currentBrightness = Math.max(0, Math.min(100, brightness));

        // Update cursor position
        const cursor = document.getElementById("color-cursor");
        if (cursor) {
            cursor.style.left = x + "px";
            cursor.style.top = y + "px";
        }

        this.updateColorPreview();
    }

    /**
     * Update from hex input
     */
    updateFromHexInput() {
        const hexInput = document.getElementById("color-hex-input");
        if (hexInput && hexInput.value) {
            this.hexToHsv(hexInput.value);
            this.updateColorBoxGradient();
            this.updateColorPreview();
        }
    }

    /**
     * Apply selected color to palette
     */
    applyColorPicker() {
        if (!this.currentPickerMethod || this.currentPickerIndex === null)
            return;

        const hex = this.hsvToHex();
        this.updatePaletteColor(
            this.currentPickerMethod,
            this.currentPickerIndex,
            hex,
        );
        this.closeColorPicker();
    }

    /**
     * Update palette color in UI
     */
    updatePaletteColor(method, index, color) {
        // Update the palette array
        switch (method) {
            case "kmeans":
                this.palettesKmeans[index] = color;
                break;
            case "histogram":
                this.palettesHistogram[index] = color;
                break;
            case "median":
                this.paletteMedianCut[index] = color;
                break;
        }

        // Update UI
        const colorElement = document.querySelector(
            `.palette-color-${method}-${index}`,
        );
        if (colorElement) {
            colorElement.style.backgroundColor = color;
        }

        const labelElement = document.getElementById(
            `label-${method}-${index}`,
        );
        if (labelElement) {
            labelElement.textContent = color.toUpperCase();
        }

        const inputElement = document.getElementById(
            `color-${method}-${index}`,
        );
        if (inputElement) {
            inputElement.value = color;
        }
    }

    /**
     * Handle colorize button click
     */
    async handleColorize(batikImage, colorImage, colorSourceType = "upload") {
        try {
            // Validate inputs
            if (!batikImage) {
                alert("Gambar batik belum di-upload");
                return;
            }

            // Show processing modal
            const processingModal = document.getElementById("processing-modal");
            if (processingModal) {
                processingModal.classList.remove("hidden");
            }

            // Disable button
            const processButton = document.getElementById("process-button");
            if (processButton) {
                processButton.disabled = true;
            }

            console.log(
                "Starting colorization with colorSourceType:",
                colorSourceType,
            );

            // Prepare data for all three methods or single method for manual palette
            const results = {};
            const methods =
                colorSourceType === "manual"
                    ? ["kmeans"]
                    : ["kmeans", "histogram", "median"];

            console.log("Processing methods:", methods);

            // Process each method in parallel
            const promises = methods.map((method) =>
                this.colorizeMethod(method, batikImage).catch((error) => {
                    console.error(
                        `Method ${method} failed (network error):`,
                        error,
                    );
                    // Network error or JSON parse error
                    return {
                        success: false,
                        result: null,
                        message: error.message || "Network error",
                        method: method,
                    };
                }),
            );

            const responses = await Promise.all(promises);

            // Collect results
            let hasError = false;
            methods.forEach((method, index) => {
                results[method] = responses[index];
                if (!responses[index].success) {
                    hasError = true;
                }
            });

            if (hasError) {
                console.warn("Some methods failed:", results);
            }

            console.log("Colorization complete, saving results...");

            // Save results to session (include colorImage)
            await this.saveResults(results, batikImage, colorImage);

            // Hide processing modal
            if (processingModal) {
                processingModal.classList.add("hidden");
            }

            // Redirect to output page
            console.log("Redirecting to output page:", this.outputUrl);
            window.location.href = this.outputUrl;
        } catch (error) {
            console.error("Colorize error:", error);

            // Hide processing modal
            const processingModal = document.getElementById("processing-modal");
            if (processingModal) {
                processingModal.classList.add("hidden");
            }

            // Enable button
            const processButton = document.getElementById("process-button");
            if (processButton) {
                processButton.disabled = false;
            }

            alert("Error saat memproses pewarnaan: " + error.message);
        }
    }

    /**
     * Colorize with specific method
     */
    async colorizeMethod(method, batikImage) {
        try {
            const palette = this.getPaletteForMethod(method);

            console.log(`Colorizing with ${method} method...`, {
                paletteLength: palette.length,
                paletteColors: palette,
            });

            // Get CSRF token
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.content ||
                "";

            const payload = {
                batik_image: batikImage,
                palette: palette,
                method: method,
            };

            console.log(`Sending to ${this.colorizeUrl}:`, payload);

            const response = await fetch(this.colorizeUrl, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(payload),
            });

            const responseData = await response.json();

            console.log(`Response from ${method}:`, {
                status: response.status,
                data: responseData,
            });

            if (!response.ok) {
                // Return error response instead of throwing
                return {
                    success: false,
                    result: null,
                    message:
                        responseData.message ||
                        `HTTP Error: ${response.status}`,
                    status: response.status,
                };
            }

            return responseData;
        } catch (error) {
            // Network error or JSON parse error
            console.error(`Fetch error for ${method}:`, error);
            throw error; // Re-throw so it's caught by the caller's catch block
        }
    }

    /**
     * Get palette for method
     */
    getPaletteForMethod(method) {
        switch (method) {
            case "kmeans":
                return this.palettesKmeans;
            case "histogram":
                return this.palettesHistogram;
            case "median":
                return this.paletteMedianCut;
            default:
                return [];
        }
    }

    /**
     * Save results to session
     */
    async saveResults(results, batikImage, colorImage = null) {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content || "";

        const payload = {
            colorize_results: results,
            colorize_batik_image: batikImage,
        };

        // Include color image if provided
        if (colorImage) {
            payload.colorize_color_image = colorImage;
        }

        const response = await fetch(this.saveResultsUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || "Failed to save results");
        }

        return await response.json();
    }
}

// Export for use
if (typeof module !== "undefined" && module.exports) {
    module.exports = PewarnaanPalletNet;
}
