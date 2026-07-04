/**
 * Pewarnaan PalletNet - Color Picker & Palette Management
 * File: resources/js/pewarnaan-pallnet.js
 */

class PewarnaanPalletNet {
    constructor(
        palettesKmeans = [],
        palettesHistogram = [],
        paletteMedianCut = [],
    ) {
        this.palettesKmeans = palettesKmeans;
        this.palettesHistogram = palettesHistogram;
        this.paletteMedianCut = paletteMedianCut;

        // Palette yang akan digunakan untuk pewarnaan (dimodifikasi oleh user)
        this.modifiedPalettes = {
            kmeans: [...palettesKmeans],
            histogram: [...palettesHistogram],
            median: [...paletteMedianCut],
        };

        // Color Picker State
        this.currentColorPickerData = {
            method: null,
            index: null,
            currentColor: "#000000",
        };

        // HSV Values
        this.currentH = 0;
        this.currentS = 100;
        this.currentV = 100;
        this.isDragging = false;

        // Click listener reference untuk color picker modal
        this.colorPickerClickListener = null;

        this.colorBox = document.getElementById("color-box-container");
        this.init();
    }

    /**
     * Initialize event listeners
     */
    init() {
        if (this.colorBox) {
            this.colorBox.addEventListener("mousedown", (e) =>
                this.handleMouseDown(e),
            );
            window.addEventListener("mousemove", (e) =>
                this.handleMouseMove(e),
            );
            window.addEventListener("mouseup", () => this.handleMouseUp());
        }

        this.setupBackdropListener();
    }

    setupBackdropListener() {
        const backdrop = document.getElementById("color-picker-backdrop");
        if (backdrop) {
            backdrop.addEventListener("click", () => this.closeColorPicker());
        }
    }

    /**
     * Open color picker modal untuk warna tertentu
     */
    openColorPicker(method, index) {
        const colorValue = this.modifiedPalettes[method]?.[index];
        if (!colorValue) return;

        const element = document.querySelector(
            `.palette-color-${method}-${index}`,
        );
        if (!element) return;

        this.currentColorPickerData = {
            method,
            index,
            currentColor: colorValue,
        };

        // Konversi hex ke HSV
        const hsv = this.hexToHsv(colorValue);
        this.currentH = hsv.h;
        this.currentS = hsv.s;
        this.currentV = hsv.v;

        this.updateColorPickerUI(colorValue, hsv);
        this.positionColorPickerModal(element);
    }

    /**
     * Update UI dalam color picker modal
     */
    updateColorPickerUI(colorValue, hsv) {
        const hexInput = document.getElementById("color-hex-input");
        const colorPreview = document.getElementById("color-preview");
        const hueSlider = document.getElementById("hue-slider");
        const baseColorLayer = document.getElementById("base-color-layer");
        const colorCursor = document.getElementById("color-cursor");

        if (hexInput) hexInput.value = colorValue.toUpperCase();
        if (colorPreview) colorPreview.style.backgroundColor = colorValue;
        if (hueSlider) hueSlider.value = hsv.h;
        if (baseColorLayer)
            baseColorLayer.style.backgroundColor = `hsl(${hsv.h}, 100%, 50%)`;

        if (this.colorBox && colorCursor) {
            const cursorX = (this.currentS / 100) * this.colorBox.offsetWidth;
            const cursorY =
                ((100 - this.currentV) / 100) * this.colorBox.offsetHeight;
            colorCursor.style.left = cursorX + "px";
            colorCursor.style.top = cursorY + "px";
        }
    }

    /**
     * Position modal di dekat elemen yang diklik
     */
    positionColorPickerModal(element) {
        const backdrop = document.getElementById("color-picker-backdrop");
        const modal = document.getElementById("color-picker-modal");
        if (!modal) return;

        if (backdrop) backdrop.classList.remove("hidden");
        modal.classList.remove("hidden");
        modal.style.left = "";
        modal.style.top = "";

        // Remove old click listener if exists (prevent memory leak)
        if (this.colorPickerClickListener) {
            document.removeEventListener("click", this.colorPickerClickListener);
        }
    }

    /**
     * Konversi hex ke HSV
     */
    hexToHsv(hex) {
        hex = hex.replace("#", "");
        let r = parseInt(hex.slice(0, 2), 16) / 255;
        let g = parseInt(hex.slice(2, 4), 16) / 255;
        let b = parseInt(hex.slice(4, 6), 16) / 255;

        let max = Math.max(r, g, b);
        let min = Math.min(r, g, b);
        let h = 0,
            s = 0,
            v = max;

        let d = max - min;
        s = max === 0 ? 0 : d / max;

        if (max === min) h = 0;
        else if (max === r) h = (g - b) / d + (g < b ? 6 : 0);
        else if (max === g) h = (b - r) / d + 2;
        else if (max === b) h = (r - g) / d + 4;
        h /= 6;

        return {
            h: Math.round(h * 360),
            s: Math.round(s * 100),
            v: Math.round(v * 100),
        };
    }

    /**
     * Update Hue dari slider
     */
    updateHue() {
        const hueSlider = document.getElementById("hue-slider");
        if (!hueSlider) return;

        this.currentH = parseInt(hueSlider.value);
        const baseColorLayer = document.getElementById("base-color-layer");
        if (baseColorLayer) {
            baseColorLayer.style.backgroundColor = `hsl(${this.currentH}, 100%, 50%)`;
        }
        this.updateFinalColor();
    }

    /**
     * Handle mouse down pada color box
     */
    handleMouseDown(e) {
        this.isDragging = true;
        this.handleColorSelect(e);
    }

    /**
     * Handle mouse move pada color box
     */
    handleMouseMove(e) {
        if (this.isDragging) {
            this.handleColorSelect(e);
        }
    }

    /**
     * Handle mouse up
     */
    handleMouseUp() {
        this.isDragging = false;
    }

    /**
     * Handle klik/drag pada color box
     */
    handleColorSelect(e) {
        if (!this.colorBox) return;

        const rect = this.colorBox.getBoundingClientRect();
        let x = e.clientX - rect.left;
        let y = e.clientY - rect.top;

        x = Math.max(0, Math.min(x, rect.width));
        y = Math.max(0, Math.min(y, rect.height));

        // Saturation (X) dan Value/Brightness (Y)
        this.currentS = (x / rect.width) * 100;
        this.currentV = 100 - (y / rect.height) * 100;

        // Update cursor
        const cursor = document.getElementById("color-cursor");
        if (cursor) {
            cursor.style.left = x + "px";
            cursor.style.top = y + "px";
        }

        this.updateFinalColor();
    }

    /**
     * Konversi HSV ke HEX
     */
    hsvToHex(h, s, v) {
        v /= 100;
        const sAbs = s / 100;
        let c = v * sAbs;
        let x = c * (1 - Math.abs(((h / 60) % 2) - 1));
        let m = v - c;
        let r, g, b;

        if (h < 60) [r, g, b] = [c, x, 0];
        else if (h < 120) [r, g, b] = [x, c, 0];
        else if (h < 180) [r, g, b] = [0, c, x];
        else if (h < 240) [r, g, b] = [0, x, c];
        else if (h < 300) [r, g, b] = [x, 0, c];
        else [r, g, b] = [c, 0, x];

        const toHex = (n) =>
            Math.round((n + m) * 255)
                .toString(16)
                .padStart(2, "0");
        return `#${toHex(r)}${toHex(g)}${toHex(b)}`;
    }

    /**
     * Update final color display
     */
    updateFinalColor() {
        const hex = this.hsvToHex(this.currentH, this.currentS, this.currentV);
        const colorPreview = document.getElementById("color-preview");
        const colorHexInput = document.getElementById("color-hex-input");

        if (colorPreview) colorPreview.style.backgroundColor = hex;
        if (colorHexInput) colorHexInput.value = hex.toUpperCase();
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
            // Remove click listener properly
            if (this.colorPickerClickListener) {
                document.removeEventListener(
                    "click",
                    this.colorPickerClickListener,
                );
                this.colorPickerClickListener = null;
            }
        }
    }

    /**
     * Handle click outside color picker
     */
    handleColorPickerOutsideClick(e) {
        const modal = document.getElementById("color-picker-modal");
        if (!modal || modal.classList.contains("hidden")) return;

        if (modal.contains(e.target)) return;

        // Check if click is on any palette color
        let isOnPalette = false;
        if (e.target.classList) {
            const classList = Array.from(e.target.classList);
            isOnPalette = classList.some((className) =>
                className.startsWith("palette-color-"),
            );
        }

        if (!isOnPalette) {
            this.closeColorPicker();
        }
    }

    /**
     * Update warna dari hex input
     */
    updateFromHexInput() {
        const hexInput = document.getElementById("color-hex-input");
        if (!hexInput) return;

        const hexValue = hexInput.value.trim();
        if (!/^#[0-9A-F]{6}$/i.test(hexValue)) return;

        const colorPreview = document.getElementById("color-preview");
        if (colorPreview) colorPreview.style.backgroundColor = hexValue;

        const hsv = this.hexToHsv(hexValue);
        this.currentH = hsv.h;
        this.currentS = hsv.s;
        this.currentV = hsv.v;

        const hueSlider = document.getElementById("hue-slider");
        if (hueSlider) hueSlider.value = hsv.h;

        const baseColorLayer = document.getElementById("base-color-layer");
        if (baseColorLayer)
            baseColorLayer.style.backgroundColor = `hsl(${hsv.h}, 100%, 50%)`;

        if (this.colorBox) {
            const cursorX = (this.currentS / 100) * this.colorBox.offsetWidth;
            const cursorY =
                ((100 - this.currentV) / 100) * this.colorBox.offsetHeight;
            const colorCursor = document.getElementById("color-cursor");
            if (colorCursor) {
                colorCursor.style.left = cursorX + "px";
                colorCursor.style.top = cursorY + "px";
            }
        }
    }

    /**
     * Apply color dari color picker
     */
    applyColorPicker() {
        const hexInput = document.getElementById("color-hex-input");
        if (!hexInput) return;

        const hexValue = hexInput.value.trim();

        if (!/^#[0-9A-F]{6}$/i.test(hexValue)) {
            alert("Format warna tidak valid. Gunakan format #RRGGBB");
            return;
        }

        if (
            !this.currentColorPickerData.method ||
            this.currentColorPickerData.index === null
        ) {
            return;
        }

        this.updatePaletteColor(
            this.currentColorPickerData.method,
            this.currentColorPickerData.index,
            hexValue,
        );

        this.closeColorPicker();
    }

    /**
     * Update palette color di modified palettes dan UI
     */
    updatePaletteColor(method, index, newColor) {
        this.modifiedPalettes[method][index] = newColor;

        // Update color box display
        const colorBoxes = document.querySelectorAll(
            `.palette-color-${method}-${index}`,
        );
        colorBoxes.forEach((box) => {
            box.style.backgroundColor = newColor;
        });

        // Update hex label
        const label = document.getElementById(`label-${method}-${index}`);
        if (label) {
            label.textContent = newColor.toUpperCase();
        }

        console.log(`Updated ${method}[${index}] to ${newColor}`);
    }

    /**
     * Proses pewarnaan dengan palette tertentu
     */
    async processWithPalette(method, palette, batikImage, colorImage) {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content || "";
        const apiUrl =
            document.querySelector('meta[name="api-colorize-url"]')?.content ||
            "";

        try {
            const response = await fetch(apiUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    batik_image: batikImage,
                    color_image: colorImage,
                    palette: palette,
                    skip_extract: true,
                    method: method,
                }),
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(
                    data.message || `Gagal memproses pewarnaan ${method}`,
                );
            }

            const result = data.result;
            const resultUrl = result.result_image_url;

            if (!resultUrl) {
                throw new Error(
                    `Server tidak mengembalikan URL gambar hasil untuk ${method}`,
                );
            }

            console.log(`Result URL (${method}):`, resultUrl);

            return {
                success: true,
                method,
                image_url: resultUrl,
                processing_time_ms: result.processing_time_ms,
            };
        } catch (error) {
            console.error(`Process ${method} error:`, error);
            throw error;
        }
    }

    /**
     * Handle button colorize - process all 3 methods in parallel
     */
    async handleColorize(batikImage, colorImage, colorSourceType = "upload") {
        const processButton = document.getElementById("process-button");
        const buttonText = document.getElementById("button-text");
        const loadingSpinner = document.getElementById("loading-spinner");
        const processingModal = document.getElementById("processing-modal");

        if (
            !processButton ||
            !buttonText ||
            !loadingSpinner ||
            !processingModal
        ) {
            console.error("Required elements not found");
            return;
        }

        // Disable button
        processButton.disabled = true;
        buttonText.textContent = "Sedang Memproses...";
        loadingSpinner.classList.remove("hidden");
        processingModal.classList.remove("hidden");

        try {
            if (!batikImage) {
                throw new Error("Gambar batik sumber tidak ditemukan.");
            }

            console.log("Colorize with colorSourceType:", colorSourceType);

            // For manual palette, we only need to validate kmeans palette
            // For upload palette, validate all 3 palettes
            if (colorSourceType === "manual") {
                if (this.modifiedPalettes.kmeans.length === 0) {
                    throw new Error(
                        "Palette warna tidak lengkap. Silakan pilih warna terlebih dahulu.",
                    );
                }

                // Process only 1 metode (kmeans) untuk manual palette
                const results = await Promise.all([
                    this.processWithPalette(
                        "kmeans",
                        this.modifiedPalettes.kmeans,
                        batikImage,
                        colorImage,
                    ),
                ]);

                console.log("Manual palette processed successfully", results);

                // Save results dan redirect
                await this.saveResultsAndRedirect(
                    results,
                    batikImage,
                    colorImage,
                );
            } else {
                // Upload palette - validate all 3 metode dan process all
                if (
                    this.modifiedPalettes.kmeans.length === 0 ||
                    this.modifiedPalettes.histogram.length === 0 ||
                    this.modifiedPalettes.median.length === 0
                ) {
                    throw new Error(
                        "Palette warna tidak lengkap. Silakan upload ulang gambar warna Anda.",
                    );
                }

                if (!colorImage) {
                    throw new Error(
                        "Tidak ada gambar warna yang diunggah. Silakan upload gambar warna terlebih dahulu.",
                    );
                }

                // Process 3 metode secara paralel
                const results = await Promise.all([
                    this.processWithPalette(
                        "kmeans",
                        this.modifiedPalettes.kmeans,
                        batikImage,
                        colorImage,
                    ),
                    this.processWithPalette(
                        "histogram",
                        this.modifiedPalettes.histogram,
                        batikImage,
                        colorImage,
                    ),
                    this.processWithPalette(
                        "median",
                        this.modifiedPalettes.median,
                        batikImage,
                        colorImage,
                    ),
                ]);

                console.log("All results processed successfully", results);

                // Save results dan redirect
                await this.saveResultsAndRedirect(
                    results,
                    batikImage,
                    colorImage,
                );
            }
        } catch (error) {
            console.error("Colorize error:", error);
            alert("Error: " + error.message);
            processingModal.classList.add("hidden");
        } finally {
            // Re-enable button
            processButton.disabled = false;
            buttonText.textContent = "Proses Gambar";
            loadingSpinner.classList.add("hidden");
        }
    }

    /**
     * Simpan hasil ke session dan redirect
     */
    async saveResultsAndRedirect(results, batikImage, colorImage) {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content || "";
        const saveUrl =
            document.querySelector('meta[name="api-save-results-url"]')
                ?.content || "";
        const outputUrl =
            document.querySelector('meta[name="output-url"]')?.content || "";

        try {
            const response = await fetch(saveUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    batik_image: batikImage,
                    color_image: colorImage,
                    results: {
                        kmeans: results.find((r) => r.method === "kmeans"),
                        histogram: results.find(
                            (r) => r.method === "histogram",
                        ),
                        median: results.find((r) => r.method === "median"),
                    },
                }),
            });

            if (response.ok) {
                window.location.href = outputUrl;
            } else {
                throw new Error("Gagal menyimpan hasil");
            }
        } catch (error) {
            console.error("Save results error:", error);
            alert("Error menyimpan hasil: " + error.message);
        }
    }
}

// Export untuk digunakan di browser
if (typeof module !== "undefined" && module.exports) {
    module.exports = PewarnaanPalletNet;
}
