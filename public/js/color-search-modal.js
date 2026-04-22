window.ColorSearchModal = {
    _state: {},

    _getState(id) {
        if (!this._state[id]) {
            this._state[id] = {
                file: null,
                palettes: [],
                selectedPalettes: [],
                recommendations: [],
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
    },

    close(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.classList.add("hidden");
        modal.classList.remove("flex");
        document.body.style.overflow = "";
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

    handleFile(file, id) {
        if (!file) return;

        const validTypes = ["image/jpeg", "image/png", "image/webp"];
        const maxSize = 10 * 1024 * 1024;

        if (!validTypes.includes(file.type)) {
            alert("Format gambar harus JPG, PNG, atau WEBP.");
            return;
        }

        if (file.size > maxSize) {
            alert("Ukuran gambar maksimal 10MB.");
            return;
        }

        const state = this._getState(id);
        state.file = file;

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
        reader.readAsDataURL(file);
    },

    async search(id) {
        const state = this._getState(id);
        const modal = document.getElementById(id);
        const endpoint = modal?.dataset?.endpoint || "";
        const scanBtn = document.getElementById(`${id}-scan-btn`);
        const actionLabel = document.getElementById(`${id}-action-label`);

        if (!endpoint) {
            alert("Endpoint pencarian warna belum diset.");
            return;
        }

        if (!state.file) {
            alert("Pilih atau unggah gambar terlebih dahulu.");
            return;
        }

        if (scanBtn) {
            scanBtn.disabled = true;
            scanBtn.textContent = "Memproses...";
        }

        const formData = new FormData();
        formData.append("image", state.file);
        state.selectedPalettes.forEach((hex) =>
            formData.append("selected_palettes[]", hex),
        );
        formData.append(
            "_token",
            document.querySelector('meta[name="csrf-token"]')?.content || "",
        );

        try {
            const response = await fetch(endpoint, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
                body: formData,
            });

            const data = await response.json();
            if (!response.ok || !data?.success) {
                throw new Error(
                    data?.message || "Gagal memproses pencarian warna.",
                );
            }

            const result = data.result || {};
            state.palettes = result.palettes || [];
            state.selectedPalettes = result.selected_palettes || [];
            state.recommendations = result.recommendations || [];

            this._renderPalettes(id);
            this._renderRecommendations(id);

            if (actionLabel) actionLabel.textContent = "Ingin Pindai Ulang?";
        } catch (error) {
            alert(error.message || "Terjadi kesalahan.");
        } finally {
            if (scanBtn) {
                scanBtn.disabled = false;
                scanBtn.textContent = "Pindai Gambar";
            }
        }
    },

    togglePalette(id, hex) {
        const state = this._getState(id);
        const hasColor = state.selectedPalettes.includes(hex);

        if (hasColor) {
            state.selectedPalettes = state.selectedPalettes.filter(
                (item) => item !== hex,
            );
        } else {
            state.selectedPalettes.push(hex);
        }

        this._renderPalettes(id);
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
                const selected = state.selectedPalettes.includes(palette.hex);
                return `
                    <button
                        type="button"
                        onclick="ColorSearchModal.togglePalette('${id}', '${palette.hex}')"
                        class="relative h-[72px] w-full rounded-xl border transition-colors sm:h-20 ${selected ? "border-amber-500" : "border-gray-700"}"
                        title="${palette.name} (${palette.hex})"
                        style="background-color: ${palette.hex};"
                    >
                        ${selected ? '<i class="bi bi-check-circle-fill absolute right-2 top-2 text-base text-green-500 sm:text-lg"></i>' : ""}
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
                    <img src="${item.image_url}" alt="${item.name}" class="h-24 w-full object-cover sm:h-28 md:h-32">
                    <div class="p-3">
                        <p class="line-clamp-1 text-xs font-semibold text-white sm:text-sm">${item.name}</p>
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
        state.selectedPalettes = [];
        state.recommendations = [];

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

        const list = document.getElementById(`${id}-recommend-list`);
        const count = document.getElementById(`${id}-recommend-count`);
        if (list) list.innerHTML = "";
        if (count) count.textContent = "";
    },
};
