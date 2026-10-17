import { Link, Outlet, useLocation } from "react-router";
import { ArrowLeftRight, ArrowUpLeft, ChevronDown } from "lucide-react";
import Brand from "../components/layout/Brand";
import Navigation from "../components/layout/Navigation";
import { customerNavigation } from "../routes/navigation";
import { Avatar, Button, IconButton, Tooltip } from "@mui/material";
import { useAuth } from "../auth/AuthContext";
import { useNavigate } from "react-router";
import { toast } from "react-toastify";
import { readableApiError } from "../services/api/client";
import { defaultAvatarUrl } from "../constants/assets";
import NotificationBell from "../components/layout/NotificationBell";
import CustomerOnboardingTour from "../components/customer/CustomerOnboardingTour";

export default function CustomerLayout() {
    const { pathname } = useLocation();
    const current = customerNavigation.find((item) => item.path === pathname);
    const { user, loading, logout } = useAuth();
    const navigate = useNavigate();
    async function handleLogout() {
        try {
            await logout();
            navigate("/login");
        } catch (error) {
            toast.error(readableApiError(error));
        }
    }
    return (
        <div className="customer-shell">
            <a className="skip-link" href="#main-content">
                رفتن به محتوا
            </a>
            <header className="customer-header">
                <div className="customer-header-inner">
                    <Brand customer />
                    <div className="header-account">
                        {user?.system_role === "super_admin" && (
                            <Tooltip title="رفتن به پنل مدیریت">
                                <IconButton component={Link} to="/panel" aria-label="رفتن به پنل مدیریت" className="layout-switch">
                                    <ArrowLeftRight size={19} />
                                </IconButton>
                            </Tooltip>
                        )}
                        {user ? (
                            <>
                                <NotificationBell />
                                <Link to="/app/profile" className="account-profile-link" aria-label="مشاهده پروفایل من">
                                    <span className="account-profile-copy">
                                        <strong>{user.name}</strong>
                                        <small>{user.system_role === "super_admin" ? "مدیر کل" : "حساب مشتری"}</small>
                                    </span>
                                    <Avatar src={user.image_url ?? defaultAvatarUrl} alt={user.name} className="header-avatar" />
                                    <ChevronDown size={15} className="account-profile-chevron" aria-hidden="true" />
                                </Link>
                                <Button className="preview-return" color="inherit" onClick={handleLogout}>
                                    خروج <ArrowUpLeft size={17} />
                                </Button>
                            </>
                        ) : !loading ? (
                            <>
                                <Button component={Link} to="/login" variant="contained" size="small">
                                    ورود
                                </Button>
                                <Button component={Link} to="/register" color="inherit" size="small">
                                    ثبت‌نام
                                </Button>
                            </>
                        ) : null}
                    </div>
                </div>
            </header>
            <Navigation
                items={customerNavigation}
                label="منوی مشتری"
                className="customer-desktop-nav"
            />
            <main
                id="main-content"
                tabIndex={-1}
                className="customer-main"
                aria-label={current?.label ?? (pathname === "/app/booking" ? "رزرو نوبت" : "صفحه پیدا نشد")}
            >
                <Outlet />
            </main>
            <footer className="customer-footer">
                با عشق به زیبایی، با احترام به زمان شما.
                <span>آرا · سالن نمونه</span>
            </footer>
            <Navigation
                items={customerNavigation}
                label="منوی موبایل مشتری"
                className="customer-bottom-nav"
            />
            <CustomerOnboardingTour />
        </div>
    );
}
