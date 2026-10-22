import { createContext, useContext, useEffect, useRef, useState } from "react";
import type { ReactNode } from "react";
import { useLocation } from "react-router";

interface InstallPrompt extends Event {
    prompt(): Promise<void>;
    userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
}
interface PwaState {
    installed: boolean;
    secure: boolean;
    ios: boolean;
    canInstall: boolean;
    installing: boolean;
    error: string | null;
    updateReady: boolean;
    install: () => Promise<void>;
    update: () => void;
}
const PwaContext = createContext<PwaState | null>(null);
const standalone = () => window.matchMedia("(display-mode: standalone)").matches || (navigator as Navigator & { standalone?: boolean }).standalone === true;

export function PwaProvider({ children }: { children: ReactNode }) {
    const { pathname } = useLocation();
    const customerArea = pathname === "/app" || pathname.startsWith("/app/");
    const [installed, setInstalled] = useState(standalone);
    const [prompt, setPrompt] = useState<InstallPrompt | null>(null);
    const [installing, setInstalling] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [updateReady, setUpdateReady] = useState(false);
    const registration = useRef<ServiceWorkerRegistration | null>(null);
    const reloadRequested = useRef(false);
    const secure = window.isSecureContext;
    const ios = /iPhone|iPad|iPod/.test(navigator.userAgent) || (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);

    useEffect(() => {
        const media = window.matchMedia("(display-mode: standalone)");
        const onMode = () => setInstalled(standalone());
        const onPrompt = (event: Event) => { event.preventDefault(); setPrompt(event as InstallPrompt); };
        const onInstalled = () => { setInstalled(true); setPrompt(null); setInstalling(false); };
        window.addEventListener("beforeinstallprompt", onPrompt);
        window.addEventListener("appinstalled", onInstalled);
        media.addEventListener("change", onMode);
        return () => { window.removeEventListener("beforeinstallprompt", onPrompt); window.removeEventListener("appinstalled", onInstalled); media.removeEventListener("change", onMode); };
    }, []);

    useEffect(() => {
        if (!customerArea || !secure || !import.meta.env.PROD || !("serviceWorker" in navigator)) return;
        let disposed = false;
        let reg: ServiceWorkerRegistration | null = null;
        const listeners: Array<() => void> = [];
        const detectUpdate = () => { if (!disposed && reg?.waiting && navigator.serviceWorker.controller) setUpdateReady(true); };
        const onUpdateFound = () => {
            const worker = reg?.installing;
            if (!worker) return;
            worker.addEventListener("statechange", detectUpdate);
            listeners.push(() => worker.removeEventListener("statechange", detectUpdate));
        };
        const onController = () => { if (reloadRequested.current) window.location.reload(); };
        const onVisible = () => { if (document.visibilityState === "visible") void reg?.update().catch(() => undefined); };
        navigator.serviceWorker.addEventListener("controllerchange", onController);
        document.addEventListener("visibilitychange", onVisible);
        void navigator.serviceWorker.register("/sw.js", { scope: "/", updateViaCache: "none" }).then(value => {
            if (disposed) return;
            reg = value;
            registration.current = value;
            reg.addEventListener("updatefound", onUpdateFound);
            onUpdateFound();
            detectUpdate();
        }).catch(() => { if (!disposed) setError("آماده‌سازی برنامه کامل نشد؛ اتصال را بررسی و صفحه را دوباره باز کنید."); });
        return () => {
            disposed = true;
            reg?.removeEventListener("updatefound", onUpdateFound);
            listeners.forEach(remove => remove());
            navigator.serviceWorker.removeEventListener("controllerchange", onController);
            document.removeEventListener("visibilitychange", onVisible);
        };
    }, [customerArea, secure]);

    async function install() {
        if (!prompt || installing) return;
        setInstalling(true); setError(null);
        try {
            await prompt.prompt();
            await prompt.userChoice;
            setPrompt(null);
        } catch { setError("مرورگر نصب را انجام نداد؛ از راهنمای نصب پایین استفاده کنید."); }
        finally { setInstalling(false); }
    }
    function update() {
        if (!registration.current?.waiting) return;
        reloadRequested.current = true;
        registration.current.waiting.postMessage({ type: "SKIP_WAITING" });
    }
    return <PwaContext.Provider value={{ installed, secure, ios, canInstall: Boolean(prompt && secure && !installed), installing, error, updateReady, install, update }}>{children}</PwaContext.Provider>;
}
export function usePwa() {
    const value = useContext(PwaContext);
    if (!value) throw new Error("PwaProvider is missing.");
    return value;
}
