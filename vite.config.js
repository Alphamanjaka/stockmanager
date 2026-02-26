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
                "resources/css/sidebar.css",
                "resources/js/back.js",
                "resources/js/purchases-index.js",

                // Points d'entrée du Front-Office
                "resources/css/front.css",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
