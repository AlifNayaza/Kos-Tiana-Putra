#!/usr/bin/env python3
"""
Model Prediksi Kebutuhan Stok Kos Tiana Putra 1 (FR-13)
Metode: Linear Regression (Scikit-Learn) + Simple Moving Average (7 Hari Terakhir)
Database: MySQL / SQLite (Membaca konfigurasi otomatis dari file .env Laravel)
"""

import os
import sys
import math
import datetime
import warnings

warnings.filterwarnings('ignore')

if sys.stdout.encoding != 'utf-8':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

import numpy as np
import pandas as pd
from sklearn.linear_model import LinearRegression


# =====================================================================
# BAGIAN 1: MEMBACA KONFIGURASI DATABASE DARI FILE .ENV LARAVEL
# =====================================================================
def load_env_config():
    """Membaca pengaturan koneksi Database langsung dari file .env Laravel."""
    env_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", ".env")
    config = {
        "DB_CONNECTION": "mysql",
        "DB_HOST": "127.0.0.1",
        "DB_PORT": "3306",
        "DB_DATABASE": "kos_tiana_putra",
        "DB_USERNAME": "root",
        "DB_PASSWORD": ""
    }

    if os.path.exists(env_path):
        with open(env_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                key, value = line.split("=", 1)
                key = key.strip()
                value = value.strip().strip('"').strip("'")
                if key in config:
                    config[key] = value

    for key in ["DB_CONNECTION", "DB_HOST", "DB_DATABASE", "DB_USERNAME", "DB_PASSWORD", "DB_PORT"]:
        if os.environ.get(key):
            config[key] = os.environ[key]

    return config


# =====================================================================
# BAGIAN 2: KONEKSI DATABASE (MYSQL / SQLITE)
# =====================================================================
def get_db_connection(db_cfg):
    connection_type = db_cfg.get("DB_CONNECTION", "mysql").lower()

    if connection_type == "sqlite":
        import sqlite3
        db_path = db_cfg.get("DB_DATABASE", "")
        if not os.path.isabs(db_path):
            base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
            db_path = os.path.join(base_dir, db_path if db_path else "database/database.sqlite")
        return sqlite3.connect(db_path), "sqlite"

    try:
        import pymysql
        conn = pymysql.connect(
            host=db_cfg["DB_HOST"],
            port=int(db_cfg["DB_PORT"]),
            user=db_cfg["DB_USERNAME"],
            password=db_cfg["DB_PASSWORD"],
            database=db_cfg["DB_DATABASE"],
            charset="utf8mb4",
            autocommit=False
        )
        return conn, "mysql"
    except Exception as e:
        print(f"[ERROR] Gagal terhubung ke MySQL: {e}", file=sys.stderr)
        sys.exit(1)


# =====================================================================
# BAGIAN 3: FUNGSI UTAMA PREDIKSI STOK MACHINE LEARNING
# =====================================================================
def run_prediction():
    db_cfg = load_env_config()
    conn, driver = get_db_connection(db_cfg)

    print(f"[*] Terhubung ke Database ({driver.upper()}): {db_cfg.get('DB_DATABASE')}")

    # 1. Ambil daftar katalog menu & galon aktif
    catalog_query = """
        SELECT id, name, category, price, stock, minimum_stock, unit 
        FROM catalog_items 
        WHERE is_available = 1
        ORDER BY id ASC
    """
    catalog_df = pd.read_sql(catalog_query, conn)

    if catalog_df.empty:
        print("[!] Tidak ada item katalog yang aktif.")
        conn.close()
        return

    # 2. Tentukan rentang waktu 30 hari terakhir (Hari ke-1 s/d Hari ke-30)
    now = datetime.datetime.now()
    now_str = now.strftime('%Y-%m-%d %H:%M:%S')
    end_date = now.date()
    start_date = end_date - datetime.timedelta(days=29)
    cutoff_str = start_date.strftime('%Y-%m-%d 00:00:00')

    # 3. Ambil total penjualan harian per item selama 30 hari terakhir
    param_placeholder = "?" if driver == "sqlite" else "%s"
    sales_query = f"""
        SELECT 
            oi.catalog_item_id,
            DATE(o.created_at) as order_date,
            SUM(oi.quantity) as total_terjual
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE LOWER(o.status) = 'selesai'
          AND o.created_at >= {param_placeholder}
        GROUP BY oi.catalog_item_id, DATE(o.created_at)
    """
    sales_df = pd.read_sql(sales_query, conn, params=[cutoff_str])

    # Siapkan daftar tanggal lengkap 30 hari
    kalender_30_hari = pd.date_range(start=start_date, end=end_date, freq='D')

    prediction_records = []
    print("\n" + "=" * 95)
    print(f"{'ID':<4} {'NAMA ITEM':<32} {'STOK':<6} {'7H':<5} {'TARGET':<7} {'RESTOCK':<8} {'AVG/H':<6} {'TREN':<7} {'STATUS'}")
    print("=" * 95)

    for _, item in catalog_df.iterrows():
        item_id = int(item['id'])
        item_name = str(item['name'])
        current_stock = int(item['stock'])
        safety_stock = int(item['minimum_stock'])
        unit = str(item['unit'])

        item_sales = sales_df[sales_df['catalog_item_id'] == item_id]

        df_harian = pd.DataFrame({'order_date': kalender_30_hari})
        if not item_sales.empty:
            item_sales = item_sales.copy()
            item_sales['order_date'] = pd.to_datetime(item_sales['order_date'])
            df_harian = pd.merge(df_harian, item_sales[['order_date', 'total_terjual']], on='order_date', how='left')
            df_harian['total_terjual'] = df_harian['total_terjual'].fillna(0)
        else:
            df_harian['total_terjual'] = 0.0

        # Regresi Linear: X = urutan hari 1..30, y = penjualan
        X = np.arange(1, 31).reshape(-1, 1)
        y = df_harian['total_terjual'].values

        total_terjual_30_hari = float(np.sum(y))

        if total_terjual_30_hari == 0:
            daily_avg = 0.0
            predicted_demand = max(2, int(math.ceil(safety_stock * 0.5)))
            sales_trend = 'stabil'
            confidence = 0.78
        else:
            daily_avg = round(total_terjual_30_hari / 30.0, 1)
            rata_rata_7_hari_terakhir = float(np.mean(y[-7:]))

            model = LinearRegression()
            model.fit(X, y)

            hari_ke_depan = np.arange(31, 38).reshape(-1, 1)
            prediksi_harian_regresi = np.clip(model.predict(hari_ke_depan), 0, None)
            total_7_hari_regresi = float(np.sum(prediksi_harian_regresi))

            total_7_hari_moving_avg = rata_rata_7_hari_terakhir * 7.0

            # 50% Regresi Linear + 50% Moving Average 7 Hari
            gabungan_prediksi = (0.5 * total_7_hari_regresi) + (0.5 * total_7_hari_moving_avg)
            predicted_demand = max(1, int(math.ceil(gabungan_prediksi)))

            # Arah tren dari slope garis regresi
            slope = float(model.coef_[0])
            if slope > 0.025:
                sales_trend = 'naik'
            elif slope < -0.025:
                sales_trend = 'turun'
            else:
                sales_trend = 'stabil'

            r2_score = max(0.0, float(model.score(X, y)))
            confidence = round(min(0.96, 0.84 + (r2_score * 0.12)), 2)

        # Target Stok Aman & Saran Restock
        target_stock = predicted_demand + safety_stock
        recommended_restock = max(0, target_stock - current_stock)
        days_of_supply = round(current_stock / daily_avg, 1) if daily_avg > 0 else (99 if current_stock > 0 else 0)

        # Tentukan status stok & catatan rekomendasi realistis kontekstual kos-kosan
        if current_stock <= safety_stock or (current_stock <= (predicted_demand * 0.4) and recommended_restock > 0):
            status = 'kritis'
            if 'galon' in item_name.lower():
                note = (
                    f"Stok kritis (tersisa {current_stock} {unit}, habis dalam ~{days_of_supply} hari). "
                    f"Kebutuhan 7 hari ({predicted_demand} {unit}) + batas aman ({safety_stock} {unit}) = target {target_stock} {unit}. "
                    f"Segera pesan restock +{recommended_restock} {unit} ke agen air langganan."
                )
            elif 'kopi' in item_name.lower():
                note = (
                    f"Stok kritis (sisa {current_stock} {unit}, cukup untuk ~{days_of_supply} hari). "
                    f"Tren nugas malam {sales_trend} (rata-rata {daily_avg} {unit}/hari). "
                    f"Segera belanja +{recommended_restock} sachet kopi & susu kental manis."
                )
            else:
                note = (
                    f"Stok kritis ({current_stock} {unit}, persediaan sisa ~{days_of_supply} hari). "
                    f"Target persediaan 7 hari: {target_stock} {unit}. "
                    f"Segera restock +{recommended_restock} {unit} ke gudang warkop."
                )
        elif recommended_restock > 0:
            status = 'waspada'
            if 'indomie' in item_name.lower():
                note = (
                    f"Stok menipis ({current_stock} {unit}, cukup ~{days_of_supply} hari). "
                    f"Tren pesanan malam {sales_trend} ({daily_avg} porsi/hari). "
                    f"Disarankan restock +{recommended_restock} porsi mie & telur sebelum akhir pekan."
                )
            elif 'galon' in item_name.lower():
                note = (
                    f"Stok mendekati batas aman ({current_stock} {unit}, cukup ~{days_of_supply} hari). "
                    f"Proyeksi kebutuhan 7 hari: {predicted_demand} {unit}. "
                    f"Saran restock +{recommended_restock} {unit} cadangan."
                )
            else:
                note = (
                    f"Stok perlu ditambah ({current_stock} {unit}, cukup ~{days_of_supply} hari). "
                    f"Kebutuhan 7 hari ({predicted_demand} {unit}) + cadangan ({safety_stock} {unit}) = target {target_stock} {unit}. "
                    f"Saran restock +{recommended_restock} {unit}."
                )
        else:
            status = 'aman'
            note = (
                f"Stok aman ({current_stock} {unit}, persediaan mencukupi ~{days_of_supply} hari). "
                f"Tren penjualan {sales_trend} (rata-rata konsumsi {daily_avg} {unit}/hari)."
            )

        prediction_records.append((
            item_id,
            predicted_demand,
            target_stock,
            current_stock,
            recommended_restock,
            "7 Hari ke Depan",
            daily_avg,
            safety_stock,
            sales_trend,
            status,
            note,
            confidence,
            now_str,
            now_str,
            now_str
        ))

        print(f"{item_id:<4} {item_name[:30]:<32} {current_stock:<6} {predicted_demand:<5} {target_stock:<7} {recommended_restock:<8} {daily_avg:<6.1f} {sales_trend:<7} {status}")

    # Simpan hasil prediksi ke Database
    if driver == "sqlite":
        insert_sql = """
            INSERT INTO stock_predictions (
                catalog_item_id, predicted_demand_qty, target_stock, current_stock,
                recommended_restock_qty, prediction_period, daily_avg_sales, safety_stock,
                sales_trend, status, recommendation_note, confidence_score,
                calculated_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        """
    else:
        insert_sql = """
            INSERT INTO stock_predictions (
                catalog_item_id, predicted_demand_qty, target_stock, current_stock,
                recommended_restock_qty, prediction_period, daily_avg_sales, safety_stock,
                sales_trend, status, recommendation_note, confidence_score,
                calculated_at, created_at, updated_at
            ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """

    try:
        cursor = conn.cursor()
        cursor.execute("DELETE FROM stock_predictions")
        cursor.executemany(insert_sql, prediction_records)
        conn.commit()
        print("=" * 95)
        print(f"[OK] Berhasil menyimpan {len(prediction_records)} prediksi kebutuhan stok ke database.")
    except Exception as e:
        conn.rollback()
        print(f"[ERROR] Gagal menyimpan prediksi ke database: {e}", file=sys.stderr)
        sys.exit(1)
    finally:
        conn.close()


if __name__ == "__main__":
    run_prediction()