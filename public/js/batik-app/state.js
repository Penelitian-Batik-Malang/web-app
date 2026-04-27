/**
 * =========================================================================
 * BatikApp — State Management
 * =========================================================================
 *
 * State global untuk aplikasi Terapkan/Rekomendasi Batik.
 * Semua modul JS membaca dan menulis ke objek state ini.
 *
 * PENTING:
 *   - State ini BUKAN reactive (tidak ada observer/watcher)
 *   - Setelah mengubah state, panggil render yang sesuai secara manual
 *   - State di-reset saat user upload ulang atau navigasi kembali
 *
 * PROPERTI UTAMA:
 *   - fashionFile    : File gambar fashion yang diupload user
 *   - sessionId      : UUID session dari ML API (dibuat saat inference)
 *   - partsList      : Array bagian pakaian terdeteksi
 *   - selectedPart   : Bagian yang sedang dipilih di panel
 *   - batikImg       : HTMLImageElement motif batik yang dipilih
 *   - batikTransform : Posisi/zoom/rotasi motif batik di canvas
 *
 * @module  BatikApp.State
 * @see     public/js/batik-app/main.js — Orchestrator
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};

/**
 * State global aplikasi.
 *
 * @type {Object}
 */
window.BatikApp.state = {
    // ── Fashion Image ─────────────────────────────────────────────
    /** @type {File|null} File gambar fashion yang diupload */
    fashionFile: null,
    /** @type {string|null} UUID session dari ML API */
    sessionId: null,
    /** @type {{w: number, h: number}|null} Dimensi gambar dari API (ukuran mask) */
    imageSize: null,

    // ── Parts Detection ───────────────────────────────────────────
    /**
     * Daftar bagian pakaian terdeteksi.
     * @type {Array<{key: string, partName: string, index: number, label: string, bbox: Object, maskImg: HTMLImageElement|null, area: number, score: number|null}>}
     */
    partsList: [],
    /** @type {string|null} Key bagian yang sedang di-hover */
    hoveredKey: null,
    /** @type {Set<string>} Set key bagian yang sudah di-blend */
    blendedKeys: new Set(),
    /**
     * Riwayat batik yang diterapkan (untuk result view).
     * @type {Array<{key: string, partLabel: string, batikName: string, batikSrc: string|null}>}
     */
    appliedBatiks: [],

    // ── Batik Panel ───────────────────────────────────────────────
    /** @type {Object|null} Bagian pakaian yang sedang dipilih */
    selectedPart: null,
    /** @type {HTMLImageElement|null} Gambar motif batik yang dipilih */
    batikImg: null,
    /** @type {{src: string|null, name: string}} Info batik yang sedang aktif */
    currentBatikInfo: null,
    /** @type {{scale: number, offsetX: number, offsetY: number, rotation: number}} Transform motif di canvas */
    batikTransform: { scale: 1, offsetX: 0, offsetY: 0, rotation: 0 },
    /** @type {number} Lebar crop box (pixel) */
    cropBoxW: 200,
    /** @type {number} Tinggi crop box (pixel) */
    cropBoxH: 200,

    // ── Canvas Interaction ─────────────────────────────────────────
    /** @type {boolean} Apakah sedang drag motif */
    isDragging: false,
    /** @type {{x: number, y: number}} Posisi awal drag */
    dragStart: { x: 0, y: 0 },
    /** @type {Object} Offset awal saat drag dimulai */
    dragStartOffset: { x: 0, y: 0 },

    // ── Fashion Image State ───────────────────────────────────────
    /** @type {string|null} Data URL gambar original (untuk result comparison) */
    originalFashionImageSrc: null,
    /** @type {HTMLImageElement|null} HTMLImageElement gambar fashion terkini */
    fashionImage: null,

    // ── Webcam ────────────────────────────────────────────────────
    /** @type {MediaStream|null} Stream webcam aktif */
    webcamStream: null,
    /** @type {string} Target webcam capture ('fashion') */
    webcamTarget: '',
};

// Backward compatibility
window.state = window.BatikApp.state;
