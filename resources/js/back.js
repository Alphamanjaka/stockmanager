// Logique spécifique au Back-Office

document.addEventListener("DOMContentLoaded", function () {
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
                timer: 1000,
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
    // Les touches viennent du menu déclaré par les modules (BackOfficeMenu), rendu en JSON par le layout.
    const shortcutsData = document.getElementById("bo-shortcuts");
    const shortcuts = shortcutsData ? JSON.parse(shortcutsData.textContent) : {};
    const launcherToggle = document.getElementById("bo-launcher-toggle");

    const openLauncher = () => {
        const dropdown = bootstrap.Dropdown.getOrCreateInstance(launcherToggle);
        dropdown.toggle();

        // Ouvert au clavier : le focus va sur le module courant, les flèches parcourent la liste
        const panel = launcherToggle.nextElementSibling;
        if (panel.classList.contains("show")) {
            (
                panel.querySelector(".is-active .dropdown-item") ||
                panel.querySelector(".dropdown-item")
            )?.focus();
        }
    };

    document.addEventListener("keydown", function (e) {
        // AltGr (= Ctrl + Alt sous Windows) sert à taper des caractères : on ne l'intercepte pas
        if (!e.altKey || e.ctrlKey || e.metaKey || e.repeat) {
            return;
        }

        // Sur Mac, Option + lettre produit un caractère spécial : on se rabat sur la touche physique
        const key = /^[a-z]$/i.test(e.key)
            ? e.key.toLowerCase()
            : e.code.startsWith("Key")
              ? e.code.slice(3).toLowerCase()
              : "";

        if (launcherToggle && key === launcherToggle.dataset.shortcut) {
            e.preventDefault();
            openLauncher();
        } else if (Object.hasOwn(shortcuts, key)) {
            e.preventDefault();
            window.location.href = shortcuts[key];
        }
    });
});
