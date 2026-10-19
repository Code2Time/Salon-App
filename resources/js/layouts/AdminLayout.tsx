import { useState } from "react";
import {
    Avatar,
    Drawer,
    IconButton,
    Tooltip,
    useMediaQuery,
    useTheme,
} from "@mui/material";
import {
    ArrowLeftRight,
    ArrowUpLeft,
    Bell,
    ChevronDown,
    Command,
    Menu,
    PanelRightClose,
    PanelRightOpen,
    Sparkles,
    X,
} from "lucide-react";
import { Link, Outlet, useLocation } from "react-router";
import Brand from "../components/layout/Brand";
import Navigation from "../components/layout/Navigation";
import { flattenNavigation, panelNavigation } from "../routes/navigation";
import type { NavigationItem } from "../routes/navigation";
import { useAuth } from "../auth/AuthContext";
import { useNavigate } from "react-router";
import { Button } from "@mui/material";
import { toast } from "react-toastify";
import { readableApiError } from "../services/api/client";
import { defaultAvatarUrl } from "../constants/assets";
import NotificationBell from "../components/layout/NotificationBell";
import ColorModeToggle from "../components/layout/ColorModeToggle";

const sidebarStateKey = "ara-panel-sidebar-collapsed";

export default function AdminLayout() {
    const [menuOpen, setMenuOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(() => {
        try {
            return window.localStorage.getItem(sidebarStateKey) === "true";
        } catch {
            return false;
        }
    });
    const desktop = useMediaQuery(useTheme().breakpoints.up("lg"));
    const { pathname } = useLocation();
    const navigate = useNavigate();
    const { user, logout } = useAuth();
    const membershipRole = user?.memberships[0]?.role;
    const membershipPermissions = user?.memberships[0]?.permissions ?? [];
    const panelRole = membershipRole === "salon_owner" || membershipRole === "employee" ? membershipRole : undefined;
    const accountRole =
        user?.system_role === "super_admin"
            ? "مدیر کل"
            : membershipRole === "salon_owner"
              ? "مدیر سالن"
              : "کارکنان سالن";
    const allowed = (item: NavigationItem) =>
        user?.system_role === "super_admin" ||
        Boolean(panelRole && (!item.roles || item.roles.includes(panelRole)) &&
            (membershipRole !== "employee" || !item.permission || membershipPermissions.includes(item.permission)));
    const visibleNavigation = panelNavigation.filter(allowed).map((item) => item.children
        ? { ...item, children: item.children.filter(allowed) }
        : item).filter((item) => !item.children || item.children.length > 0);
    const current = flattenNavigation(visibleNavigation).find((item) => item.path === pathname || (item.path === "/panel/orders" && pathname.startsWith("/panel/orders/"))) ?? (pathname === "/panel/notifications" ? {
        path: "/panel/notifications",
        label: "اعلان‌ها",
        description: "پیام‌ها و رویدادهای مهم حساب شما",
        icon: Bell,
    } : undefined);
    const CurrentIcon = current?.icon ?? Command;
    const today = new Intl.DateTimeFormat("fa-IR", {
        weekday: "long",
        day: "numeric",
        month: "long",
    }).format(new Date());
    async function handleLogout() {
        try {
            await logout();
            navigate("/login");
        } catch (error) {
            toast.error(readableApiError(error));
        }
    }
    function toggleSidebar() {
        setSidebarCollapsed((collapsed) => {
            const next = !collapsed;
            try {
                window.localStorage.setItem(sidebarStateKey, String(next));
            } catch {
                // The visual state still works when browser storage is unavailable.
            }
            return next;
        });
    }
    const sidebar = (
        <div className="sidebar-content">
            <div className="sidebar-brand">
                <Brand />
                {desktop ? (
                    <Tooltip title={sidebarCollapsed ? "باز کردن منو" : "جمع کردن منو"} placement="left">
                        <IconButton
                            className="sidebar-toggle"
                            aria-label={sidebarCollapsed ? "باز کردن منوی کناری" : "جمع کردن منوی کناری"}
                            aria-expanded={!sidebarCollapsed}
                            onClick={toggleSidebar}
                        >
                            {sidebarCollapsed ? <PanelRightOpen size={19} /> : <PanelRightClose size={19} />}
                        </IconButton>
                    </Tooltip>
                ) : (
                    <IconButton
                        aria-label="بستن منو"
                        onClick={() => setMenuOpen(false)}
                    >
                        <X size={20} />
                    </IconButton>
                )}
            </div>
            <div className="sidebar-workspace">
                <span className="sidebar-workspace-icon"><Sparkles size={16} /></span>
                <span>
                    <small>فضای کاری فعال</small>
                    <strong>سالن زیبایی آرا</strong>
                </span>
            </div>
            <p className="nav-caption">مدیریت سالن</p>
            <Navigation
                items={visibleNavigation}
                label="منوی پنل مدیریت"
                className="panel-navigation"
                onNavigate={() => setMenuOpen(false)}
            />
            <div className="sidebar-bottom">
                <div className="sidebar-note">
                    <span>ARA</span>
                    <div>
                        <strong>مرکز کنترل سالن</strong>
                        <p>همه‌چیز مرتب و در دسترس شماست.</p>
                    </div>
                </div>
                <ColorModeToggle />
                <Button className="sidebar-logout" color="inherit" onClick={handleLogout}>
                    <span>خروج از حساب</span> <ArrowUpLeft size={18} />
                </Button>
            </div>
        </div>
    );

    return (
        <div className={`admin-shell${sidebarCollapsed ? " sidebar-collapsed" : ""}`}>
            <a className="skip-link" href="#main-content">
                رفتن به محتوا
            </a>
            {desktop ? (
                <aside className="desktop-sidebar">{sidebar}</aside>
            ) : (
                <Drawer
                    anchor="left"
                    open={menuOpen}
                    onClose={() => setMenuOpen(false)}
                    slotProps={{
                        paper: {
                            sx: {
                                width: "min(310px, 88vw)",
                                bgcolor: "#4c2b3a",
                                backgroundImage: "linear-gradient(180deg, #583346, #422534)",
                                borderRadius: "0 24px 24px 0",
                                overflow: "hidden",
                            },
                            "aria-label": "منوی پنل",
                        },
                    }}
                >
                    {sidebar}
                </Drawer>
            )}
            <div className="admin-workspace">
                <header className="admin-header">
                    <div className="header-title">
                        {!desktop && (
                            <IconButton
                                aria-label="باز کردن منو"
                                aria-expanded={menuOpen}
                                onClick={() => setMenuOpen(true)}
                            >
                                <Menu size={22} />
                            </IconButton>
                        )}
                        <span className="header-page-icon" aria-hidden="true">
                            <CurrentIcon size={20} strokeWidth={1.8} />
                        </span>
                        <span className="header-page-copy">
                            <strong>{current?.label ?? "صفحه پیدا نشد"}</strong>
                            <small>{current?.description ?? "فضای مدیریت سالن"}</small>
                        </span>
                    </div>
                    <div className="header-account">
                        <span className="header-date">{today}</span>
                        <NotificationBell />
                        {user?.system_role === "super_admin" && (
                            <Tooltip title="رفتن به اپ مشتری">
                                <IconButton
                                    component={Link}
                                    to="/app"
                                    aria-label="رفتن به اپ مشتری"
                                    className="layout-switch"
                                >
                                    <ArrowLeftRight size={19} />
                                </IconButton>
                            </Tooltip>
                        )}
                        <Link
                            to="/panel/profile"
                            className="account-profile-link"
                            aria-label="مشاهده پروفایل من"
                        >
                            <span className="account-profile-copy">
                                <strong>{user?.name}</strong>
                                <small>{accountRole}</small>
                            </span>
                            <Avatar
                                src={user?.image_url ?? defaultAvatarUrl}
                                alt={user?.name}
                                className="header-avatar"
                            />
                            <ChevronDown
                                size={15}
                                className="account-profile-chevron"
                                aria-hidden="true"
                            />
                        </Link>
                    </div>
                </header>
                <main id="main-content" tabIndex={-1} className="admin-main">
                    <Outlet />
                </main>
                <footer className="admin-footer">
                    <span>آرا؛ همراه روزهای زیبای کاری</span>
                    <span>version 0.0.1</span>
                </footer>
            </div>
        </div>
    );
}
