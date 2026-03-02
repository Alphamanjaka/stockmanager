// Logique spécifique au Front-Office

document.addEventListener("DOMContentLoaded", function () {
    // --- Gestion des Alertes (SweetAlert2) ---
    // On suppose que Swal est disponible globalement via window
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
});
