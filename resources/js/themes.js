document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("theme-toggle");
    const themeIcon = document.getElementById("theme-icon");
    const root = document.documentElement; // apply on <html>

    const prefersDarkMQ = window.matchMedia("(prefers-color-scheme: dark)");

    function updateIcon(theme) {
        if (!themeIcon || !themeToggle) return;
        // normalize classes
        themeIcon.classList.remove("fa-moon", "fa-sun");
        if (theme === "dark") {
            themeIcon.classList.add("fa-sun");
            themeToggle.classList.remove("text-dark");
            themeToggle.classList.add("text-warning");
        } else {
            themeIcon.classList.add("fa-moon");
            themeToggle.classList.remove("text-warning");
            themeToggle.classList.add("text-dark");
        }
        themeToggle.setAttribute(
            "aria-pressed",
            theme === "dark" ? "true" : "false",
        );
        themeToggle.title =
            theme === "dark"
                ? "Passer au thème clair"
                : "Passer au thème sombre";
    }

    function setTheme(theme, source = "user") {
        root.setAttribute("data-theme", theme);
        root.setAttribute("data-theme-source", source);
        try {
            localStorage.setItem("theme", theme);
        } catch (e) {
            // ignore storage errors
        }
        updateIcon(theme);
    }

    // Initialize
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
        setTheme(savedTheme, "user");
    } else {
        const systemTheme = prefersDarkMQ.matches ? "dark" : "light";
        root.setAttribute("data-theme", systemTheme);
        root.setAttribute("data-theme-source", "system");
        updateIcon(systemTheme);
    }

    // React to system theme changes only when user hasn't chosen a theme
    prefersDarkMQ.addEventListener("change", (e) => {
        if (root.getAttribute("data-theme-source") === "system") {
            setTheme(e.matches ? "dark" : "light", "system");
        }
    });

    // Toggle handler
    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            const current =
                root.getAttribute("data-theme") === "dark" ? "dark" : "light";
            const next = current === "dark" ? "light" : "dark";
            setTheme(next, "user");
        });
    }
});
