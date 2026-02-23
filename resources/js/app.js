import './bootstrap';
import { TabulatorFull as Tabulator } from 'tabulator-tables';

// Import jQuery et le rendre global
import $ from 'jquery';
window.$ = window.jQuery = $;

// Import Bootstrap (JS)
import 'bootstrap';

// Import Select2 (JS)
import 'select2';

// Import Chart.js et le rendre global
import Chart from 'chart.js/auto';
import annotationPlugin from 'chartjs-plugin-annotation';
import 'chartjs-adapter-date-fns';

Chart.register(annotationPlugin); // Enregistrement global
window.Chart = Chart;

// Import Tabulator
window.Tabulator = Tabulator;


