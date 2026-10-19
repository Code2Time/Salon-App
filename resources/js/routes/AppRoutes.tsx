import { useEffect } from "react";
import { Route, Routes, useLocation } from "react-router";
import AdminLayout from "../layouts/AdminLayout";
import CustomerLayout from "../layouts/CustomerLayout";
import Dashboard from "../pages/panel/Dashboard";
import Home from "../pages/customer/Home";
import Preview from "../pages/Preview";
import NotFound from "../pages/NotFound";
import PlaceholderPage from "../components/common/PlaceholderPage";
import { customerNavigation, flattenNavigation, panelNavigation } from "./navigation";
import RouteAccess from "../components/auth/RouteAccess";
import AuthPage from "../pages/auth/AuthPage";
import HomeRedirect from "../pages/auth/HomeRedirect";
import PanelPageAccess from "../components/auth/PanelPageAccess";
import Users from "../pages/panel/Users";
import Profile from "../pages/panel/Profile";
import CustomerProfile from "../pages/customer/Profile";
import ServiceCategories from "../pages/panel/ServiceCategories";
import Services from "../pages/panel/Services";
import Products from "../pages/panel/Products";
import CustomerProducts from "../pages/customer/Products";
import ProductOrders from "../pages/ProductOrders";
import CustomerServices from "../pages/customer/Services";
import AppointmentSettings from "../pages/panel/AppointmentSettings";
import EmployeeSchedules from "../pages/panel/EmployeeSchedules";
import PanelAppointments from "../pages/panel/Appointments";
import CustomerAppointments from "../pages/customer/Appointments";
import CustomerBooking from "../pages/customer/Booking";
import Notifications from "../pages/Notifications";

const protectedCustomerPaths = new Set(["/app/booking", "/app/appointments", "/app/profile"]);

export default function AppRoutes() {
    const { pathname } = useLocation();
    useEffect(() => {
        const publicTitles: Record<string, string> = {
            "/": "آرا",
            "/login": "ورود",
            "/register": "ثبت‌نام",
            "/preview": "پیش‌نمایش",
            "/panel/notifications": "اعلان‌ها",
            "/app/notifications": "اعلان‌ها",
            "/app/booking": "رزرو نوبت",
            "/app/orders": "سفارش‌های من",
        };
        const current = [...flattenNavigation(panelNavigation), ...customerNavigation].find(
            (item) => item.path === pathname,
        );
        document.title = `${current?.label ?? publicTitles[pathname] ?? (/^\/(panel|app)\/orders\/\d+$/.test(pathname) ? "جزئیات سفارش" : "صفحه پیدا نشد")} | آرا`;
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
                <Route path="users" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/users")!}><Users /></PanelPageAccess>} />
                <Route path="customers" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/customers")!}><Users fixedRole="customer" title="مشتریان" eyebrow="همراهان سالن" description="اطلاعات مشتریان سالن را جستجو، ایجاد و مدیریت کنید." /></PanelPageAccess>} />
                <Route path="employees" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/employees")!}><Users fixedRole="employee" title="کارکنان" eyebrow="تیم سالن" description="اعضای تیم و وضعیت دسترسی آن‌ها را مدیریت کنید." /></PanelPageAccess>} />
                <Route path="profile" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/profile")!}><Profile /></PanelPageAccess>} />
                <Route path="service-categories" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/service-categories")!}><ServiceCategories /></PanelPageAccess>} />
                <Route path="services" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/services")!}><Services /></PanelPageAccess>} />
                <Route path="products" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/products")!}><Products /></PanelPageAccess>} />
                <Route path="orders" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/orders")!}><ProductOrders panel /></PanelPageAccess>} />
                <Route path="orders/:orderId" element={<PanelPageAccess item={panelNavigation.find((item) => item.path === "/panel/orders")!}><ProductOrders panel /></PanelPageAccess>} />
                <Route path="appointment-settings" element={<PanelPageAccess item={flattenNavigation(panelNavigation).find((item) => item.path === "/panel/appointment-settings")!}><AppointmentSettings /></PanelPageAccess>} />
                <Route path="my-schedule" element={<PanelPageAccess item={flattenNavigation(panelNavigation).find((item) => item.path === "/panel/my-schedule")!}><EmployeeSchedules /></PanelPageAccess>} />
                <Route path="employee-schedules" element={<PanelPageAccess item={flattenNavigation(panelNavigation).find((item) => item.path === "/panel/employee-schedules")!}><EmployeeSchedules management /></PanelPageAccess>} />
                <Route path="appointments" element={<PanelPageAccess item={flattenNavigation(panelNavigation).find((item) => item.path === "/panel/appointments")!}><PanelAppointments /></PanelPageAccess>} />
                <Route path="notifications" element={<Notifications />} />
                {flattenNavigation(panelNavigation).slice(1).filter((item) => !["/panel/users", "/panel/customers", "/panel/employees", "/panel/profile", "/panel/service-categories", "/panel/services", "/panel/products", "/panel/orders", "/panel/appointment-settings", "/panel/my-schedule", "/panel/employee-schedules", "/panel/appointments"].includes(item.path) && !item.children?.length).map((item) => (
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
                <Route path="services" element={<CustomerServices />} />
                <Route path="products" element={<CustomerProducts />} />
                <Route path="orders" element={<RouteAccess area="customer"><ProductOrders /></RouteAccess>} />
                <Route path="orders/:orderId" element={<RouteAccess area="customer"><ProductOrders /></RouteAccess>} />
                <Route path="booking" element={<RouteAccess area="customer"><CustomerBooking /></RouteAccess>} />
                <Route path="profile" element={<RouteAccess area="customer"><CustomerProfile /></RouteAccess>} />
                <Route path="appointments" element={<RouteAccess area="customer"><CustomerAppointments /></RouteAccess>} />
                <Route path="notifications" element={<RouteAccess area="customer"><Notifications /></RouteAccess>} />
                {customerNavigation.slice(1).filter((item) => !["/app/profile", "/app/services", "/app/products", "/app/booking", "/app/appointments"].includes(item.path)).map((item) => (
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
