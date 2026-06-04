// Logique de la barre de recherche globale

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("global-search-input");
    const searchResultsContainer = document.getElementById(
        "global-search-results",
    );

    if (!searchInput || !searchResultsContainer) {
        // console.warn("Éléments de recherche globale non trouvés (searchInput ou searchResultsContainer).");
        return;
    }

    // Liste des routes statiques pour la recherche côté client
    const staticRoutes = [
        { type: "route", name: "Tableau de bord", url: "/admin/dashboard" },
        { type: "route", name: "Produits", url: "/admin/products" },
        {
            type: "route",
            name: "Créer un produit",
            url: "/admin/products/create",
        },
        { type: "route", name: "Catégories", url: "/admin/categories" },
        { type: "route", name: "Achats", url: "/admin/purchases" },
        {
            type: "route",
            name: "Créer un achat",
            url: "/admin/purchases/create",
        },
        { type: "route", name: "Ventes", url: "/admin/sales" },
        { type: "route", name: "Créer une vente", url: "/admin/sales/create" },
        { type: "route", name: "Fournisseurs", url: "/admin/suppliers" },
        // { type: "route", name: "Clients", url: "/admin/customers" },
        { type: "route", name: "Utilisateurs", url: "/admin/users" },
        { type: "route", name: "Rôles et Permissions", url: "/admin/roles" },
        { type: "route", name: "Paramètres", url: "/admin/settings" },
        { type: "route", name: "Sauvegardes", url: "/admin/backups" },
        {
            type: "route",
            name: "Mouvements de stock",
            name_alt: "Stock Movements",
            url: "/admin/movements",
        },
        // Ajoutez d'autres routes pertinentes ici
    ];

    let debounceTimer;

    searchInput.addEventListener("input", function () {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 2) {
            // Minimum 2 caractères pour déclencher la recherche
            searchResultsContainer.innerHTML = "";
            searchResultsContainer.classList.remove("show");
            return;
        }

        debounceTimer = setTimeout(() => performSearch(query), 300); // Délais de 300ms
    });

    async function performSearch(query) {
        let allResults = [];

        // 1. Recherche côté client pour les routes statiques,
        // Use encodeURIComponent to handle special characters in the query string
        const encodedQuery = encodeURIComponent(query);
        const lowerCaseQuery = encodedQuery.toLowerCase();

        const filteredRoutes = staticRoutes.filter(
            (route) =>
                route.name.toLowerCase().includes(lowerCaseQuery) ||
                (route.name_alt &&
                    route.name_alt.toLowerCase().includes(lowerCaseQuery)),
        );
        allResults = allResults.concat(filteredRoutes);

        // 2. Recherche côté serveur via API
        try {
            const response = await axios.get(`/api/global-search?q=${encodedQuery}`);
            allResults = allResults.concat(response.data);
        } catch (error) {
            console.error(
                "Erreur lors de la recherche globale via l'API:",
                error,
            );
            // Gérer l'erreur, par exemple afficher un message à l'utilisateur
        }

        displayResults(allResults);
    }

    function displayResults(results) {
        searchResultsContainer.innerHTML = ""; // Nettoyer les résultats précédents

        if (results.length === 0) {
            searchResultsContainer.classList.remove("show");
            return;
        }

        // Regrouper les résultats par type
        const groupedResults = results.reduce((acc, result) => {
            const type =
                result.type === "product"
                    ? "Produits"
                    : result.type === "purchase"
                      ? "Achats"
                      : result.type === "sale"
                        ? "Ventes"
                        : result.type === "movement"
                          ? "Stocks"
                      : result.type === "route"
                        ? "Pages"
                        : "Autres";
            if (!acc[type]) {
                acc[type] = [];
            }
            acc[type].push(result);
            return acc;
        }, {});

        for (const type in groupedResults) {
            const groupTitle = document.createElement("h6");
            groupTitle.classList.add("dropdown-header");
            groupTitle.textContent = type;
            searchResultsContainer.appendChild(groupTitle);

            groupedResults[type].forEach((result) => {
                const link = document.createElement("a");
                link.classList.add("dropdown-item");
                link.href = result.url;
                link.textContent = result.name;
                searchResultsContainer.appendChild(link);
            });
        }

        searchResultsContainer.classList.add("show"); // Afficher le conteneur
    }

    // Cacher les résultats si l'utilisateur clique en dehors
    document.addEventListener("click", function (e) {
        if (
            !searchInput.contains(e.target) &&
            !searchResultsContainer.contains(e.target)
        ) {
            searchResultsContainer.classList.remove("show");
        }
    });
});
