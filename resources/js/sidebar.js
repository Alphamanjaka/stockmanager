const sidebarToggle = document.getElementById("sidebarToggle");
const sidebar = document.querySelector(".sidebar");
const mainContent = document.querySelector("main");

if (sidebarToggle && sidebar && mainContent) {
    sidebarToggle.addEventListener("click", () => {
        if (window.innerWidth > 768) {
            // Logique Desktop : Réduire
            sidebar.classList.toggle("collapsed");
            mainContent.classList.toggle("expanded");
        } else {
            // Logique Mobile : Afficher/Cacher
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

const toggleSidebarBtn = document.getElementById("toggleSidebar");
if (toggleSidebarBtn) {
    toggleSidebarBtn.addEventListener("click", function () {
        const sb = document.querySelector(".sidebar");
        if (sb) sb.classList.toggle("collapsed");
    });
}

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
