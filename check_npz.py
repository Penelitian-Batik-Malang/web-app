"""
Script diagnostik: cek format path di NPZ batik dan apakah file-nya ada.
Jalankan dari: d:\laragon\www\web-app\
Command: d:\laragon\www\fashionpedia\venv\Scripts\python.exe check_npz.py
"""
import numpy as np
import os

NPZ_PATH = r"d:\laragon\www\fashionpedia\data\batik_skenario_3_warna.npz"
BATIK_ROOT_LOCAL = r"D:\laragon\www\Data_Untuk_Warna_Dominan"
BATIK_ROOT_COLAB = "/content/drive/MyDrive/Data Penelitian Batik 2025/Data_Untuk_Warna_Dominan"

print("="*60)
print("CEK NPZ BATIK")
print("="*60)

data = np.load(NPZ_PATH, allow_pickle=True)
filenames = data['filename']
labels    = data['label']
fitur     = data['fitur_warna']

print(f"\nTotal item  : {len(filenames)}")
print(f"fitur shape : {fitur.shape}")
print(f"fitur dtype : {fitur.dtype}")
print(f"\nContoh filename pertama:")
f0 = filenames[0]
print(f"  type    : {type(f0)}")
print(f"  repr    : {repr(str(f0))}")

print(f"\n--- Simulasi path replacement ---")
for i, f in enumerate(filenames[:3]):
    f_str   = str(f)
    f_local = f_str.replace(BATIK_ROOT_COLAB, BATIK_ROOT_LOCAL)
    f_local = f_local.replace('/', os.sep)
    f_norm  = os.path.normpath(f_local)
    exists  = os.path.exists(f_norm)
    print(f"\n[{i}] original: {f_str[:80]}")
    print(f"    replaced: {f_local[:80]}")
    print(f"    normalized: {f_norm}")
    print(f"    exists: {exists}")

print(f"\n--- Sample fitur_warna[0] ---")
print(fitur[0])
print(f"\n--- Label sample ---")
print(labels[:5])
