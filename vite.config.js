import { readFileSync, writeFileSync } from "node:fs";
import { createHash } from "node:crypto";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.tsx",
                "resources/static/logos/favicon-circle.png",
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
        {
            name: "salon-service-worker",
            apply: "build",
            closeBundle() {
                const manifest = readFileSync(new URL("./public/build/manifest.json", import.meta.url));
                const source = readFileSync(new URL("./resources/js/pwa/service-worker.js", import.meta.url), "utf8");
                const offline = readFileSync(new URL("./public/pwa/offline.html", import.meta.url));
                const version = createHash("sha256").update(manifest).update(source).update(offline).digest("hex").slice(0, 16);
                writeFileSync(new URL("./public/sw.js", import.meta.url), source.replace("__BUILD_ID__", version));
            },
        },
    ],

    server: {
        host: process.env.VITE_DEV_HOST ? "0.0.0.0" : "127.0.0.1",
        port: Number(process.env.DEV_VITE_PORT ?? 3001),
        strictPort: true,
        ...(process.env.VITE_DEV_HOST
            ? { hmr: { host: process.env.VITE_DEV_HOST } }
            : {}),

        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
