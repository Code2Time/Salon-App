import { useEffect } from "react";
import { Route, Routes, useLocation } from "react-router";
import AdminLayout from "../layouts/AdminLayout";
import CustomerLayout from "../layouts/CustomerLayout";
import Dashboard from "../pages/panel/Dashboard";
import Home from "../pages/customer/Home";
import Preview from "../pages/Preview";
import NotFound from "../pages/NotFound";
import PlaceholderPage from "../components/common/PlaceholderPage";
import { customerNavigation, panelNavigation } from "./navigation";
import RouteAccess from "../components/auth/RouteAccess";
import AuthPage from "../pages/auth/AuthPage";
import HomeRedirect from "../pages/auth/HomeRedirect";
import PanelPageAccess from "../components/auth/PanelPageAccess";

const protectedCustomerPaths = new Set(["/app/booking", "/app/appointments", "/app/profile"]);

export default function AppRoutes() {
    const { pathname } = useLocation();
    useEffect(() => {
        const publicTitles: Record<string, string> = {
            "/": "آرا",
            "/login": "ورود",
            "/register": "ثبت‌نام",
            "/preview": "پیش‌نمایش",
        };
        const current = [...panelNavigation, ...customerNavigation].find(
            (item) => item.path === pathname,
        );
        document.title = `${current?.label ?? publicTitles[pathname] ?? "صفحه پیدا نشد"} | آرا`;
        window.scrollTo(0, 0);
    }, [pathname]);
    return (
        <Routes>
            <Route path="/" element={<HomeRedirect />} />
            <Route path="/login" element={<AuthPage mode="login" />} />
            <Route path="/register" element={<AuthPage mode="register" />} />
            <Route path="/preview" element={<Preview />} />
            <Route
                path="/panel"
                element={
                    <RouteAccess area="panel">
                        <AdminLayout />
                    </RouteAccess>
                }
            >
                <Route index element={<Dashboard />} />
                {panelNavigation.slice(1).map((item) => (
                    <Route
                        key={item.path}
                        path={item.path.slice("/panel/".length)}
                        element={
                            <PanelPageAccess item={item}>
                                <PlaceholderPage item={item} />
                            </PanelPageAccess>
                        }
                    />
                ))}
                <Route path="*" element={<NotFound />} />
            </Route>
            <Route path="/app" element={<CustomerLayout />}>
                <Route index element={<Home />} />
                {customerNavigation.slice(1).map((item) => (
                    <Route
                        key={item.path}
                        path={item.path.slice("/app/".length)}
                        element={protectedCustomerPaths.has(item.path)
                            ? <RouteAccess area="customer"><PlaceholderPage item={item} /></RouteAccess>
                            : <PlaceholderPage item={item} />}
                    />
                ))}
                <Route path="*" element={<NotFound />} />
            </Route>
            <Route path="*" element={<NotFound />} />
        </Routes>
    );
}
