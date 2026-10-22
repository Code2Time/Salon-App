import { execFileSync } from "node:child_process";
import { networkInterfaces } from "node:os";
import { isIPv4 } from "node:net";
import concurrently from "concurrently";

function detectHost() {
    if (process.env.DEV_HOST) return process.env.DEV_HOST;
    try {
        const route = execFileSync("ip", ["-4", "route", "get", "1.1.1.1"], {
            encoding: "utf8",
            stdio: ["ignore", "pipe", "ignore"],
        });
        const host = route.match(/\bsrc\s+([\d.]+)/)?.[1];
        if (host) return host;
    } catch {
        // Other platforms can use the network interface list.
    }
    for (const [name, addresses] of Object.entries(networkInterfaces())) {
        if (/^(docker|br-|veth|tun|tap|wg)/i.test(name)) continue;
        const address = addresses.find((item) => item.family === "IPv4" && !item.internal);
        if (address) return address.address;
    }
    return "127.0.0.1";
}

const host = detectHost();
const port = process.env.DEV_PORT ?? "8080";
if (!isIPv4(host) || !/^\d+$/.test(port) || Number(port) < 1 || Number(port) > 65535) {
    console.error("DEV_HOST must be an IPv4 address and DEV_PORT must be 1–65535.");
    process.exit(1);
}

Object.assign(process.env, {
    APP_URL: `http://${host}:${port}`,
    VITE_DEV_HOST: host,
    SANCTUM_STATEFUL_DOMAINS: `${host}:${port},localhost:${port},127.0.0.1:${port}`,
});
console.log(`\nSalon App: http://${host}:${port}\nLocal: http://localhost:${port}\n`);
if (process.argv.includes("--check")) process.exit(0);

execFileSync("php", ["artisan", "config:clear"], { stdio: "inherit" });
const { result } = concurrently([
    { name: "server", command: `php artisan serve --host=0.0.0.0 --port=${port} --tries=1` },
    { name: "vite", command: "npm run dev:vite" },
    { name: "queue", command: "php artisan queue:listen --queue=mail,telegram,default --tries=5 --timeout=60" },
    { name: "schedule", command: "php artisan schedule:work" },
], { killOthersOn: ["success", "failure"], killTimeout: 3000 });
result.catch(() => { process.exitCode = 1; });
