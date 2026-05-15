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
    base: "http://127.0.0.1:8000/",
    server: {
        host: "0.0.0.0",
        hmr: {
            host: "http://127.0.0.1:8000/",
            protocol: "ws",
        },
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
