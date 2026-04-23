"""
Script diagnostik lanjutan: bandingkan nama file di NPZ vs yang ada di disk.
Command: d:\laragon\www\fashionpedia\venv\Scripts\python.exe check_npz2.py
"""
import numpy as np
import os
from PIL import Image
import io, base64

NPZ_PATH = r"d:\laragon\www\fashionpedia\data\batik_skenario_3_warna.npz"
BATIK_ROOT_LOCAL = r"D:\laragon\www\Data_Untuk_Warna_Dominan"
BATIK_ROOT_COLAB = "/content/drive/MyDrive/Data Penelitian Batik 2025/Data_Untuk_Warna_Dominan"
DISK_ROOT = r"D:\laragon\www\Data_Untuk_Warna_Dominan"

data = np.load(NPZ_PATH, allow_pickle=True)
filenames = data['filename']

# Rekap: berapa file yang exist
exist_count = 0
missing_count = 0
first_missing = []
first_exist = []

for f in filenames:
    f_str   = str(f)
    f_local = f_str.replace(BATIK_ROOT_COLAB, BATIK_ROOT_LOCAL)
    f_local = f_local.replace('/', os.sep)
    f_norm  = os.path.normpath(f_local)

    if os.path.exists(f_norm):
        exist_count += 1
        if len(first_exist) < 3:
            first_exist.append(f_norm)
    else:
        missing_count += 1
        if len(first_missing) < 3:
            first_missing.append(f_norm)

print(f"\n=== REKAP FILE ===")
print(f"Exist  : {exist_count}")
print(f"Missing: {missing_count}")

if first_exist:
    print(f"\n--- Sample yang ADA di disk ---")
    for p in first_exist:
        print(f"  {p}")
        # Test PIL
        try:
            with Image.open(p) as img:
                print(f"    PIL OK: {img.size} {img.mode}")
        except Exception as e:
            print(f"    PIL FAIL: {e}")

if first_missing:
    print(f"\n--- Sample yang TIDAK ADA di disk ---")
    for p in first_missing:
        print(f"  {p}")

# Tampilkan isi folder disk
print(f"\n=== ISI FOLDER {DISK_ROOT} ===")
if os.path.exists(DISK_ROOT):
    for subfolder in sorted(os.listdir(DISK_ROOT)):
        subpath = os.path.join(DISK_ROOT, subfolder)
        if os.path.isdir(subpath):
            files = os.listdir(subpath)
            print(f"  {subfolder}/ ({len(files)} file) - contoh: {files[:2] if files else '(kosong)'}")
else:
    print(f"  FOLDER TIDAK ADA: {DISK_ROOT}")

# Cek subfolder hijau spesifik
hijau_path = os.path.join(DISK_ROOT, "hijau")
if os.path.exists(hijau_path):
    hijau_files = sorted(os.listdir(hijau_path))[:5]
    print(f"\n--- Contoh file di hijau/ ---")
    for hf in hijau_files:
        print(f"  {hf}")

    # Cek apakah salah satu file hijau dari NPZ match
    print(f"\n--- File hijau dari NPZ (5 pertama) ---")
    count = 0
    for f in filenames:
        f_str = str(f)
        if '/hijau/' in f_str:
            f_local = f_str.replace(BATIK_ROOT_COLAB, BATIK_ROOT_LOCAL)
            f_local = f_local.replace('/', os.sep)
            f_norm  = os.path.normpath(f_local)
            exists  = os.path.exists(f_norm)
            print(f"  {os.path.basename(f_norm)} -> exists={exists}")
            count += 1
            if count >= 5:
                break
