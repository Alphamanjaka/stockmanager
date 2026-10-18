// Prix de vente proposé pendant la saisie du prix d'achat, avec la marge par défaut des Paramètres
// (même calcul que App\Modules\Catalog\Support\Pricing) : prix = coût × (1 + marge / 100).
//
//   <form data-margin="30">                               marge en % (absente : rien n'est proposé)
//     <tr data-pricing>                                   une paire coût / prix (ligne de la grille, fenêtre)
//       <input data-pricing-cost> <input data-pricing-price>
//
// Le prix suit le coût tant qu'il n'a pas été modifié à la main ; vidé, il suit de nouveau le coût.
// Une valeur posée par le code (saisie groupée) : data-price-manual="1" si c'est un prix choisi.

/** Prix proposé, arrondi au centime. */
export const suggestPrice = (cost, margin) => Math.round(cost * (100 + margin)) / 100;

document.addEventListener("input", (event) => {
    const field = event.target;

    if (field.matches?.("[data-pricing-price]")) {
        field.dataset.priceManual = field.value === "" ? "" : "1";
        return;
    }

    if (!field.matches?.("[data-pricing-cost]")) {
        return;
    }

    const margin = parseFloat(field.closest("[data-margin]")?.dataset.margin);
    const price = field.closest("[data-pricing]")?.querySelector("[data-pricing-price]");
    if (Number.isNaN(margin) || !price || price.dataset.priceManual === "1") {
        return;
    }

    const cost = parseFloat(field.value);
    price.value = cost > 0 ? String(suggestPrice(cost, margin)) : "";
});
