import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Points d'entrée communs
                "resources/css/custom.css", // Contient Bootstrap, FontAwesome, etc.
                "resources/js/app.js",

                // Points d'entrée du Back-Office
                "resources/css/back-office.css",
                "resources/js/back.js",
                "resources/js/purchases-index.js",

                // Points d'entrée du Front-Office
                "resources/css/front.css",
                "resources/js/front.js",

                // Écran de caisse (front et back office)
                "resources/js/pos.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // Docker : écouter sur toutes les interfaces du conteneur, le navigateur passe par localhost:5173
        host: "0.0.0.0",
        port: 5173,
        strictPort: true,
        hmr: { host: "localhost" },
        watch: {
            ignored: ["**/storage/framework/views/**"],
            // Les fichiers modifiés sous Windows ne génèrent pas d'événement dans un conteneur : scrutation (Docker uniquement)
            usePolling: process.env.VITE_POLLING === "true",
            interval: 300,
        },
    },
});
