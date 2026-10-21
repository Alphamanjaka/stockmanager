// Comportements d'interface communs au back office et au front office :
// boîtes de dialogue aux couleurs de la charte, messages de session, confirmation des actions sensibles,
// et réglages par défaut des graphiques. Les couleurs sont lues dans les jetons CSS (resources/css/theme/tokens.css).

import Swal from "sweetalert2";
import { Chart } from "chart.js";

/** Valeur d'un jeton de la charte, ex. token("primary") → "#0f5ba7" (suit le thème clair/sombre). */
export const token = (name) =>
    getComputedStyle(document.documentElement).getPropertyValue(`--app-${name}`).trim();

/** Couleur #rrggbb rendue transparente, pour les aires sous les courbes. */
export const alpha = (hex, opacity) => {
    const value = parseInt(hex.replace("#", ""), 16);
    return `rgba(${(value >> 16) & 255}, ${(value >> 8) & 255}, ${value & 255}, ${opacity})`;
};

/** Couleurs des séries de graphiques, dans l'ordre. */
export const chartPalette = () => [1, 2, 3, 4, 5, 6].map((i) => token(`chart-${i}`));

// Libellés traduits fournis par le layout (<body data-i18n-cancel="..." data-i18n-confirm="...">)
const i18n = (key, fallback) => document.body?.dataset[`i18n${key}`] || fallback;

// Boutons Bootstrap ; action principale à droite, Annuler à gauche (libellé dans la langue de l'application)
export const AppSwal = Swal.mixin({
    buttonsStyling: false,
    reverseButtons: true,
    cancelButtonText: i18n("Cancel", "Cancel"),
    customClass: {
        confirmButton: "btn btn-primary",
        cancelButton: "btn btn-outline-secondary",
        denyButton: "btn btn-outline-danger",
    },
});

/** Message de session : succès en notification discrète, erreur en boîte de dialogue. */
function showSessionMessages() {
    const { sessionSuccess, sessionError } = document.body.dataset;

    if (sessionSuccess) {
        AppSwal.fire({
            toast: true,
            position: "top-end",
            icon: "success",
            title: sessionSuccess,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
    }

    if (sessionError) {
        AppSwal.fire({ icon: "error", text: sessionError });
    }
}

/**
 * Confirmation avant envoi : data-confirm="Question ?" sur le bouton d'envoi ou sur le formulaire.
 * Options : data-confirm-text (précision), data-confirm-ok (libellé du bouton), data-confirm-tone="danger".
 * Le formulaire est renvoyé avec le même bouton, dont formaction et name/value sont donc conservés.
 */
function confirmSensitiveSubmits() {
    document.addEventListener("submit", (event) => {
        // Envoi déjà arrêté par la page (contrôle de saisie) : pas de confirmation
        if (event.defaultPrevented) {
            return;
        }

        const form = event.target;
        const source = [event.submitter, form].find((element) => element?.dataset?.confirm);

        if (!source) {
            return;
        }

        if (source.dataset.confirmed) {
            delete source.dataset.confirmed;
            return;
        }

        event.preventDefault();
        const danger = source.dataset.confirmTone === "danger";

        AppSwal.fire({
            icon: danger ? "warning" : "question",
            title: source.dataset.confirm,
            text: source.dataset.confirmText || undefined,
            showCancelButton: true,
            focusCancel: danger,
            confirmButtonText: source.dataset.confirmOk || i18n("Confirm", "Confirm"),
            cancelButtonText: i18n("Cancel", "Cancel"),
            customClass: {
                confirmButton: danger ? "btn btn-danger" : "btn btn-primary",
                cancelButton: "btn btn-outline-secondary",
            },
        }).then(({ isConfirmed }) => {
            if (isConfirmed) {
                source.dataset.confirmed = "1";
                form.requestSubmit(event.submitter ?? undefined);
            }
        });
    });
}

/** Typographie, couleurs de texte et de grille des graphiques Chart.js. */
function applyChartDefaults() {
    Chart.defaults.font.family = token("font") || Chart.defaults.font.family;
    Chart.defaults.font.size = 12;
    Chart.defaults.color = token("text-muted");
    Chart.defaults.borderColor = token("chart-grid");
    Chart.defaults.elements.line.borderWidth = 2;
    Chart.defaults.elements.line.tension = 0.3;
    Chart.defaults.elements.point.radius = 0;
    Chart.defaults.elements.point.hoverRadius = 4;
    Chart.defaults.elements.point.hitRadius = 10;
    Chart.defaults.plugins.legend.labels.boxWidth = 10;
    Chart.defaults.plugins.legend.labels.boxHeight = 10;
    Chart.defaults.plugins.tooltip.backgroundColor = "#0f2742";
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 6;
    Chart.defaults.plugins.tooltip.titleFont = { weight: "600" };
}

applyChartDefaults();
document.addEventListener("DOMContentLoaded", () => {
    showSessionMessages();
    confirmSensitiveSubmits();
});
