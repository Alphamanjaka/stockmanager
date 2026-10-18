// Thème de l'application : réglé dans Administration › Paramètres › Interface & System (clair, sombre ou automatique).
// Le layout l'applique avant l'affichage (layouts/partials/theme.blade.php) ;
// en mode automatique, on suit ici les changements de thème du système.

document.addEventListener("DOMContentLoaded", () => {
    const root = document.documentElement;

    if (root.dataset.themeMode !== "auto") {
        return;
    }

    window
        .matchMedia("(prefers-color-scheme: dark)")
        .addEventListener("change", (e) => {
            // data-bs-theme : les composants Bootstrap suivent le même thème
            root.dataset.theme = root.dataset.bsTheme = e.matches ? "dark" : "light";
        });
});
