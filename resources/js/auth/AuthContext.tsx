import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import type { ReactNode } from "react";
import { fetchCurrentUser, logout as logoutRequest } from "../services/api/auth";
import type { AuthUser } from "../types/auth";

interface AuthContextValue {
    user: AuthUser | null;
    loading: boolean;
    setAuthenticatedUser: (user: AuthUser) => void;
    logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<AuthUser | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchCurrentUser()
            .then(setUser)
            .catch(() => setUser(null))
            .finally(() => setLoading(false));
    }, []);

    const logout = useCallback(async () => {
        await logoutRequest();
        setUser(null);
    }, []);

    const value = useMemo(() => ({
        user,
        loading,
        setAuthenticatedUser: setUser,
        logout,
    }), [loading, logout, user]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
    const context = useContext(AuthContext);
    if (!context) throw new Error("useAuth must be used inside AuthProvider");
    return context;
}
