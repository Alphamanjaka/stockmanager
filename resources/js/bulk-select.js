// Sélection multiple des listes du back office (<x-ui.bulk-bar>, <x-ui.bulk-check-all>, <x-ui.bulk-check>).
// Dans chaque conteneur [data-bulk] : la barre d'actions apparaît dès qu'une ligne est cochée ;
// ses formulaires [data-bulk-form] et ses liens [data-bulk-link] (exports) reçoivent les ids[] cochés.
// Raccourcis : Maj + clic coche une plage de lignes, Échap vide la sélection.
// Un tableau JavaScript (Tabulator, liste des achats) pilote la barre avec createBulkBar().

/**
 * Barre d'actions groupées : update(ids) l'affiche avec la sélection, onClear(callback) est appelé
 * par « Clear selection » et par Échap.
 */
export function createBulkBar(bar) {
    const counter = bar.querySelector("[data-bulk-count]");
    let clearCallback = () => {};

    // Les liens d'export gardent les filtres de la page : seuls les ids[] sont remplacés
    bar.querySelectorAll("[data-bulk-link]").forEach((link) => {
        link.dataset.baseHref = link.href;
    });

    const update = (ids) => {
        bar.hidden = ids.length === 0;
        counter.textContent = ids.length;

        bar.querySelectorAll("form[data-bulk-form]").forEach((form) => {
            form.querySelectorAll("input[data-bulk-id]").forEach((input) => input.remove());
            ids.forEach((id) => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "ids[]";
                input.value = id;
                input.dataset.bulkId = "";
                form.append(input);
            });

            // Question de confirmation avec le nombre d'éléments (« Archive 3 item(s)? »)
            form.querySelectorAll("[data-bulk-confirm]").forEach((button) => {
                button.dataset.confirm = button.dataset.bulkConfirm.replace(":count", ids.length);
            });
        });

        bar.querySelectorAll("[data-bulk-link]").forEach((link) => {
            const url = new URL(link.dataset.baseHref);
            url.searchParams.delete("ids[]");
            ids.forEach((id) => url.searchParams.append("ids[]", id));
            link.href = url.toString();
        });
    };

    /** Filtres de la page changés sans rechargement (Tabulator) : nouvelle base des liens d'export. */
    const setBaseParams = (params) => {
        bar.querySelectorAll("[data-bulk-link]").forEach((link) => {
            const url = new URL(link.dataset.baseHref);
            url.search = new URLSearchParams(params).toString();
            link.dataset.baseHref = url.toString();
        });
    };

    bar.querySelector("[data-bulk-clear]")?.addEventListener("click", () => clearCallback());

    document.addEventListener("keydown", (event) => {
        // Échap ferme d'abord une boîte de dialogue ou un menu ouvert : la sélection n'est vidée que sinon
        if (event.key === "Escape" && !bar.hidden && !document.querySelector(".swal2-container, .dropdown-menu.show, .modal.show")) {
            clearCallback();
        }
    });

    return {
        update,
        setBaseParams,
        onClear: (callback) => {
            clearCallback = callback;
        },
    };
}

/** Tableau HTML : cases [data-bulk-item], case « tout cocher » [data-bulk-all]. */
function setupBulkSelection(scope) {
    const barElement = scope.querySelector("[data-bulk-bar]");
    if (!barElement) {
        return;
    }

    const bar = createBulkBar(barElement);
    const all = scope.querySelector("[data-bulk-all]");
    const items = () => [...scope.querySelectorAll("[data-bulk-item]")];
    let lastClicked = null;

    const refresh = () => {
        const checked = items().filter((item) => item.checked);

        if (all) {
            all.checked = checked.length > 0 && checked.length === items().length;
            all.indeterminate = checked.length > 0 && !all.checked;
        }
        items().forEach((item) => item.closest("tr")?.classList.toggle("is-selected", item.checked));

        // Plusieurs lignes peuvent porter le même élément (variantes d'un produit) : chaque id compte une fois
        bar.update([...new Set(checked.map((item) => item.value))]);
    };

    all?.addEventListener("change", () => {
        items().forEach((item) => {
            item.checked = all.checked;
        });
        refresh();
    });

    scope.addEventListener("click", (event) => {
        const item = event.target.closest("[data-bulk-item]");
        if (!item) {
            return;
        }

        // Maj + clic : la plage entre le dernier clic et celui-ci prend l'état de la case cliquée
        const list = items();
        if (event.shiftKey && lastClicked && lastClicked !== item && list.includes(lastClicked)) {
            const [from, to] = [list.indexOf(lastClicked), list.indexOf(item)].sort((a, b) => a - b);
            list.slice(from, to + 1).forEach((other) => {
                other.checked = item.checked;
            });
        }
        lastClicked = item;

        // Les autres lignes du même élément suivent (toutes les variantes d'un produit)
        list.forEach((other) => {
            if (other.value === item.value) {
                other.checked = item.checked;
            }
        });
        refresh();
    });

    bar.onClear(() => {
        items().forEach((item) => {
            item.checked = false;
        });
        refresh();
    });

    // Retour arrière du navigateur : les cases restées cochées sont reprises
    refresh();
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-bulk]:not([data-bulk-manual])").forEach(setupBulkSelection);
});
