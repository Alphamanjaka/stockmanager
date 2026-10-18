// Logique spécifique au Back-Office (les messages de session sont affichés par ui.js)

// Sélection multiple des listes et barre d'actions groupées
import "./bulk-select.js";

document.addEventListener("DOMContentLoaded", function () {
    // --- Raccourcis Clavier (Alt + Touche) ---
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

// Prix de vente proposé à partir du prix d'achat et de la marge par défaut (création de produits)
import "./price-suggestion.js";
