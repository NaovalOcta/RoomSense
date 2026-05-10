import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwindcss(),
    ],
    base: "https://heritage-debut-wondering-cleveland.trycloudflare.com/",
    server: {
        host: "0.0.0.0",
        hmr: {
            host: "https://heritage-debut-wondering-cleveland.trycloudflare.com/",
            protocol: "wss",
        },
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
