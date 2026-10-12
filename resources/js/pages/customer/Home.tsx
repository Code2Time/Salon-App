import { Button, Paper, Typography } from "@mui/material";
import {
    ArrowLeft,
    Flower2,
    Heart,
    Sparkles,
    CalendarDays,
} from "lucide-react";
import { Link } from "react-router";

export default function Home() {
    return (
        <section>
            <div className="customer-greeting">
                <span className="eyebrow">به آرا خوش آمدید</span>
                <Typography component="h1" variant="h2">
                    امروز، کمی برای خودتان.
                </Typography>
            </div>
            <Paper className="customer-hero">
                <div className="hero-copy">
                    <span className="eyebrow">لحظه‌ای برای درخشیدن</span>
                    <Typography variant="h1" component="h2">
                        زیبایی از حالِ
                        <br />
                        خوب شما شروع می‌شود.
                    </Typography>
                    <Typography>
                        یک مکث دلنشین در میان روزهای شلوغ؛
                        <br />
                        این زمان، فقط برای شماست.
                    </Typography>
                    <Button
                        component={Link}
                        to="/app/booking"
                        variant="contained"
                    >
                        رزرو نوبت <ArrowLeft size={19} />
                    </Button>
                    <span className="hero-caption">
                        معرفی نمایشی سالن زیبایی آرا
                    </span>
                </div>
                <div className="customer-art" aria-hidden="true">
                    <div className="art-arch">
                        <Flower2 strokeWidth={0.6} />
                        <span>
                            a little time
                            <br />
                            for yourself
                        </span>
                    </div>
                    <span className="art-sparkle">✧</span>
                </div>
            </Paper>
            <div className="customer-values">
                <span>
                    <Heart size={18} /> توجه به شما
                </span>
                <span>
                    <Sparkles size={18} /> حس خوب زیبایی
                </span>
                <span>
                    <CalendarDays size={18} /> احترام به زمان
                </span>
            </div>
            <div className="section-heading">
                <Typography variant="h3" component="h2">
                    مسیر زیبایی شما
                </Typography>
                <span>ساده و دلنشین</span>
            </div>
            <div className="customer-links">
                <Paper
                    component={Link}
                    to="/app/services"
                    variant="outlined"
                    className="customer-link-card"
                >
                    <span className="shortcut-icon">
                        <Sparkles size={26} />
                    </span>
                    <div>
                        <Typography variant="h3" component="h3">
                            سرویس‌های سالن
                        </Typography>
                        <Typography color="text.secondary" variant="body2">
                            سرویس مناسب خودتان را پیدا کنید
                        </Typography>
                    </div>
                    <ArrowLeft size={20} />
                </Paper>
                <Paper
                    component={Link}
                    to="/app/appointments"
                    variant="outlined"
                    className="customer-link-card"
                >
                    <span className="shortcut-icon">
                        <CalendarDays size={26} />
                    </span>
                    <div>
                        <Typography variant="h3" component="h3">
                            قرارهای زیبایی من
                        </Typography>
                        <Typography color="text.secondary" variant="body2">
                            جای نوبت‌های آیندهٔ شما
                        </Typography>
                    </div>
                    <ArrowLeft size={20} />
                </Paper>
            </div>
            <p className="preview-notice">
                این یک پیش‌نمایش از فضای مشتری است. اطلاعات سالن نمایشی است و
                رزرو واقعی هنوز فعال نیست.
            </p>
        </section>
    );
}
