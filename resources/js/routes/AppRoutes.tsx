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

export default function AppRoutes() {
    const { pathname } = useLocation();
    useEffect(() => {
        const current = [...panelNavigation, ...customerNavigation].find(
            (item) => item.path === pathname,
        );
        document.title = `${current?.label ?? (pathname === "/" ? "پیش‌نمایش" : "صفحه پیدا نشد")} | آرا`;
        window.scrollTo(0, 0);
    }, [pathname]);
    return (
        <Routes>
            <Route path="/" element={<Preview />} />
            <Route path="/panel" element={<AdminLayout />}>
                <Route index element={<Dashboard />} />
                {panelNavigation.slice(1).map((item) => (
                    <Route
                        key={item.path}
                        path={item.path.slice("/panel/".length)}
                        element={<PlaceholderPage item={item} />}
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
                        element={<PlaceholderPage item={item} />}
                    />
                ))}
                <Route path="*" element={<NotFound />} />
            </Route>
            <Route path="*" element={<NotFound />} />
        </Routes>
    );
}
