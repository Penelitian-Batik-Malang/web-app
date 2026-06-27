(function () {
    "use strict";

    var ColorSearchPage = {
        id: "color-search-page",
        MAX_INPUT_SIZE: 20 * 1024 * 1024,
        ALERT_TIMEOUT_MS: 4200,
        state: {
            file: null,
            palettes: [],
            selectedPaletteNos: [],
            recommendations: [],
            alertTimer: null,
            paletteFetchFailed: false,
            numCluster: 5,
            cameraStream: null,
        },

        init: function () {
            var root = document.getElementById(this.id);
            if (!root) return;

            this._bindClusterSelect();
            this._bindInputs();
            this._bindPaletteList();
            this._bindCameraModal();
            this._syncActionSection();
            this._syncRefreshButton();
            this._syncClusterSelect();
        },

        _bindInputs: function () {
            var _this = this;
            var dropzone = document.getElementById(this.id + "-dropzone");
            var fileInput = document.getElementById(this.id + "-file-input");
            var cameraInput = document.getElementById(
                this.id + "-camera-input",
            );
            var uploadBtn = document.getElementById(this.id + "-upload-btn");
            var cameraBtn = document.getElementById(this.id + "-camera-btn");
            var refreshBtn = document.getElementById(this.id + "-refresh-btn");
            var scanBtn = document.getElementById(this.id + "-scan-btn");
            var resetBtn = document.getElementById(this.id + "-reset-btn");

            if (dropzone) {
                dropzone.addEventListener("click", function () {
                    _this._openInput(fileInput);
                });
                dropzone.addEventListener("dragover", function (event) {
                    event.preventDefault();
                    dropzone.classList.add("border-primary/60", "bg-primary/5");
                });
                dropzone.addEventListener("dragleave", function () {
                    dropzone.classList.remove(
                        "border-primary/60",
                        "bg-primary/5",
                    );
                });
                dropzone.addEventListener("drop", function (event) {
                    event.preventDefault();
                    dropzone.classList.remove(
                        "border-primary/60",
                        "bg-primary/5",
                    );
                    var file =
                        event.dataTransfer &&
                        event.dataTransfer.files &&
                        event.dataTransfer.files[0];
                    _this.handleFile(file);
                });
            }

            if (fileInput) {
                fileInput.addEventListener("change", function () {
                    var file = fileInput.files && fileInput.files[0];
                    _this.handleFile(file);
                });
            }

            if (cameraInput) {
                cameraInput.addEventListener("change", function () {
                    var file = cameraInput.files && cameraInput.files[0];
                    _this.handleFile(file);
                });
            }

            if (uploadBtn && fileInput) {
                uploadBtn.addEventListener("click", function () {
                    _this._openInput(fileInput);
                });
            }

            if (cameraBtn && cameraInput) {
                cameraBtn.addEventListener("click", function () {
                    _this.openCamera();
                });
            }

            if (refreshBtn) {
                refreshBtn.addEventListener("click", function () {
                    _this.refreshPalette();
                });
            }

            if (scanBtn) {
                scanBtn.addEventListener("click", function () {
                    _this.search();
                });
            }

            if (resetBtn) {
                resetBtn.addEventListener("click", function () {
                    _this.reset();
                });
            }
        },

        _bindPaletteList: function () {
            var _this = this;
            var paletteList = document.getElementById(
                this.id + "-palette-list",
            );
            if (!paletteList) return;

            paletteList.addEventListener("click", function (event) {
                var target = event.target;
                if (!target) return;

                var button = target.closest("[data-palette-no]");
                if (!button || !paletteList.contains(button)) return;

                var no = parseInt(button.getAttribute("data-palette-no"), 10);
                if (isNaN(no)) return;

                _this.togglePalette(no);
            });
        },

        _bindCameraModal: function () {
            var _this = this;
            var modal = document.getElementById("webcam-modal");
            var captureBtn = document.getElementById("webcam-capture-btn");
            var cancelBtn = document.getElementById("webcam-cancel-btn");
            var closeBtn = document.getElementById("webcam-close-btn");

            if (captureBtn) {
                captureBtn.addEventListener("click", function () {
                    _this._captureWebcam();
                });
            }

            if (cancelBtn) {
                cancelBtn.addEventListener("click", function () {
                    _this._stopWebcam(true);
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener("click", function () {
                    _this._stopWebcam(true);
                });
            }

            if (modal) {
                modal.addEventListener("click", function (event) {
                    if (event.target === modal) {
                        _this._stopWebcam(true);
                    }
                });
            }
        },

        openCamera: function () {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                this._startWebcam();
                return;
            }

            var cameraInput = document.getElementById(
                this.id + "-camera-input",
            );
            if (cameraInput) {
                this._openInput(cameraInput);
                return;
            }

            this._notify(
                "warning",
                "Fitur kamera tidak tersedia di perangkat ini.",
            );
        },

        _startWebcam: async function () {
            var modal = document.getElementById("webcam-modal");
            var video = document.getElementById("webcam-video");
            var cameraInput = document.getElementById(
                this.id + "-camera-input",
            );

            if (!modal || !video) {
                if (cameraInput) this._openInput(cameraInput);
                return;
            }

            this._stopWebcam(false);

            try {
                var stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: "environment" } },
                    audio: false,
                });
                this.state.cameraStream = stream;
                video.srcObject = stream;
                modal.style.display = "flex";
                modal.classList.remove("hidden");
                await video.play();
            } catch (_err) {
                this._stopWebcam(true);
                if (cameraInput) {
                    this._openInput(cameraInput);
                } else {
                    this._notify(
                        "warning",
                        "Izin kamera ditolak atau kamera tidak tersedia.",
                    );
                }
            }
        },

        _stopWebcam: function (hideModal) {
            var modal = document.getElementById("webcam-modal");
            var video = document.getElementById("webcam-video");
            var stream = this.state.cameraStream;

            if (stream) {
                stream.getTracks().forEach(function (track) {
                    track.stop();
                });
                this.state.cameraStream = null;
            }

            if (video) {
                try {
                    video.pause();
                } catch (_e) { }
                video.srcObject = null;
            }

            if (modal && hideModal !== false) {
                modal.style.display = "none";
                modal.classList.add("hidden");
            }
        },

        _captureWebcam: function () {
            var _this = this;
            var video = document.getElementById("webcam-video");
            var canvas = document.getElementById("webcam-canvas");
            if (!video || !canvas) return;

            var width = video.videoWidth || 1280;
            var height = video.videoHeight || 720;
            canvas.width = width;
            canvas.height = height;

            var ctx = canvas.getContext("2d");
            if (!ctx) return;
            ctx.drawImage(video, 0, 0, width, height);

            canvas.toBlob(
                function (blob) {
                    if (!blob) return;
                    var file = new File([blob], "webcam.jpg", {
                        type: "image/jpeg",
                    });
                    _this.handleFile(file);
                    _this._stopWebcam(true);
                },
                "image/jpeg",
                0.92,
            );
        },

        _openInput: function (input) {
            if (!input) return;
            input.value = "";
            input.click();
        },

        _bindClusterSelect: function () {
            var _this = this;
            var clusterSelect = document.getElementById(
                this.id + "-cluster-select",
            );
            if (!clusterSelect) return;

            clusterSelect.addEventListener("change", function () {
                var value = parseInt(clusterSelect.value, 10);
                if (!value || value === _this.state.numCluster) return;

                _this.state.numCluster = value;
                _this.state.selectedPaletteNos = [];
                _this.state.palettes = [];
                _this._renderPalettes();
                _this._syncActionSection();
                _this._syncClusterSelect();

                if (_this.state.file) {
                    _this.fetchPalette();
                }
            });
        },

        _syncClusterSelect: function () {
            var clusterSelect = document.getElementById(
                this.id + "-cluster-select",
            );
            if (!clusterSelect) return;
            clusterSelect.value = String(this.state.numCluster);
        },

        handleFile: function (file) {
            var _this = this;
            if (!file) return;

            var validTypes = ["image/jpeg", "image/png", "image/webp"];
            if (validTypes.indexOf(file.type) === -1) {
                this._notify(
                    "warning",
                    "Format gambar harus JPG, PNG, atau WEBP.",
                );
                return;
            }

            if (file.size > this.MAX_INPUT_SIZE) {
                this._notify("warning", "Ukuran gambar maksimal 50MB.");
                return;
            }

            this.state.file = file;
            this.state.palettes = [];
            this.state.selectedPaletteNos = [];
            this.state.recommendations = [];
            this.state.paletteFetchFailed = false;

            var section = document.getElementById(
                this.id + "-recommend-section",
            );
            var list = document.getElementById(this.id + "-recommend-list");
            var count = document.getElementById(this.id + "-recommend-count");
            if (section) section.classList.add("hidden");
            if (list) list.innerHTML = "";
            if (count) count.textContent = "";

            this._renderPalettes();
            this._syncActionSection();
            this._syncRefreshButton();

            var reader = new FileReader();
            reader.onload = function (e) {
                var preview = document.getElementById(_this.id + "-preview");
                var uploadState = document.getElementById(
                    _this.id + "-upload-state",
                );
                if (preview) {
                    preview.src =
                        e.target && e.target.result ? e.target.result : "";
                    preview.classList.remove("hidden");
                    preview.classList.add("cs-fade-up");
                }
                if (uploadState) uploadState.classList.add("hidden");
            };
            reader.readAsDataURL(file);

            this.fetchPalette();
        },

        _notify: function (type, message) {
            var alertEl = document.getElementById(this.id + "-alert");
            var alertIcon = document.getElementById(this.id + "-alert-icon");
            var alertMessage = document.getElementById(
                this.id + "-alert-message",
            );
            if (!alertEl || !alertIcon || !alertMessage) return;

            var variants = {
                info: {
                    icon: "bi-info-circle-fill",
                    className:
                        "border-cyan-700/60 bg-cyan-950/30 text-cyan-200",
                },
                warning: {
                    icon: "bi-exclamation-triangle-fill",
                    className:
                        "border-amber-700/60 bg-amber-950/30 text-amber-200",
                },
                error: {
                    icon: "bi-x-octagon-fill",
                    className: "border-red-700/60 bg-red-950/30 text-red-200",
                },
            };

            var variant = variants[type] || variants.info;
            alertEl.className =
                "rounded-xl border px-4 py-3 text-sm " + variant.className;
            alertEl.classList.remove("hidden");

            alertIcon.className = "bi " + variant.icon + " mt-0.5";
            alertMessage.textContent = message;

            if (this.state.alertTimer) {
                clearTimeout(this.state.alertTimer);
            }

            var _this = this;
            this.state.alertTimer = setTimeout(function () {
                _this._clearAlert();
            }, this.ALERT_TIMEOUT_MS);
        },

        _clearAlert: function () {
            if (this.state.alertTimer) {
                clearTimeout(this.state.alertTimer);
                this.state.alertTimer = null;
            }

            var alertEl = document.getElementById(this.id + "-alert");
            if (alertEl) alertEl.classList.add("hidden");
        },

        _syncActionSection: function () {
            var actionSection = document.getElementById(
                this.id + "-action-section",
            );
            if (!actionSection) return;

            var visible = !!this.state.file && this.state.palettes.length > 0;
            actionSection.classList.toggle("hidden", !visible);
            if (visible) {
                this._setScanButtonState();
            }
        },

        _setScanButtonState: function () {
            var scanBtn = document.getElementById(this.id + "-scan-btn");
            var actionNote = document.getElementById(this.id + "-action-note");
            if (!scanBtn || !actionNote) return;

            if (!this.state.selectedPaletteNos.length) {
                scanBtn.disabled = true;
                scanBtn.classList.add("opacity-60", "cursor-not-allowed");
                actionNote.textContent =
                    "Pilih minimal 1 palet warna untuk melakukan pencarian.";
                return;
            }

            scanBtn.disabled = false;
            scanBtn.classList.remove("opacity-60", "cursor-not-allowed");
            actionNote.textContent =
                "Palet siap. Klik Pindai Gambar untuk mengambil rekomendasi.";
        },

        _syncRefreshButton: function () {
            var wrap = document.getElementById(this.id + "-refresh-wrap");
            var btn = document.getElementById(this.id + "-refresh-btn");
            if (!wrap || !btn) return;

            var visible = !!this.state.file;
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
            btn.innerHTML =
                '<i class="bi bi-arrow-clockwise"></i> Refresh Palet';

            if (this.state.paletteFetchFailed) {
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
                    '<i class="bi bi-arrow-repeat"></i> Coba Ambil Palet Lagi';
            }
        },

        refreshPalette: function () {
            if (!this.state.file) {
                this._notify("warning", "Unggah gambar terlebih dahulu.");
                return;
            }
            this.fetchPalette();
        },

        fetchPalette: async function () {
            var root = document.getElementById(this.id);
            var paletteEndpoint =
                root && root.dataset ? root.dataset.paletteEndpoint : "";
            var refreshBtn = document.getElementById(this.id + "-refresh-btn");

            if (!paletteEndpoint) {
                this._notify("error", "Endpoint palet belum diset.");
                return false;
            }

            if (!this.state.file) {
                this._notify(
                    "warning",
                    "Pilih atau unggah gambar terlebih dahulu.",
                );
                return false;
            }

            var csrfToken =
                document.querySelector('meta[name="csrf-token"]') &&
                    document.querySelector('meta[name="csrf-token"]').content
                    ? document.querySelector('meta[name="csrf-token"]').content
                    : "";

            this._notify("info", "Mengekstrak palet warna dari gambar...");
            this.state.paletteFetchFailed = false;
            this._syncRefreshButton();

            if (refreshBtn) {
                refreshBtn.disabled = true;
                refreshBtn.classList.add("opacity-60", "cursor-not-allowed");
                refreshBtn.innerHTML =
                    '<i class="bi bi-arrow-repeat animate-spin"></i> Memuat Palet...';
            }

            try {
                var paletteFormData = new FormData();
                paletteFormData.append("file", this.state.file);
                paletteFormData.append(
                    "num_cluster",
                    String(this.state.numCluster),
                );

                var paletteResponse = await fetch(paletteEndpoint, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: paletteFormData,
                });

                var paletteData = await paletteResponse.json();
                if (!paletteResponse.ok || !this._isSuccess(paletteData)) {
                    throw new Error(
                        this._extractApiError(
                            paletteData,
                            "Gagal mengambil palet warna.",
                        ),
                    );
                }

                var palettePayload = paletteData.data || {};
                var paletteResult = paletteData.result || {};
                this.state.palettes =
                    palettePayload.palette || paletteResult.palette || [];
                this.state.selectedPaletteNos = this.state.palettes.map(
                    function (item) {
                        return item.no;
                    },
                );

                this._renderPalettes();
                this._syncActionSection();
                this.state.paletteFetchFailed = false;
                this._syncRefreshButton();

                if (!this.state.palettes.length) {
                    this._notify(
                        "warning",
                        "Palet tidak ditemukan dari gambar ini.",
                    );
                    return false;
                }

                this._notify(
                    "info",
                    "Palet berhasil diambil (" +
                    this.state.palettes.length +
                    " warna). Pilih warna yang diinginkan.",
                );
                return true;
            } catch (error) {
                this.state.palettes = [];
                this.state.selectedPaletteNos = [];
                this.state.paletteFetchFailed = true;
                this._renderPalettes();
                this._syncActionSection();
                this._syncRefreshButton();
                this._notify(
                    "error",
                    error && error.message
                        ? error.message
                        : "Terjadi kesalahan saat mengambil palet.",
                );
                return false;
            } finally {
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                    refreshBtn.classList.remove(
                        "opacity-60",
                        "cursor-not-allowed",
                    );
                }
                this._syncRefreshButton();
            }
        },

        _isSuccess: function (payload) {
            if (!payload || typeof payload.status !== "number") return false;
            return payload.status >= 200 && payload.status < 300;
        },

        _extractApiError: function (payload, fallbackMessage) {
            if (!payload || typeof payload !== "object") return fallbackMessage;

            if (Array.isArray(payload.errors) && payload.errors.length > 0) {
                return payload.errors[0];
            }

            if (typeof payload.message === "string" && payload.message.trim()) {
                return payload.message;
            }

            return fallbackMessage;
        },

        togglePalette: function (no) {
            var index = this.state.selectedPaletteNos.indexOf(no);
            if (index >= 0) {
                this.state.selectedPaletteNos.splice(index, 1);
            } else {
                this.state.selectedPaletteNos.push(no);
            }

            this._renderPalettes();
            this._setScanButtonState();
        },

        _renderPalettes: function () {
            var paletteList = document.getElementById(
                this.id + "-palette-list",
            );
            var paletteEmpty = document.getElementById(
                this.id + "-palette-empty",
            );
            if (!paletteList || !paletteEmpty) return;

            if (!this.state.palettes.length) {
                paletteEmpty.classList.remove("hidden");
                paletteList.classList.add("hidden");
                paletteList.innerHTML = "";
                return;
            }

            paletteEmpty.classList.add("hidden");
            paletteList.classList.remove("hidden");

            var html = this.state.palettes
                .map(function (palette) {
                    var nameLabel = palette.name ? palette.name.charAt(0).toUpperCase() + palette.name.slice(1) : "";
                    var isSelected =
                        ColorSearchPage.state.selectedPaletteNos.indexOf(
                            palette.no,
                        ) >= 0;
                    var borderClass = isSelected
                        ? "border-amber-400 ring-2 ring-amber-400/60"
                        : "border-gray-700";
                    var checkBadge = isSelected
                        ? '<span class="absolute top-2 right-2 text-green-500"><i class="bi bi-check-circle-fill"></i></span>'
                        : "";
                    return (
                        '<button type="button" class="relative h-[76px] w-full rounded-xl border ' +
                        borderClass +
                        ' sm:h-20 focus:outline-none" data-palette-no="' +
                        palette.no +
                        '" aria-pressed="' +
                        (isSelected ? "true" : "false") +
                        '" style="background-color: ' +
                        palette.palette +
                        ';" title="' +
                        palette.palette +
                        '">' +
                        checkBadge +
                        '<span class="absolute bottom-2 left-2 rounded bg-black/60 px-1.5 py-0.5 text-[11px] font-semibold text-white">' +
                        palette.palette +
                        "</span>" +
                        (nameLabel
                            ? '<span class="absolute bottom-2 right-2 rounded bg-black/60 px-1.5 py-0.5 text-[11px] font-semibold text-white">' +
                            nameLabel +
                            "</span>"
                            : "") +
                        "</button>"
                    );
                })
                .join("");

            paletteList.innerHTML =
                '<div class="mb-3 text-center text-xs text-gray-400">Klik palet untuk memilih warna dominan.</div>' +
                '<div class="grid grid-cols-2 gap-2.5 sm:gap-3 md:grid-cols-3">' +
                html +
                "</div>";
        },

        search: async function () {
            var root = document.getElementById(this.id);
            var recommendationEndpoint =
                root && root.dataset ? root.dataset.recommendationEndpoint : "";
            var scanBtn = document.getElementById(this.id + "-scan-btn");
            var actionLabel = document.getElementById(
                this.id + "-action-label",
            );

            if (!recommendationEndpoint) {
                this._notify("error", "Endpoint rekomendasi belum diset.");
                return;
            }

            if (!this.state.file) {
                this._notify(
                    "warning",
                    "Pilih atau unggah gambar terlebih dahulu.",
                );
                return;
            }

            if (!this.state.palettes.length) {
                this._notify(
                    "warning",
                    "Palet belum tersedia. Unggah ulang gambar untuk ekstraksi palet.",
                );
                return;
            }

            if (!this.state.selectedPaletteNos.length) {
                this._notify("warning", "Pilih minimal 1 palet warna.");
                return;
            }

            if (scanBtn) {
                scanBtn.disabled = true;
                scanBtn.textContent = "Memproses...";
            }

            var csrfToken =
                document.querySelector('meta[name="csrf-token"]') &&
                    document.querySelector('meta[name="csrf-token"]').content
                    ? document.querySelector('meta[name="csrf-token"]').content
                    : "";

            try {
                // Bersihkan list rekomendasi lama sebelum pindai ulang
                var sectionEl = document.getElementById(this.id + "-recommend-section");
                var listEl    = document.getElementById(this.id + "-recommend-list");
                var countEl   = document.getElementById(this.id + "-recommend-count");
                if (sectionEl) sectionEl.classList.add("hidden");
                if (listEl)    listEl.innerHTML = "";
                if (countEl)   countEl.textContent = "";
                this.state.recommendations = [];

                this._notify("info", "Mengambil rekomendasi batik...");

                var recommendationFormData = new FormData();
                recommendationFormData.append("file", this.state.file);
                recommendationFormData.append(
                    "num_cluster",
                    String(this.state.numCluster),
                );
                recommendationFormData.append("top_k", "15");
                recommendationFormData.append(
                    "selected_colors",
                    this.state.selectedPaletteNos
                        .slice()
                        .sort(function (a, b) {
                            return a - b;
                        })
                        .join(","),
                );

                var recommendationResponse = await fetch(
                    recommendationEndpoint,
                    {
                        method: "POST",
                        headers: {
                            Accept: "application/json",
                            "X-CSRF-TOKEN": csrfToken,
                        },
                        body: recommendationFormData,
                    },
                );

                var recommendationData = await recommendationResponse.json();
                if (
                    !recommendationResponse.ok ||
                    !this._isSuccess(recommendationData)
                ) {
                    throw new Error(
                        this._extractApiError(
                            recommendationData,
                            "Gagal mengambil rekomendasi.",
                        ),
                    );
                }

                var recommendationPayload = recommendationData.data || {};
                this.state.recommendations =
                    recommendationPayload.results || [];

                this._renderRecommendations();
                this._setScanButtonState();

                if (actionLabel)
                    actionLabel.textContent = "Ingin Pindai Ulang?";
                this._notify(
                    "info",
                    "Rekomendasi berhasil diambil (" +
                    this.state.recommendations.length +
                    " item).",
                );
            } catch (error) {
                this._notify(
                    "error",
                    error && error.message
                        ? error.message
                        : "Terjadi kesalahan.",
                );
            } finally {
                if (scanBtn) {
                    scanBtn.disabled = false;
                    scanBtn.textContent = "Pindai Gambar";
                }
                this._setScanButtonState();
            }
        },

        _renderRecommendations: function () {
            var section = document.getElementById(
                this.id + "-recommend-section",
            );
            var list = document.getElementById(this.id + "-recommend-list");
            var count = document.getElementById(this.id + "-recommend-count");

            if (!section || !list || !count) return;

            section.classList.remove("hidden");
            count.textContent =
                this.state.recommendations.length + " rekomendasi";

            if (!this.state.recommendations.length) {
                list.innerHTML =
                    '<div class="col-span-full rounded-xl border border-gray-800 bg-gray-900/30 p-6 text-center text-sm text-gray-400">Tidak ada rekomendasi untuk palet yang dipilih.</div>';
                return;
            }

            console.log(this.state.recommendations);
            var html = this.state.recommendations
                .map(function (item, index) {
                    var label = item.name || "Batik Serupa";
                    var imageUrl = item.image_url || item.image_path || "";
                    var distance =
                        typeof item.distance === "number"
                            ? item.distance.toFixed(4)
                            : "-";

                    var colorBadges = "";
                    if (Array.isArray(item.color_names_label) && item.color_names_label.length > 0) {
                        colorBadges = '<div class="mt-2 flex flex-wrap gap-1">' +
                            item.color_names_label.map(function (c) {
                                return '<span class="rounded bg-gray-800 px-1.5 py-0.5 text-[10px] text-gray-300">' + c + '</span>';
                            }).join('') +
                            '</div>';
                    }

                    return (
                        '<article class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900/50 cs-fade-up" style="animation-delay: ' +
                        Math.min(index * 60, 240) +
                        'ms">' +
                        '<img src="' +
                        imageUrl +
                        '" alt="' +
                        label +
                        '" class="h-24 w-full object-cover sm:h-28 md:h-32" onerror="this.style.display=\'none\';">' +
                        '<div class="p-3">' +
                        '<p class="line-clamp-1 text-xs font-semibold text-white sm:text-sm">' +
                        label +
                        "</p>" +
                        '<p class="mt-1 text-[11px] text-amber-400">Jarak warna: ' +
                        distance +
                        "</p>" +
                        colorBadges +
                        "</div>" +
                        "</article>"
                    );
                })
                .join("");

            list.innerHTML = html;
        },

        reset: function () {
            this.state.file = null;
            this.state.palettes = [];
            this.state.selectedPaletteNos = [];
            this.state.recommendations = [];
            this.state.paletteFetchFailed = false;

            var preview = document.getElementById(this.id + "-preview");
            var uploadState = document.getElementById(
                this.id + "-upload-state",
            );
            var fileInput = document.getElementById(this.id + "-file-input");
            var cameraInput = document.getElementById(
                this.id + "-camera-input",
            );
            var actionLabel = document.getElementById(
                this.id + "-action-label",
            );
            var section = document.getElementById(
                this.id + "-recommend-section",
            );

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

            this._renderPalettes();
            this._syncActionSection();
            this._syncRefreshButton();
            this._clearAlert();

            var list = document.getElementById(this.id + "-recommend-list");
            var count = document.getElementById(this.id + "-recommend-count");
            if (list) list.innerHTML = "";
            if (count) count.textContent = "";
        },
    };

    window.ColorSearchPage = ColorSearchPage;

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function () {
            ColorSearchPage.init();
        });
    } else {
        ColorSearchPage.init();
    }
})();
