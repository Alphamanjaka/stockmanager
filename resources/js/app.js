import "./bootstrap";

// --- 1. Core Libraries ---
import $ from "jquery";
import * as bootstrap from "bootstrap";

// --- 2. Third-Party Libraries ---
// Chart.js
import Chart from "chart.js/auto";
import annotationPlugin from "chartjs-plugin-annotation";
import "chartjs-adapter-date-fns";

// Tabulator
import { TabulatorFull as Tabulator } from "tabulator-tables";
// Select2
import select2 from "select2";

// --- 3. Interface commune (boîtes de dialogue, confirmations, couleurs des graphiques) ---
import { AppSwal, token, alpha, chartPalette } from "./ui.js";

// --- 4. Global Assignments & Configuration ---
// Les styles des bibliothèques sont chargés par resources/css/custom.css
window.$ = window.jQuery = $;
window.bootstrap = bootstrap;
window.Tabulator = Tabulator;
window.Swal = AppSwal;
window.appTheme = { token, alpha, chartPalette };

Chart.register(annotationPlugin);
window.Chart = Chart;
select2();

// --- 5. Custom Scripts ---
import "./themes.js";
import "./global-search.js";
