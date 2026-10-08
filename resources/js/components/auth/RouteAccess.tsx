import { CircularProgress } from "@mui/material";
import type { ReactNode } from "react";
import { Navigate, useLocation } from "react-router";
import { useAuth } from "../../auth/AuthContext";

export default function RouteAccess({ area, children }: {
    area: "panel" | "customer";
    children: ReactNode;
}) {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return <div className="auth-loading"><CircularProgress aria-label="در حال بررسی ورود" /></div>;
    }
    if (!user) {
        return <Navigate to="/login" replace state={{ from: location.pathname + location.search + location.hash }} />;
    }
    const allowed = area === "panel" ? user.abilities.access_panel : true;
    if (!allowed) return <Navigate to={user.home_path} replace />;

    return children;
}
