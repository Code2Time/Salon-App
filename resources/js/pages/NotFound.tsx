import { Button, Typography } from "@mui/material";
import { Link, useLocation } from "react-router";

export default function NotFound() {
    const { pathname } = useLocation();
    const target = pathname.startsWith("/panel/")
        ? "/panel"
        : pathname.startsWith("/app/")
          ? "/app"
          : "/";
    return (
        <section className="not-found">
            <span className="error-number">۴۰۴</span>
            <Typography component="h1" variant="h2">
                این صفحه پیدا نشد
            </Typography>
            <Typography color="text.secondary">
                شاید آدرس تغییر کرده باشد؛ از صفحهٔ اصلی ادامه دهید.
            </Typography>
            <Button component={Link} to={target} variant="contained">
                بازگشت به صفحهٔ اصلی
            </Button>
        </section>
    );
}
