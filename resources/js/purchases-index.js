document.addEventListener("DOMContentLoaded", function () {
    const tableElement = document.getElementById("purchases-table");
    if (!tableElement) {
        return; // Ne rien faire si le tableau n'est pas sur la page
    }

    /**
     * Met à jour le statut d'un achat via une requête AJAX.
     * @param {string} url - L'URL pour la mise à jour.
     * @param {string} token - Le jeton CSRF.
     * @param {string} newState - Le nouveau statut à appliquer.
     * @param {function} successCallback - Fonction à appeler en cas de succès.
     */
    const updatePurchaseState = (url, token, newState, successCallback) => {
        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                Accept: "application/json",
            },
            body: JSON.stringify({
                state: newState,
                _method: "PATCH",
            }),
        })
            .then((response) =>
                response.json().then((data) => ({
                    ok: response.ok,
                    data,
                })),
            )
            .then(({ ok, data }) => {
                if (ok) {
                    Swal.fire({
                        icon: "success",
                        title: "Succès!",
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                    successCallback();
                } else {
                    throw new Error(
                        data.message || "Une erreur inconnue est survenue.",
                    );
                }
            })
            .catch((error) => {
                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: error.message,
                });
            });
    };

    /**
     * Formateur pour la colonne "Actions" de Tabulator, affichant des boutons contextuels.
     */
    const actionsFormatter = (cell) => {
        const data = cell.getRow().getData();
        const urls = data.urls;
        const state = data.state;
        let buttons = "";

        switch (state) {
            case "Draft":
                buttons = `
                    <a href="${urls.edit}" class="btn btn-sm btn-outline-primary" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="${urls.destroy}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet achat ?');">
                        <input type="hidden" name="_token" value="${urls.csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                `;
                break;
            case "Ordered":
                buttons = `
                    <button class="btn btn-sm btn-success quick-update-state" data-url="${urls.update_state}" data-token="${urls.csrf}" data-new-state="Received" title="Marquer comme Reçu">
                        <i class="fas fa-check-circle"></i> Reçu
                    </button>
                `;
                break;
            case "Received":
                buttons = `
                    <button class="btn btn-sm btn-primary quick-update-state" data-url="${urls.update_state}" data-token="${urls.csrf}" data-new-state="Paid" title="Marquer comme Payé">
                        <i class="fas fa-dollar-sign"></i> Payé
                    </button>
                `;
                break;
        }

        // Le bouton "Voir" est toujours présent
        return `
            <div class="btn-group">
                <a href="${urls.show}" class="btn btn-sm btn-outline-secondary" title="Voir">
                    <i class="fas fa-eye"></i>
                </a>
                ${buttons}
            </div>
        `;
    };

    // Initialisation de Tabulator
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
        placeholder: "Aucun achat trouvé",
        columns: [
            {
                title: "Reference",
                field: "reference",
                headerFilter: "input",
            },
            {
                title: "Supplier", field: "supplier_name",
                headerFilter: "input",
                width: 200,
             },
            { title: "Date", field: "date", hozAlign: "center", width: 120 },
            {
                title: "Statut",
                field: "state",
                hozAlign: "center",
                formatter: "html",
                width: 100,
            },
            {
                title: "Total Net",
                field: "total_net",
                hozAlign: "right",
                formatter: "html",
                width: 150,
            },
            {
                title: "Actions",
                field: "urls",
                formatter: actionsFormatter,
                hozAlign: "center",
                headerSort: false,
                width: 150,
            },
        ],
    });

    // Gestion du clic sur les onglets de statut
    document.querySelectorAll("#state-tabs .nav-link").forEach((tab) => {
        tab.addEventListener("click", function () {
            const state = this.getAttribute("data-state");
            table.clearFilter();
            if (state) {
                table.setFilter("state", "=", state);
            }
        });
    });

    // Gestion des clics sur les boutons d'action rapide (délégation d'événement)
    tableElement.addEventListener("click", function (e) {
        const quickButton = e.target.closest("button.quick-update-state");
        if (quickButton) {
            const url = quickButton.dataset.url;
            const token = quickButton.dataset.token;
            const newState = quickButton.dataset.newState;

            updatePurchaseState(url, token, newState, () => {
                table.setData(); // Recharge les données du tableau
            });
        }
    });
});
