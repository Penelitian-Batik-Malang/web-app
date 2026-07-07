window.ColorSearchModal = {
    _state: {},
    MAX_INPUT_SIZE: 50 * 1024 * 1024,
    MAX_UPLOAD_SIZE: 45 * 1024 * 1024,
    ALERT_TIMEOUT_MS: 4200,

    _getState(id) {
        if (!this._state[id]) {
            this._state[id] = {
                file: null,
                palettes: [],
                selectedPaletteIndexes: [],
                recommendations: [],
                alertTimer: null,
                paletteFetchFailed: false,
            };
        }
        return this._state[id];
    },

    open(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.remove("hidden");
        modal.classList.add("flex");
        document.body.style.overflow = "hidden";
        this._clearAlert(id);
        this._syncActionSection(id);
        this._syncRefreshButton(id);
    },

    close(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add("hidden");
        modal.classList.remove("flex");
        document.body.style.overflow = "";
        this._clearAlert(id);
    },

    handleBackdropClick(event, id) {
        if (event.target === document.getElementById(id)) {
            this.close(id);
        }
    },

    handleDragOver(event, id) {
        event.preventDefault();
        const zone = document.getElementById(`${id}-dropzone`);
        if (zone) zone.classList.add("border-primary", "bg-primary/5");
    },

    handleDragLeave(event, id) {
        event.preventDefault();
        const zone = document.getElementById(`${id}-dropzone`);
        if (zone) zone.classList.remove("border-primary", "bg-primary/5");
    },

    handleDrop(event, id) {
        event.preventDefault();
        const zone = document.getElementById(`${id}-dropzone`);
        if (zone) zone.classList.remove("border-primary", "bg-primary/5");

        const file = event.dataTransfer?.files?.[0];
        this.handleFile(file, id);
    },

    async handleFile(file, id) {
        if (!file) return;

        const validTypes = ["image/jpeg", "image/png", "image/webp"];

        if (!validTypes.includes(file.type)) {
            this._notify(
                id,
                "warning",
                "Format gambar harus JPG, PNG, atau WEBP.",
            );
            return;
        }

        if (file.size > this.MAX_INPUT_SIZE) {
            this._notify(id, "warning", "Ukuran gambar maksimal 50MB.");
            return;
        }

        let uploadFile = file;
        if (file.size > this.MAX_UPLOAD_SIZE) {
            uploadFile = await this._optimizeImage(file, this.MAX_UPLOAD_SIZE);
            if (!uploadFile) {
                this._notify(
                    id,
                    "error",
                    "Gambar terlalu besar untuk dikirim. Coba pilih gambar dengan resolusi lebih kecil.",
                );
                return;
            }
            this._notify(
                id,
                "info",
                "Gambar berhasil dioptimasi agar aman untuk upload.",
            );
        }

        const state = this._getState(id);
        state.file = uploadFile;
        state.palettes = [];
        state.selectedPaletteIndexes = [];
        state.recommendations = [];
        state.paletteFetchFailed = false;

        const section = document.getElementById(`${id}-recommend-section`);
        const list = document.getElementById(`${id}-recommend-list`);
        const count = document.getElementById(`${id}-recommend-count`);
        if (section) section.classList.add("hidden");
        if (list) list.innerHTML = "";
        if (count) count.textContent = "";

        this._renderPalettes(id);
        this._syncActionSection(id);
        this._syncRefreshButton(id);

        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById(`${id}-preview`);
            const uploadState = document.getElementById(`${id}-upload-state`);
            if (preview) {
                preview.src = e.target?.result || "";
                preview.classList.remove("hidden");
            }
            if (uploadState) uploadState.classList.add("hidden");
        };
        reader.readAsDataURL(uploadFile);

        await this.fetchPalette(id);
    },

    _notify(id, type, message) {
        const alertEl = document.getElementById(`${id}-alert`);
        const alertIcon = document.getElementById(`${id}-alert-icon`);
        const alertMessage = document.getElementById(`${id}-alert-message`);
        if (!alertEl || !alertIcon || !alertMessage) return;

        const variants = {
            info: {
                icon: "bi-info-circle-fill",
                className: "border-cyan-700/60 bg-cyan-950/30 text-cyan-200",
            },
            warning: {
                icon: "bi-exclamation-triangle-fill",
                className: "border-amber-700/60 bg-amber-950/30 text-amber-200",
            },
            error: {
                icon: "bi-x-octagon-fill",
                className: "border-red-700/60 bg-red-950/30 text-red-200",
            },
        };

        const variant = variants[type] || variants.info;
        alertEl.className = `rounded-xl border px-4 py-3 text-sm ${variant.className}`;
        alertEl.classList.remove("hidden");

        alertIcon.className = `bi ${variant.icon} mt-0.5`;
        alertMessage.textContent = message;

        const state = this._getState(id);
        if (state.alertTimer) {
            clearTimeout(state.alertTimer);
        }

        state.alertTimer = setTimeout(() => {
            this._clearAlert(id);
        }, this.ALERT_TIMEOUT_MS);
    },

    _clearAlert(id) {
        const state = this._getState(id);
        if (state.alertTimer) {
            clearTimeout(state.alertTimer);
            state.alertTimer = null;
        }

        const alertEl = document.getElementById(`${id}-alert`);
        if (alertEl) {
            alertEl.classList.add("hidden");
        }
    },

    _setScanButtonState(id) {
        const state = this._getState(id);
        const scanBtn = document.getElementById(`${id}-scan-btn`);
        const actionNote = document.getElementById(`${id}-action-note`);
        if (!scanBtn || !actionNote) return;

        if (!state.selectedPaletteIndexes.length) {
            scanBtn.disabled = true;
            scanBtn.classList.add("opacity-60", "cursor-not-allowed");
            actionNote.textContent =
                "Pilih minimal 1 palette warna untuk melakukan pencarian rekomendasi.";
            return;
        }

        scanBtn.disabled = false;
        scanBtn.classList.remove("opacity-60", "cursor-not-allowed");
        actionNote.textContent =
            "Palette siap. Klik Pindai Gambar untuk mengambil rekomendasi.";
    },

    _syncActionSection(id) {
        const state = this._getState(id);
        const actionSection = document.getElementById(`${id}-action-section`);
        if (!actionSection) return;

        const visible = !!state.file && state.palettes.length > 0;
        actionSection.classList.toggle("hidden", !visible);
        if (visible) {
            this._setScanButtonState(id);
        }
    },

    _syncRefreshButton(id) {
        const state = this._getState(id);
        const wrap = document.getElementById(`${id}-refresh-wrap`);
        const btn = document.getElementById(`${id}-refresh-btn`);
        if (!wrap || !btn) return;

        const visible = !!state.file;
        wrap.classList.toggle("hidden", !visible);

        if (!visible) return;

        btn.disabled = false;
        btn.classList.remove(
            "opacity-60",
            "cursor-not-allowed",
            "border-red-700/60",
            "bg-red-950/30",
            "text-red-200",
        );
        btn.classList.add(
            "border-amber-700/50",
            "bg-amber-950/30",
            "text-amber-300",
        );
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh Palette';

        if (state.paletteFetchFailed) {
            btn.classList.remove(
                "border-amber-700/50",
                "bg-amber-950/30",
                "text-amber-300",
            );
            btn.classList.add(
                "border-red-700/60",
                "bg-red-950/30",
                "text-red-200",
            );
            btn.innerHTML =
                '<i class="bi bi-arrow-repeat"></i> Coba Ambil Palette Lagi';
        }
    },

    async refreshPalette(id) {
        const state = this._getState(id);
        if (!state.file) {
            this._notify(id, "warning", "Unggah gambar terlebih dahulu.");
            return;
        }

        await this.fetchPalette(id);
    },

    async fetchPalette(id) {
        const state = this._getState(id);
        const modal = document.getElementById(id);
        const paletteEndpoint = modal?.dataset?.paletteEndpoint || "";
        const refreshBtn = document.getElementById(`${id}-refresh-btn`);

        if (!paletteEndpoint) {
            this._notify(id, "error", "Endpoint palette belum diset.");
            return false;
        }

        if (!state.file) {
            this._notify(
                id,
                "warning",
                "Pilih atau unggah gambar terlebih dahulu.",
            );
            return false;
        }

        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content || "";

        this._notify(id, "info", "Mengekstrak palette warna dari gambar...");
        state.paletteFetchFailed = false;
        this._syncRefreshButton(id);

        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.classList.add("opacity-60", "cursor-not-allowed");
            refreshBtn.innerHTML =
                '<i class="bi bi-arrow-repeat animate-spin"></i> Memuat Palette...';
        }

        try {
            const paletteFormData = new FormData();
            paletteFormData.append("image", state.file);
            paletteFormData.append("num_clusters", "5");
            paletteFormData.append("_token", csrfToken);

            const paletteResponse = await fetch(paletteEndpoint, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: paletteFormData,
            });

            const paletteData = await paletteResponse.json();
            if (!paletteResponse.ok || !paletteData?.success) {
                throw new Error(
                    this._extractApiError(
                        paletteData,
                        "Gagal mengambil palette warna.",
                    ),
                );
            }

            const paletteResult = paletteData.result || {};
            state.palettes = paletteResult.palettes || [];
            state.selectedPaletteIndexes =
                paletteResult.selected_palette_indexes ||
                state.palettes.map((palette) => palette.index);

            this._renderPalettes(id);
            this._syncActionSection(id);
            state.paletteFetchFailed = false;
            this._syncRefreshButton(id);

            if (!state.palettes.length) {
                this._notify(
                    id,
                    "warning",
                    "Palette tidak ditemukan dari gambar ini.",
                );
                return false;
            }

            this._notify(
                id,
                "info",
                `Palette berhasil diambil (${state.palettes.length} warna). Pilih warna yang diinginkan.`,
            );
            return true;
        } catch (error) {
            state.palettes = [];
            state.selectedPaletteIndexes = [];
            state.paletteFetchFailed = true;
            this._renderPalettes(id);
            this._syncActionSection(id);
            this._syncRefreshButton(id);
            this._notify(
                id,
                "error",
                error?.message || "Terjadi kesalahan saat mengambil palette.",
            );
            return false;
        } finally {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.classList.remove("opacity-60", "cursor-not-allowed");
            }
            this._syncRefreshButton(id);
        }
    },

    _loadImage(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                URL.revokeObjectURL(url);
                resolve(img);
            };
            img.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error("Gagal membaca gambar."));
            };
            img.src = url;
        });
    },

    _blobFromCanvas(canvas, quality) {
        return new Promise((resolve) => {
            canvas.toBlob((blob) => resolve(blob), "image/jpeg", quality);
        });
    },

    async _optimizeImage(file, maxBytes) {
        try {
            const image = await this._loadImage(file);
            const maxWidth = 1600;
            const maxHeight = 1600;

            let width = image.width;
            let height = image.height;
            const ratio = Math.min(maxWidth / width, maxHeight / height, 1);
            width = Math.max(1, Math.floor(width * ratio));
            height = Math.max(1, Math.floor(height * ratio));

            const canvas = document.createElement("canvas");
            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext("2d");
            if (!ctx) return null;
            ctx.drawImage(image, 0, 0, width, height);

            const qualities = [0.9, 0.82, 0.74, 0.66, 0.58, 0.5, 0.42];
            for (const quality of qualities) {
                const blob = await this._blobFromCanvas(canvas, quality);
                if (blob && blob.size <= maxBytes) {
                    return new File([blob], this._toJpegFileName(file.name), {
                        type: "image/jpeg",
                        lastModified: Date.now(),
                    });
                }
            }

            return null;
        } catch (_error) {
            return null;
        }
    },

    _toJpegFileName(originalName) {
        const dotIndex = originalName.lastIndexOf(".");
        if (dotIndex === -1) return `${originalName}.jpg`;
        return `${originalName.slice(0, dotIndex)}.jpg`;
    },

    _extractApiError(payload, fallbackMessage) {
        if (!payload || typeof payload !== "object") return fallbackMessage;

        const imageErrors = payload?.errors?.image;
        if (Array.isArray(imageErrors) && imageErrors.length > 0) {
            return imageErrors[0];
        }

        if (typeof payload.message === "string" && payload.message.trim()) {
            return payload.message;
        }

        return fallbackMessage;
    },

    async search(id) {
        const state = this._getState(id);
        const modal = document.getElementById(id);
        const recommendationEndpoint =
            modal?.dataset?.recommendationEndpoint || "";
        const scanBtn = document.getElementById(`${id}-scan-btn`);
        const actionLabel = document.getElementById(`${id}-action-label`);

        if (!recommendationEndpoint) {
            this._notify(id, "error", "Endpoint rekomendasi belum diset.");
            return;
        }

        if (!state.file) {
            this._notify(
                id,
                "warning",
                "Pilih atau unggah gambar terlebih dahulu.",
            );
            return;
        }

        if (!state.palettes.length) {
            this._notify(
                id,
                "warning",
                "Palette belum tersedia. Unggah ulang gambar untuk ekstraksi palette.",
            );
            return;
        }

        if (!state.selectedPaletteIndexes.length) {
            this._notify(id, "warning", "Pilih minimal 1 palette warna.");
            return;
        }

        if (scanBtn) {
            scanBtn.disabled = true;
            scanBtn.textContent = "Memproses...";
        }

        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.content || "";

        try {
            this._notify(id, "info", "Mengambil rekomendasi batik...");

            const recommendationFormData = new FormData();
            recommendationFormData.append("image", state.file);
            recommendationFormData.append("num_clusters", "5");
            recommendationFormData.append("top_k", "15");
            state.selectedPaletteIndexes.forEach((index) => {
                recommendationFormData.append(
                    "selected_colors[]",
                    String(index),
                );
            });
            recommendationFormData.append("_token", csrfToken);

            const recommendationResponse = await fetch(recommendationEndpoint, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: recommendationFormData,
            });

            const recommendationData = await recommendationResponse.json();
            if (!recommendationResponse.ok || !recommendationData?.success) {
                throw new Error(
                    this._extractApiError(
                        recommendationData,
                        "Gagal mengambil rekomendasi.",
                    ),
                );
            }

            console.debug("FAISS modal raw response:", recommendationData);
            const recommendationPayload = recommendationData.data || {};
            console.debug("FAISS modal payload.data:", recommendationPayload);
            const recommendationResults = recommendationPayload.results || [];
            console.debug(
                "FAISS modal results length before filtering:",
                recommendationResults.length,
            );
            const seen = new Set();
            state.recommendations = recommendationResults
                .filter((item) => {
                    let key = "";
                    if (item?.image_id != null) {
                        key = `image:${item.image_id}`;
                    } else if (item?.vec_id != null) {
                        key = `vec:${item.vec_id}`;
                    } else {
                        key = `label:${item?.label ?? ""}`;
                    }
                    if (seen.has(key)) {
                        return false;
                    }
                    seen.add(key);
                    return true;
                })
                .slice(0, 15);
            console.debug(
                "FAISS modal results length after filtering:",
                state.recommendations.length,
            );

            this._renderRecommendations(id);
            this._setScanButtonState(id);

            if (actionLabel) actionLabel.textContent = "Ingin Pindai Ulang?";
            this._notify(
                id,
                "info",
                `Rekomendasi berhasil diambil (${state.recommendations.length} item).`,
            );
        } catch (error) {
            this._notify(id, "error", error?.message || "Terjadi kesalahan.");
        } finally {
            if (scanBtn) {
                scanBtn.disabled = false;
                scanBtn.textContent = "Pindai Gambar";
            }
            this._setScanButtonState(id);
        }
    },

    togglePalette(id, hex) {
        const state = this._getState(id);
        const palette = state.palettes.find((item) => item.hex === hex);
        if (!palette) return;

        const hasColor = state.selectedPaletteIndexes.includes(palette.index);

        if (hasColor) {
            state.selectedPaletteIndexes = state.selectedPaletteIndexes.filter(
                (item) => item !== palette.index,
            );
        } else {
            state.selectedPaletteIndexes.push(palette.index);
        }

        this._renderPalettes(id);
        this._setScanButtonState(id);
    },

    _renderPalettes(id) {
        const state = this._getState(id);
        const paletteList = document.getElementById(`${id}-palette-list`);
        const paletteEmpty = document.getElementById(`${id}-palette-empty`);

        if (!paletteList || !paletteEmpty) return;

        if (!state.palettes.length) {
            paletteEmpty.classList.remove("hidden");
            paletteList.classList.add("hidden");
            paletteList.innerHTML = "";
            return;
        }

        paletteEmpty.classList.add("hidden");
        paletteList.classList.remove("hidden");

        const html = state.palettes
            .map((palette) => {
                const selected = state.selectedPaletteIndexes.includes(
                    palette.index,
                );
                return `
                    <button
                        type="button"
                        onclick="ColorSearchModal.togglePalette('${id}', '${palette.hex}')"
                        class="relative h-[72px] w-full rounded-xl border transition-colors sm:h-20 ${selected ? "border-amber-500" : "border-gray-700"}"
                        title="${palette.name} (${palette.hex})"
                        style="background-color: ${palette.hex};"
                    >
                        ${selected ? '<span class="absolute right-2 top-2 text-emerald-400"><i class="bi bi-check-circle-fill text-base sm:text-lg"></i></span>' : ""}
                        <span class="absolute bottom-2 left-2 rounded bg-black/60 px-1.5 py-0.5 text-[11px] font-semibold text-white">${palette.hex}</span>
                    </button>
                `;
            })
            .join("");

        paletteList.innerHTML = `
            <div class="mb-3 text-center text-xs text-gray-400">Klik palette untuk memilih manual warna pencarian.</div>
            <div class="grid grid-cols-2 gap-2.5 sm:gap-3 md:grid-cols-3">${html}</div>
        `;
    },

    _renderRecommendations(id) {
        const state = this._getState(id);
        const section = document.getElementById(`${id}-recommend-section`);
        const list = document.getElementById(`${id}-recommend-list`);
        const count = document.getElementById(`${id}-recommend-count`);

        if (!section || !list || !count) return;

        section.classList.remove("hidden");
        count.textContent = `${state.recommendations.length} rekomendasi`;

        if (!state.recommendations.length) {
            list.innerHTML =
                '<div class="col-span-full rounded-xl border border-gray-800 bg-gray-900/30 p-6 text-center text-sm text-gray-400">Tidak ada rekomendasi untuk palette yang dipilih.</div>';
            return;
        }

        list.innerHTML = state.recommendations
            .map(
                (item) => `
                <article class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/50">
                    <img src="${item.image_url || item.image_path || ""}" alt="${item.label || item.name || "Batik Serupa"}" class="h-24 w-full object-cover sm:h-28 md:h-32">
                    <div class="p-3">
                        <p class="line-clamp-1 text-xs font-semibold text-white sm:text-sm">${item.label || item.name || "Batik Serupa"}</p>
                    </div>
                </article>
            `,
            )
            .join("");
    },

    reset(id) {
        const state = this._getState(id);
        state.file = null;
        state.palettes = [];
        state.selectedPaletteIndexes = [];
        state.recommendations = [];
        state.paletteFetchFailed = false;

        const preview = document.getElementById(`${id}-preview`);
        const uploadState = document.getElementById(`${id}-upload-state`);
        const fileInput = document.getElementById(`${id}-file-input`);
        const cameraInput = document.getElementById(`${id}-camera-input`);
        const actionLabel = document.getElementById(`${id}-action-label`);
        const section = document.getElementById(`${id}-recommend-section`);

        if (preview) {
            preview.src = "";
            preview.classList.add("hidden");
        }
        if (uploadState) uploadState.classList.remove("hidden");
        if (fileInput) fileInput.value = "";
        if (cameraInput) cameraInput.value = "";
        if (actionLabel)
            actionLabel.textContent = "Lakukan Pencarian Sekarang?";
        if (section) section.classList.add("hidden");

        this._renderPalettes(id);
        this._syncActionSection(id);
        this._syncRefreshButton(id);
        this._clearAlert(id);

        const list = document.getElementById(`${id}-recommend-list`);
        const count = document.getElementById(`${id}-recommend-count`);
        if (list) list.innerHTML = "";
        if (count) count.textContent = "";
    },
};
