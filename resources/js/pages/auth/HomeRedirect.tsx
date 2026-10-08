import { CircularProgress } from "@mui/material";
import { Navigate } from "react-router";
import { useAuth } from "../../auth/AuthContext";

export default function HomeRedirect() {
    const { user, loading } = useAuth();
    if (loading) return <div className="auth-loading"><CircularProgress /></div>;
    return <Navigate to={user?.home_path ?? "/app"} replace />;
}
