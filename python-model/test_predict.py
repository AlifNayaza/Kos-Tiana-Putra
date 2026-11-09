"""Tes untuk model prediksi stok (python-model/predict.py).

Jalankan dari root proyek:  python -m unittest discover -s python-model -v

Tes memakai database SQLite sementara, jadi tidak menyentuh database development.
"""

import datetime
import os
import sqlite3
import subprocess
import sys
import tempfile
import unittest

import numpy as np

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)

import predict  # noqa: E402

SCHEMA = """
CREATE TABLE catalog_items (
    id INTEGER PRIMARY KEY, name TEXT, category TEXT, price INTEGER, stock INTEGER,
    minimum_stock INTEGER, unit TEXT, is_available INTEGER DEFAULT 1, deleted_at TEXT
);
CREATE TABLE orders (id INTEGER PRIMARY KEY, status TEXT, created_at TEXT);
CREATE TABLE order_items (id INTEGER PRIMARY KEY, order_id INTEGER, catalog_item_id INTEGER, quantity INTEGER);
CREATE TABLE stock_predictions (
    id INTEGER PRIMARY KEY AUTOINCREMENT, catalog_item_id INTEGER, predicted_demand_qty INTEGER,
    target_stock INTEGER, current_stock INTEGER, recommended_restock_qty INTEGER, prediction_period TEXT,
    daily_avg_sales REAL, safety_stock INTEGER, sales_trend TEXT, status TEXT, recommendation_note TEXT,
    confidence_score REAL, calculated_at TEXT, created_at TEXT, updated_at TEXT
);
"""


class ForecastMathTest(unittest.TestCase):
    def test_constant_demand_is_forecast_accurately(self):
        y = np.full(30, 2.0)
        total, slope = predict.forecast_next_7_days(y)
        self.assertAlmostEqual(total, 14.0, places=3)
        self.assertAlmostEqual(slope, 0.0, places=6)

    def test_forecast_is_never_negative(self):
        y = np.concatenate([np.full(15, 10.0), np.zeros(15)])  # permintaan anjlok
        total, _ = predict.forecast_next_7_days(y)
        self.assertGreaterEqual(total, 0.0)

    def test_backtest_gives_high_accuracy_for_steady_sales(self):
        self.assertGreaterEqual(predict.backtest_accuracy(np.full(30, 3.0)), 0.95)

    def test_backtest_is_low_for_erratic_sales(self):
        y = np.zeros(30)
        y[-3] = 40.0  # lonjakan sekali di periode uji, tidak terduga dari data latih
        y[:23] = 1.0
        self.assertLess(predict.backtest_accuracy(y), 0.5)

    def test_backtest_returns_none_without_data(self):
        self.assertIsNone(predict.backtest_accuracy(np.zeros(30)))
        self.assertIsNone(predict.backtest_accuracy(np.ones(10)))


class EndToEndTest(unittest.TestCase):
    def setUp(self):
        fd, self.db_path = tempfile.mkstemp(suffix=".sqlite")
        os.close(fd)
        conn = sqlite3.connect(self.db_path)
        conn.executescript(SCHEMA)

        today = datetime.date.today()
        # 1 = galon laris, 2 = menu tanpa penjualan, 3 = menu sudah dihapus (soft delete)
        conn.executemany(
            "INSERT INTO catalog_items (id, name, category, price, stock, minimum_stock, unit, is_available, deleted_at) VALUES (?,?,?,?,?,?,?,?,?)",
            [
                (1, "Galon Aqua 19L", "galon", 18000, 4, 5, "galon", 1, None),
                (2, "Keripik", "makanan", 5000, 30, 5, "bungkus", 1, None),
                (3, "Menu Lama", "makanan", 9000, 10, 3, "porsi", 1, "2026-01-01 00:00:00"),
            ],
        )
        order_id = 0
        for days_ago in range(29, -1, -1):
            order_id += 1
            created = (today - datetime.timedelta(days=days_ago)).strftime("%Y-%m-%d 12:00:00")
            conn.execute("INSERT INTO orders (id, status, created_at) VALUES (?,?,?)", (order_id, "selesai", created))
            conn.execute("INSERT INTO order_items (order_id, catalog_item_id, quantity) VALUES (?,?,?)", (order_id, 1, 2))
        conn.commit()
        conn.close()

    def tearDown(self):
        os.remove(self.db_path)

    def test_script_writes_predictions_for_active_items_only(self):
        env = dict(os.environ, DB_CONNECTION="sqlite", DB_DATABASE=self.db_path)
        result = subprocess.run(
            [sys.executable, os.path.join(HERE, "predict.py")],
            env=env, capture_output=True, text=True, timeout=120,
        )
        self.assertEqual(result.returncode, 0, result.stderr)

        conn = sqlite3.connect(self.db_path)
        rows = {r[0]: r for r in conn.execute(
            "SELECT catalog_item_id, predicted_demand_qty, recommended_restock_qty, status, confidence_score FROM stock_predictions"
        )}
        conn.close()

        self.assertEqual(set(rows), {1, 2}, "Item yang sudah dihapus tidak boleh diprediksi.")

        galon = rows[1]
        self.assertGreaterEqual(galon[1], 13)          # ~2 galon/hari x 7 hari
        self.assertGreater(galon[2], 0)                # stok 4 < kebutuhan -> perlu restock
        self.assertEqual(galon[3], "kritis")
        self.assertGreaterEqual(galon[4], 0.9)         # penjualan stabil -> backtest akurat

        sepi = rows[2]
        self.assertEqual(sepi[4], 0.0, "Tanpa riwayat penjualan, akurasi tidak boleh dikarang.")


if __name__ == "__main__":
    unittest.main()
