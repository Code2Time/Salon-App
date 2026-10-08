import type { ReactNode } from "react";
import { Navigate } from "react-router";
import { useAuth } from "../../auth/AuthContext";
import type { NavigationItem } from "../../routes/navigation";

export default function PanelPageAccess({ item, children }: { item: NavigationItem; children: ReactNode }) {
    const { user } = useAuth();
    if (user?.system_role === "super_admin" || !item.roles) return children;
    const role = user?.memberships[0]?.role;
    return role && item.roles.includes(role) ? children : <Navigate to="/panel" replace />;
}
