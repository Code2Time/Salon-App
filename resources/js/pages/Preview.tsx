import { Button, Paper, Typography } from "@mui/material";
import { ArrowLeft, LayoutDashboard, Smartphone } from "lucide-react";
import { Link } from "react-router";
import Brand from "../components/layout/Brand";

export default function Preview() {
    return (
        <main className="preview-page">
            <Brand />
            <span className="eyebrow">یک تجربه، دو فضای متفاوت</span>
            <Typography variant="h1" component="h1">
                به آرا خوش آمدید
            </Typography>
            <Typography color="text.secondary">
                فضای موردنظرتان را برای پیش‌نمایش انتخاب کنید.
            </Typography>
            <div className="preview-grid">
                {[
                    {
                        title: "پنل مدیریت",
                        text: "فضای کار شما برای روزهای منظم‌تر سالن",
                        path: "/panel",
                        icon: LayoutDashboard,
                    },
                    {
                        title: "اپلیکیشن مشتری",
                        text: "تجربه‌ای ساده برای قرارهای زیبایی",
                        path: "/app",
                        icon: Smartphone,
                    },
                ].map(({ title, text, path, icon: Icon }) => (
                    <Paper
                        variant="outlined"
                        className="preview-card"
                        key={path}
                    >
                        <span className="empty-icon">
                            <Icon size={30} />
                        </span>
                        <Typography variant="h2" component="h2">
                            {title}
                        </Typography>
                        <Typography color="text.secondary">{text}</Typography>
                        <Button component={Link} to={path} variant="contained">
                            مشاهدهٔ پیش‌نمایش <ArrowLeft size={18} />
                        </Button>
                    </Paper>
                ))}
            </div>
            <p className="preview-notice">
                این صفحه برای پیش‌نمایش رابط کاربری است و ورود به حساب کاربری
                محسوب نمی‌شود.
            </p>
        </main>
    );
}
