const sidebarToggle = document.getElementById("toggleSidebar");
const sidebar = document.querySelector(".sidebar");
const mainContent = document.querySelector("main");

// 1. Restaurer l'état de la sidebar au chargement
document.addEventListener("DOMContentLoaded", () => {
    if (sidebar && localStorage.getItem("sidebar-state") === "collapsed") {
        sidebar.classList.add("collapsed");
        // Mettre à jour l'icône si la sidebar est initialement collapsed
        const icon = sidebarToggle.querySelector("i");
        if (icon) {
            icon.classList.remove("fa-angle-left");
            icon.classList.add("fa-angle-right");
        }
    }
});

if (sidebarToggle && sidebar && mainContent) {
    sidebarToggle.addEventListener("click", () => {
        // Ajouter une animation de transition pour un effet plus doux
        sidebar.style.transition = "width 0.3s ease";
        // Forcer un reflow pour que la transition soit appliquée
        void sidebar.offsetWidth;

        if (window.innerWidth > 768) {
            // Logique Desktop : Réduire (Collapse)
            sidebar.classList.toggle("collapsed");
            // Sauvegarder l'état
            const state = sidebar.classList.contains("collapsed") ? "collapsed" : "expanded";
            localStorage.setItem("sidebar-state", state);
            updateToggleIcon(sidebar.classList.contains("collapsed"));
        } else {
            // Logique Mobile : Afficher/Cacher
            // Pas de persistance pour l'état mobile, car c'est temporaire
            sidebar.classList.toggle("show-mobile");
        }
    });
}

// Fermer le menu mobile si on clique en dehors
document.addEventListener("click", (e) => {
    if (
        sidebar &&
        sidebarToggle &&
        window.innerWidth <= 768 &&
        !sidebar.contains(e.target) &&
        !sidebarToggle.contains(e.target) &&
        sidebar.classList.contains("show-mobile")
    ) {
        sidebar.classList.remove("show-mobile");
    }
});

// Gestion intelligente des menus flottants (éviter le débordement bas)
const menuGroups = document.querySelectorAll(".menu-group");
menuGroups.forEach((group) => {
    group.addEventListener("mouseenter", () => {
        if (sidebar && sidebar.classList.contains("collapsed")) {
            const submenu = group.querySelector(".collapse");
            if (submenu) {
                const rect = group.getBoundingClientRect();
                // Si l'élément est dans la moitié inférieure de l'écran, on aligne par le bas (remonte)
                if (rect.top > window.innerHeight / 2) {
                    submenu.style.top = "auto";
                    submenu.style.bottom = "0";
                } else {
                    submenu.style.top = "0";
                    submenu.style.bottom = "auto";
                }
            }
        }
    });
});

/**
 * Met à jour l'icône du bouton de bascule de la sidebar.
 * @param {boolean} isCollapsed - Vrai si la sidebar est en état "collapsed".
 */
function updateToggleIcon(isCollapsed) {
    const icon = sidebarToggle.querySelector("i");
    if (icon) {
        icon.classList.remove("fa-angle-left", "fa-angle-right");
        icon.classList.add(isCollapsed ? "fa-angle-right" : "fa-angle-left");
    }
}
