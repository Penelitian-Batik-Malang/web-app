/**
 * =========================================================================
 * BatikApp — Constants (Warna & Label Bagian Pakaian)
 * =========================================================================
 *
 * Mendefinisikan konstanta warna dan label terjemahan Indonesia
 * untuk setiap bagian pakaian yang dideteksi oleh Fashionpedia API.
 *
 * Warna digunakan untuk overlay mask pada fashion canvas.
 * Label digunakan untuk tampilan sidebar parts list dan tooltip.
 *
 * CARA MENAMBAH BAGIAN BARU:
 *   1. Tambahkan entry di PART_COLORS dengan warna RGB unik
 *   2. Tambahkan entry di PART_LABELS dengan terjemahan Indonesia
 *   3. Pastikan nama key sama persis dengan yang dikembalikan ML API
 *
 * @module  BatikApp.Constants
 * @see     public/js/batik-app/main.js — Orchestrator
 * @see     docs/JS_MODULES_GUIDE.md — Dokumentasi lengkap
 * =========================================================================
 */

window.BatikApp = window.BatikApp || {};

/**
 * Warna RGB untuk overlay mask setiap bagian pakaian.
 * Format: { 'nama-bagian': [R, G, B] }
 *
 * @type {Object.<string, number[]>}
 */
window.BatikApp.PART_COLORS = {
    'shirt':     [128, 128, 128],
    't-shirt':   [100, 150, 200],
    'sweater':   [200, 150, 100],
    'cardigan':  [150, 200, 100],
    'jacket':    [200, 100, 150],
    'vest':      [150, 100, 200],
    'dress':     [100, 200, 150],
    'jumpsuit':  [250, 150,  50],
    'suit':      [ 50, 150, 250],
    'coat':      [150, 250,  50],
    'sleeve':    [255,  80,  80],
    'collar':    [ 80, 160, 255],
    'lapel':     [ 80, 200,  80],
    'hood':      [255, 180,  50],
    'pocket':    [180,  80, 255],
    'neckline':  [255, 255,  80],
    'epaulette': [ 80, 220, 220],
};

// Backward compatibility — beberapa bagian kode lama menggunakan window.PART_COLORS
window.PART_COLORS = window.BatikApp.PART_COLORS;

/**
 * Label terjemahan Indonesia untuk setiap bagian pakaian.
 * Format: { 'nama-bagian-english': 'Terjemahan Indonesia' }
 *
 * @type {Object.<string, string>}
 */
window.BatikApp.PART_LABELS = {
    'shirt':     'Kemeja',
    't-shirt':   'Kaos',
    'sweater':   'Sweater',
    'cardigan':  'Kardigan',
    'jacket':    'Jaket',
    'vest':      'Rompi',
    'dress':     'Gaun',
    'jumpsuit':  'Jumpsuit',
    'suit':      'Setelan',
    'coat':      'Mantel',
    'sleeve':    'Lengan',
    'collar':    'Kerah',
    'lapel':     'Lapel',
    'hood':      'Tudung',
    'pocket':    'Saku',
    'neckline':  'Leher',
    'epaulette': 'Epaulet',
};
