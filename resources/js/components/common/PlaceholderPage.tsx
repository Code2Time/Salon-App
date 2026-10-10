import { Paper, Typography } from "@mui/material";
import { Construction } from "lucide-react";
import type { NavigationItem } from "../../routes/navigation";

export default function PlaceholderPage({ item }: { item: NavigationItem }) {
    const Icon = item.icon;
    return (
        <section>
            <div className="page-heading">
                <span className="eyebrow">فضای شما در آرا</span>
                <Typography variant="h2" component="h1">
                    {item.label}
                </Typography>
                <Typography color="text.secondary">
                    {item.description}
                </Typography>
            </div>
            <Paper variant="outlined" className="placeholder-card">
                <span className="empty-icon">
                    <Icon size={34} strokeWidth={1.4} />
                </span>
                <Typography variant="h3" component="h2">
                    اینجا برای {item.label} آماده می‌شود
                </Typography>
                <Typography color="text.secondary">
                    این بخش در فاز بعد پیاده‌سازی می‌شود.
                </Typography>
                <span className="coming-soon">
                    <Construction size={15} /> در حال آماده‌سازی
                </span>
            </Paper>
        </section>
    );
}
