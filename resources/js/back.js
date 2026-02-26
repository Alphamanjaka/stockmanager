// Logique spécifique au Back-Office

document.addEventListener("DOMContentLoaded", function () {
    // --- 1. Gestion de la Sidebar avec Persistance ---
    const sidebar = document.querySelector(".sidebar");
    const toggleBtn = document.getElementById("toggleSidebar");

    // Restaurer l'état au chargement
    if (localStorage.getItem("sidebar-state") === "collapsed") {
        sidebar.classList.add("collapsed");
    }

    if (toggleBtn) {
        toggleBtn.addEventListener("click", function (e) {
            e.preventDefault();
            sidebar.classList.toggle("collapsed");

            // Sauvegarder l'état
            const state = sidebar.classList.contains("collapsed")
                ? "collapsed"
                : "expanded";
            localStorage.setItem("sidebar-state", state);
        });
    }

    // --- 2. Gestion des Alertes (SweetAlert2) ---
    // Note: On suppose que Swal est disponible globalement ou via window
    if (typeof Swal !== "undefined") {
        const successMessage = document.body.dataset.sessionSuccess;
        const errorMessage = document.body.dataset.sessionError;

        if (successMessage) {
            Swal.fire({
                icon: "success",
                title: "Succès !",
                text: successMessage,
                timer: 3000,
                showConfirmButton: false,
            });
        }

        if (errorMessage) {
            Swal.fire({
                icon: "error",
                title: "Oups...",
                text: errorMessage,
            });
        }
    }

    // --- 3. Raccourcis Clavier (Alt + Touche) ---
    document.addEventListener("keydown", function (e) {
        if (e.altKey) {
            const key = e.key.toLowerCase();
            const targetLink = document.querySelector(
                `a[data-shortcut="${key}"]`,
            );
            if (targetLink) {
                e.preventDefault();
                window.location.href = targetLink.getAttribute("href");
            }
        }
    });
});
