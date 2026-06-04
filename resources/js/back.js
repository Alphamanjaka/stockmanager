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
