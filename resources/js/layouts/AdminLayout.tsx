import { useState } from "react";
import { Drawer, IconButton, useMediaQuery, useTheme } from "@mui/material";
import { ArrowUpLeft, Menu, X, Sprout } from "lucide-react";
import { Link, Outlet, useLocation } from "react-router";
import Brand from "../components/layout/Brand";
import Navigation from "../components/layout/Navigation";
import { panelNavigation } from "../routes/navigation";

export default function AdminLayout() {
    const [menuOpen, setMenuOpen] = useState(false);
    const desktop = useMediaQuery(useTheme().breakpoints.up("lg"));
    const { pathname } = useLocation();
    const current = panelNavigation.find((item) => item.path === pathname);
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
                items={panelNavigation}
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
                <Link to="/app" className="text-link">
                    پیش‌نمایش اپ مشتری <ArrowUpLeft size={18} />
                </Link>
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
                    <span className="preview-badge">
                        <span className="status-dot" /> پیش‌نمایش پنل
                    </span>
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
