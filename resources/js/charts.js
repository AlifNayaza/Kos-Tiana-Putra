/*
 * Chart.js versi ramping: hanya komponen yang benar-benar dipakai (batang + garis),
 * dan dimuat HANYA di halaman bergrafik (dashboard, laporan, prediksi).
 * Halaman lain tidak mengunduh pustaka ini sama sekali.
 */
import {
    Chart,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    BarController, BarElement,
    LineController, LineElement, PointElement,
    CategoryScale, LinearScale,
    Tooltip, Legend, Filler,
);

window.Chart = Chart;
