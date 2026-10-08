import { useState } from "react";
import { Avatar, Drawer, IconButton, Tooltip, useMediaQuery, useTheme } from "@mui/material";
import { ArrowLeftRight, ArrowUpLeft, Menu, X, Sprout } from "lucide-react";
import { Link, Outlet, useLocation } from "react-router";
import Brand from "../components/layout/Brand";
import Navigation from "../components/layout/Navigation";
import { panelNavigation } from "../routes/navigation";
import { useAuth } from "../auth/AuthContext";
import { useNavigate } from "react-router";
import { Button } from "@mui/material";
import { toast } from "react-toastify";
import { readableApiError } from "../services/api/client";

export default function AdminLayout() {
    const [menuOpen, setMenuOpen] = useState(false);
    const desktop = useMediaQuery(useTheme().breakpoints.up("lg"));
    const { pathname } = useLocation();
    const navigate = useNavigate();
    const { user, logout } = useAuth();
    const membershipRole = user?.memberships[0]?.role;
    const visibleNavigation = panelNavigation.filter((item) =>
        user?.system_role === "super_admin" || !item.roles || (membershipRole && item.roles.includes(membershipRole)),
    );
    const current = visibleNavigation.find((item) => item.path === pathname);
    async function handleLogout() {
        try {
            await logout();
            navigate("/login");
        } catch (error) {
            toast.error(readableApiError(error));
        }
    }
    const sidebar = (
        <div className="sidebar-content">
            <div className="sidebar-brand">
                <Brand />
                {!desktop && (
                    <IconButton
                        aria-label="بستن منو"
                        onClick={() => setMenuOpen(false)}
                    >
                        <X size={20} />
                    </IconButton>
                )}
            </div>
            <div className="workspace-label">
                <span className="status-dot" /> فضای مدیریت سالن{" "}
                <span className="tiny-tag">نمایشی</span>
            </div>
            <p className="nav-caption">میز کار شما</p>
            <Navigation
                items={visibleNavigation}
                label="منوی پنل مدیریت"
                className="panel-navigation"
                onNavigate={() => setMenuOpen(false)}
            />
            <div className="sidebar-bottom">
                <div className="sidebar-note">
                    <Sprout size={24} />
                    <strong>فضایی برای رشد شما</strong>
                    <p>با نظم بیشتر، برای زیبایی وقت بگذارید.</p>
                </div>
                <Button color="inherit" onClick={handleLogout}>
                    خروج از حساب <ArrowUpLeft size={18} />
                </Button>
            </div>
        </div>
    );

    return (
        <div className="admin-shell">
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
                            sx: { width: "min(290px, 88vw)" },
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
                        <span>
                            پنل مدیریت{" "}
                            <span className="breadcrumb-divider">/</span>{" "}
                            <strong>{current?.label ?? "صفحه پیدا نشد"}</strong>
                        </span>
                    </div>
                    <div className="header-account">
                        {user?.system_role === "super_admin" && (
                            <Tooltip title="رفتن به اپ مشتری">
                                <IconButton component={Link} to="/app" aria-label="رفتن به اپ مشتری" className="layout-switch">
                                    <ArrowLeftRight size={19} />
                                </IconButton>
                            </Tooltip>
                        )}
                        <span className="preview-badge user-badge">
                            <Avatar src={user?.image_url ?? undefined} alt={user?.name} className="header-avatar">
                                {user?.name?.trim().charAt(0)}
                            </Avatar>
                            <span className="user-name">{user?.name}</span>
                        </span>
                    </div>
                </header>
                <main id="main-content" tabIndex={-1} className="admin-main">
                    <Outlet />
                </main>
                <footer className="admin-footer">
                    <span>آرا؛ همراه روزهای زیبای کاری</span>
                    <span>نسخهٔ اولیهٔ رابط کاربری</span>
                </footer>
            </div>
        </div>
    );
}
