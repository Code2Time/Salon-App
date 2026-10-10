import { Navigate } from "react-router";
import { useAuth } from "../../auth/AuthContext";
import AppLoading from "../../components/common/AppLoading";

export default function HomeRedirect() {
    const { user, loading } = useAuth();
    if (loading) return <AppLoading />;
    return <Navigate to={user?.home_path ?? "/app"} replace />;
}
