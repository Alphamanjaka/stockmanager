const themeToggle = document.getElementById("theme-toggle");
const themeIcon = document.getElementById("theme-icon");
const body = document.documentElement; // On applique sur <html>

// 1. Vérifier si un thème est déjà enregistré
const currentTheme = localStorage.getItem("theme");
if (currentTheme) {
    body.setAttribute("data-theme", currentTheme);
    updateIcon(currentTheme);
}

// 2. Gérer le clic
if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        let theme =
            body.getAttribute("data-theme") === "dark" ? "light" : "dark";

        body.setAttribute("data-theme", theme);
        localStorage.setItem("theme", theme); // Sauvegarde le choix
        updateIcon(theme);
    });
}

function updateIcon(theme) {
    if (!themeIcon || !themeToggle) return;

    if (theme === "dark") {
        themeIcon.classList.replace("fa-moon", "fa-sun");
        themeToggle.classList.replace("text-dark", "text-warning");
    } else {
        themeIcon.classList.replace("fa-sun", "fa-moon");
        themeToggle.classList.replace("text-warning", "text-dark");
    }
}
