import "./bootstrap";

// --- 1. Core Libraries ---
import $ from "jquery";
import "bootstrap";

// --- 2. Styles (CSS) ---
// Library CSS
import "select2/dist/css/select2.min.css";
import "select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css";
// Custom CSS
import "../css/purchase-form.css";

// --- 3. Third-Party Libraries ---
// Chart.js
import Chart from "chart.js/auto";
import annotationPlugin from "chartjs-plugin-annotation";
import "chartjs-adapter-date-fns";

// Tabulator
import { TabulatorFull as Tabulator } from "tabulator-tables";
// SweetAlert2
import Swal from "sweetalert2";
// Select2
import select2 from "select2";

// --- 4. Global Assignments & Configuration ---
window.$ = window.jQuery = $;
window.Tabulator = Tabulator;
window.Swal = Swal;

Chart.register(annotationPlugin);
window.Chart = Chart;
select2();

// --- 5. Custom Scripts ---
import "./themes.js";
import "./sidebar.js";
