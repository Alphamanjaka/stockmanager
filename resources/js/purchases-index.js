// Liste des achats (purchases/index.blade.php) : tableau Tabulator alimenté par l'API, filtre par statut,
// actions rapides (réception, suppression), liens d'export aux filtres du tableau.
// Boîtes de dialogue : window.Swal (charte, resources/js/ui.js).

document.addEventListener("DOMContentLoaded", function () {
    const tableElement = document.getElementById("purchases-table");
    if (!tableElement) {
        return; // Ne rien faire si le tableau n'est pas sur la page
    }

    // Les champs affichés viennent de saisies (fournisseur, référence) : échappés avant insertion dans le HTML
    const escapeHtml = (value) =>
        String(value ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

    const toast = (message) =>
        Swal.fire({ toast: true, position: "top-end", icon: "success", title: message, showConfirmButton: false, timer: 2500 });

    /** Envoie une requête JSON et renvoie la réponse, ou lève une erreur avec le message du serveur. */
    const send = (url, token, method, body = null) =>
        fetch(url, {
            method,
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": token, Accept: "application/json" },
            body: body ? JSON.stringify(body) : null,
        })
            .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) {
                    throw new Error(data.message || "An unexpected error occurred.");
                }
                return data;
            });

    const showError = (error) => Swal.fire({ icon: "error", text: error.message });

    /** Passe un achat à un nouveau statut (ex. « Received » : le stock est mis à jour). */
    const updatePurchaseState = (url, token, newState, onSuccess) => {
        Swal.fire({
            icon: "question",
            title: "Mark this purchase as received?",
            text: "The stock of every line will be increased. This cannot be undone.",
            showCancelButton: true,
            confirmButtonText: "Mark as received",
        }).then(({ isConfirmed }) => {
            if (isConfirmed) {
                send(url, token, "POST", { state: newState, _method: "PATCH" })
                    .then((data) => { toast(data.message); onSuccess(); })
                    .catch(showError);
            }
        });
    };

    /** Supprime un achat après confirmation. */
    const deletePurchase = (url, token, onSuccess) => {
        Swal.fire({
            icon: "warning",
            title: "Delete this purchase?",
            text: "This action cannot be undone.",
            showCancelButton: true,
            focusCancel: true,
            confirmButtonText: "Delete",
            customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-outline-secondary" },
        }).then(({ isConfirmed }) => {
            if (isConfirmed) {
                send(url, token, "DELETE")
                    .then((data) => { toast(data.message); onSuccess(); })
                    .catch(showError);
            }
        });
    };

    // Statut : même pastille que <x-ui.status>
    const stateTones = { Draft: "neutral", Ordered: "info", Received: "success" };
    const stateFormatter = (cell) => {
        const state = cell.getValue();
        return `<span class="ui-badge ui-badge-dot ui-tone-${stateTones[state] ?? "neutral"}">${escapeHtml(state)}</span>`;
    };

    const iconButton = (attributes, icon, title, extraClass = "") =>
        `<${attributes.href ? "a" : "button"} class="btn btn-icon ${extraClass}" title="${title}" aria-label="${title}"
            ${Object.entries(attributes).map(([key, value]) => `${key}="${value}"`).join(" ")}>
            <i class="bi ${icon}" aria-hidden="true"></i>
        </${attributes.href ? "a" : "button"}>`;

    /** Colonne « Actions » : boutons selon le statut de l'achat. */
    const actionsFormatter = (cell) => {
        const { urls, state } = cell.getRow().getData();
        let buttons = "";

        if (state === "Draft") {
            buttons =
                iconButton({ href: urls.edit }, "bi-pencil", "Edit") +
                iconButton({ type: "button", "data-url": urls.destroy, "data-token": urls.csrf }, "bi-trash3", "Delete", "btn-icon-danger quick-delete");
        } else if (state === "Ordered") {
            buttons = `
                <button type="button" class="btn btn-sm btn-outline-success quick-update-state" data-url="${urls.update_state}"
                    data-token="${urls.csrf}" data-new-state="Received">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i> Received
                </button>`;
        }

        // « Voir » est toujours présent
        return `<div class="ui-row-actions">${iconButton({ href: urls.show }, "bi-eye", "View")}${buttons}</div>`;
    };

    const table = new Tabulator(tableElement, {
        ajaxURL: tableElement.dataset.url,
        pagination: "remote",
        paginationSize: 15,
        paginationMode: "remote",
        paginationSizeSelector: [10, 15, 25, 50],
        filterMode: "remote",
        sortMode: "remote",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        placeholder: "No purchase found",
        columns: [
            { title: "Reference", field: "reference", headerFilter: "input", formatter: (cell) => `<span class="fw-semibold">${escapeHtml(cell.getValue())}</span>` },
            { title: "Supplier", field: "supplier", headerFilter: "input", minWidth: 180, formatter: (cell) => escapeHtml(cell.getValue() ?? "—") },
            { title: "Date", field: "date", width: 150 },
            { title: "Status", field: "state", formatter: stateFormatter, width: 130 },
            { title: "Total net", field: "total_net", hozAlign: "right", headerHozAlign: "right", width: 160 },
            { title: "", field: "urls", formatter: actionsFormatter, hozAlign: "right", headerSort: false, width: 190 },
        ],
    });

    // Filtre par statut (pastilles au-dessus du tableau)
    document.querySelectorAll("#state-tabs .nav-link").forEach((tab) => {
        tab.addEventListener("click", function () {
            const state = this.getAttribute("data-state");
            table.clearFilter();
            if (state) {
                table.setFilter("state", "=", state);
            }
        });
    });

    // Exports : mêmes filtres que le tableau (statut, référence, fournisseur), lus à chaque chargement
    const exportLinks = [...document.querySelectorAll("[data-export-link]")];
    exportLinks.forEach((link) => {
        link.dataset.baseHref = link.href;
    });

    table.on("dataLoaded", () => {
        const params = {};
        [...table.getFilters(), ...table.getHeaderFilters()].forEach(({ field, value }) => {
            if (["state", "reference", "supplier"].includes(field) && typeof value === "string" && value !== "") {
                params[field] = value;
            }
        });

        exportLinks.forEach((link) => {
            const url = new URL(link.dataset.baseHref);
            url.search = new URLSearchParams(params).toString();
            link.href = url.toString();
        });
    });

    // Actions rapides (délégation d'événement)
    tableElement.addEventListener("click", function (e) {
        const quickButton = e.target.closest("button.quick-update-state");
        if (quickButton) {
            const { url, token, newState } = quickButton.dataset;
            updatePurchaseState(url, token, newState, () => table.setData());
        }

        const deleteButton = e.target.closest("button.quick-delete");
        if (deleteButton) {
            const { url, token } = deleteButton.dataset;
            deletePurchase(url, token, () => table.setData());
        }
    });
});
