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
     * Supprime un achat via une requête AJAX après confirmation.
     * @param {string} url - L'URL pour la suppression.
     * @param {string} token - Le jeton CSRF.
     * @param {function} successCallback - Fonction à appeler en cas de succès.
     */
    const deletePurchase = (url, token, successCallback) => {
        Swal.fire({
            title: "Êtes-vous sûr?",
            text: "Cette action est irréversible!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Oui, supprimer!",
            cancelButtonText: "Annuler",
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(url, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": token,
                        Accept: "application/json",
                    },
                })
                    .then((response) =>
                        response
                            .json()
                            .then((data) => ({ ok: response.ok, data })),
                    )
                    .then(({ ok, data }) => {
                        if (ok) {
                            Swal.fire("Supprimé!", data.message, "success");
                            successCallback();
                        } else {
                            throw new Error(
                                data.message ||
                                    "Une erreur inconnue est survenue.",
                            );
                        }
                    })
                    .catch((error) => {
                        Swal.fire("Erreur!", error.message, "error");
                    });
            }
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
                    <button class="btn btn-sm btn-outline-danger quick-delete" data-url="${urls.destroy}" data-token="${urls.csrf}" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
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
                width: 200,
            },
            {
                title: "Fournisseur",
                field: "supplier.name", // Utilise la notation "dot" pour les objets imbriqués
                headerFilter: "input",
                width: 200,
            },
            {
                title: "Date",
                field: "date",
                hozAlign: "center",
                width: 120,
                formatter: "datetime", // Formateur de date
                formatterParams: {
                    outputFormat: "dd/MM/yyyy",
                    invalidPlaceholder: "(date invalide)",
                },
            },
            {
                title: "Statut",
                field: "state",
                hozAlign: "center",
                formatter: "html",
                width: 120,
            },
            {
                title: "Total Net",
                field: "total_net",
                hozAlign: "right",
                formatter: "money", // Formateur monétaire
                formatterParams: {
                    decimal: ",",
                    thousand: " ",
                    symbol: " Mga",
                    symbolAfter: true,
                    precision: 2,
                },
                width: 150,
            },
            {
                title: "Actions",
                field: "urls",
                formatter: actionsFormatter,
                hozAlign: "center",
                headerSort: false,
                width: 180,
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

        const deleteButton = e.target.closest("button.quick-delete");
        if (deleteButton) {
            const url = deleteButton.dataset.url;
            const token = deleteButton.dataset.token;
            deletePurchase(url, token, () => {
                table.setData(); // Recharge les données du tableau
            });
        }
    });
});
