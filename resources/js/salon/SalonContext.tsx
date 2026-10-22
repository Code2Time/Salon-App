import { createContext, useCallback, useContext, useEffect, useRef, useState } from "react";
import type { ReactNode } from "react";
import { useLocation } from "react-router";
import { useAuth } from "../auth/AuthContext";
import { fetchSalon } from "../services/api/salon";
import type { SalonDetails } from "../services/api/salon";
import { readableApiError } from "../services/api/client";

interface SalonContextValue {
    salon: SalonDetails | null;
    loading: boolean;
    error: string | null;
    reload: () => Promise<void>;
    setSalon: (salon: SalonDetails) => void;
}
const SalonContext = createContext<SalonContextValue | null>(null);

export function SalonProvider({ children }: { children: ReactNode }) {
    const { user, loading: authLoading } = useAuth();
    const { pathname } = useLocation();
    const panel = pathname.startsWith("/panel") && Boolean(user?.abilities.access_panel);
    const [salon, setSalon] = useState<SalonDetails | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const requestId = useRef(0);
    const reload = useCallback(async () => {
        const request = ++requestId.current;
        setLoading(true);
        setError(null);
        try {
            const result = await fetchSalon(panel);
            if (request === requestId.current) setSalon(result);
        } catch (caught) {
            if (request === requestId.current) { setSalon(null); setError(readableApiError(caught)); }
        } finally {
            if (request === requestId.current) setLoading(false);
        }
    }, [panel, user?.id]);
    useEffect(() => {
        if (!authLoading) void reload();
        return () => { requestId.current += 1; };
    }, [authLoading, reload]);
    useEffect(() => {
        document.querySelector('meta[name="apple-mobile-web-app-title"]')?.setAttribute("content", salon?.name ?? "سالن زیبایی");
    }, [salon?.name]);
    return <SalonContext.Provider value={{ salon, loading, error, reload, setSalon }}>{children}</SalonContext.Provider>;
}

export function useSalon() {
    const context = useContext(SalonContext);
    if (!context) throw new Error("SalonProvider is missing.");
    return context;
}
