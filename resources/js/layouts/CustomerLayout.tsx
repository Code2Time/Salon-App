import { Link, Outlet, useLocation } from "react-router";
import { ArrowUpLeft } from "lucide-react";
import Brand from "../components/layout/Brand";
import Navigation from "../components/layout/Navigation";
import { customerNavigation } from "../routes/navigation";

export default function CustomerLayout() {
    const { pathname } = useLocation();
    const current = customerNavigation.find((item) => item.path === pathname);
    return (
        <div className="customer-shell">
            <a className="skip-link" href="#main-content">
                رفتن به محتوا
            </a>
            <header className="customer-header">
                <div className="customer-header-inner">
                    <Brand customer />
                    <Link to="/" className="preview-return">
                        پیش‌نمایش‌ها <ArrowUpLeft size={17} />
                    </Link>
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
                aria-label={current?.label ?? "صفحه پیدا نشد"}
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
        </div>
    );
}
