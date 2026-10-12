import { Button, Paper, Typography } from "@mui/material";
import {
    ArrowLeft,
    CalendarDays,
    Scissors,
    UsersRound,
    Flower2,
} from "lucide-react";
import { Link } from "react-router";

const shortcuts = [
    {
        title: "مشتریان شما",
        description: "شروع یک ارتباط ماندگار",
        path: "/panel/customers",
        icon: UsersRound,
    },
    {
        title: "سرویس‌های سالن",
        description: "زیبایی، با امضای شما",
        path: "/panel/services",
        icon: Scissors,
    },
    {
        title: "تقویم نوبت‌ها",
        description: "هر قرار، در زمان خودش",
        path: "/panel/appointments",
        icon: CalendarDays,
    },
];

export default function Dashboard() {
    return (
        <section>
            <div className="page-heading">
                <span className="eyebrow">یک شروع زیبا</span>
                <Typography variant="h2" component="h1">
                    به فضای کاری آرا خوش آمدید
                </Typography>
                <Typography color="text.secondary">
                    همه‌چیز برای روزهای منظم‌تر سالن، از اینجا شروع می‌شود.
                </Typography>
            </div>
            <Paper className="welcome-banner">
                <div>
                    <span className="eyebrow">برای شما و کسب‌وکارتان</span>
                    <Typography variant="h2" component="h2">
                        شما زیبایی خلق کنید،
                        <br />
                        نظمش را به آرا بسپارید.
                    </Typography>
                    <Typography color="text.secondary">
                        فضایی یکپارچه برای تیم، سرویس‌ها و قرارهای سالن شما.
                    </Typography>
                    <Button component={Link} to="/app" variant="contained">
                        دیدن فضای مشتری <ArrowLeft size={18} />
                    </Button>
                </div>
                <div className="botanical-art" aria-hidden="true">
                    <Flower2 strokeWidth={0.65} />
                    <span>ARA BEAUTY STUDIO</span>
                </div>
            </Paper>
            <div className="section-heading">
                <Typography variant="h3" component="h2">
                    دسترسی‌های سریع
                </Typography>
                <span>میز کار، به سبک شما</span>
            </div>
            <div className="shortcut-grid">
                {shortcuts.map(({ title, description, path, icon: Icon }) => (
                    <Paper
                        key={path}
                        component={Link}
                        to={path}
                        variant="outlined"
                        className="shortcut-card"
                    >
                        <span className="shortcut-icon">
                            <Icon size={25} strokeWidth={1.5} />
                        </span>
                        <Typography variant="h3" component="h3">
                            {title}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                            {description}
                        </Typography>
                        <ArrowLeft size={18} className="shortcut-arrow" />
                    </Paper>
                ))}
            </div>
            <Paper variant="outlined" className="dashboard-empty">
                <span className="empty-icon">
                    <CalendarDays size={30} strokeWidth={1.4} />
                </span>
                <div>
                    <Typography variant="h3" component="h2">
                        داشبورد شما در حال شکل‌گیری است
                    </Typography>
                    <Typography color="text.secondary" variant="body2">
                        این بخش در فاز بعد پیاده‌سازی می‌شود. آمار واقعی و
                        نوبت‌ها پس از اتصال اطلاعات سالن اینجا نمایش داده
                        می‌شوند.
                    </Typography>
                </div>
                <span className="tiny-tag">پیش‌نمایش</span>
            </Paper>
        </section>
    );
}
