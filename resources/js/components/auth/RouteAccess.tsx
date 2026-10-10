import type { ReactNode } from "react";
import { Navigate, useLocation } from "react-router";
import { useAuth } from "../../auth/AuthContext";
import AppLoading from "../common/AppLoading";

export default function RouteAccess({ area, children }: {
    area: "panel" | "customer";
    children: ReactNode;
}) {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return <AppLoading />;
    }
    if (!user) {
        return <Navigate to="/login" replace state={{ from: location.pathname + location.search + location.hash }} />;
    }
    const allowed = area === "panel" ? user.abilities.access_panel : true;
    if (!allowed) return <Navigate to={user.home_path} replace />;

    return children;
}
