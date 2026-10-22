import { InstallHint } from "../../pwa/InstallHint";
import { useSalon } from "../../salon/SalonContext";
import "../../../css/salon-settings.css";
import { Button, Paper, Typography } from "@mui/material";
import {
    ArrowLeft,
    Phone,
    MapPin,
    Camera,
    Send,
    Globe,
    MessageCircle,
    Flower2,
    Heart,
    Sparkles,
    CalendarDays,
} from "lucide-react";
import { Link } from "react-router";

export default function Home() {
    const { salon } = useSalon();
    const socialLinks = [{ url: salon?.instagram_url, label: "اینستاگرام", Icon: Camera }, { url: salon?.telegram_url, label: "تلگرام", Icon: Send }, { url: salon?.whatsapp_url, label: "واتساپ", Icon: MessageCircle }, { url: salon?.website_url, label: "وب‌سایت", Icon: Globe }].filter(link => link.url);
    return (
        <section>
            <div className="customer-greeting">
                <span className="eyebrow">به {salon?.name ?? "سالن"} خوش آمدید</span>
                <Typography component="h1" variant="h2">
                    امروز، کمی برای خودتان.
                </Typography>
            </div>
            <InstallHint />
            <Paper className="customer-hero">
                <div className="hero-copy">
                    <span className="eyebrow">لحظه‌ای برای درخشیدن</span>
                    <Typography variant="h1" component="h2">
                        زیبایی از حالِ
                        <br />
                        خوب شما شروع می‌شود.
                    </Typography>
                    <Typography className="salon-public-description">
                        {salon?.description || "زمانی برای رسیدگی به زیبایی و حال خوب شما."}
                    </Typography>
                    <Button
                        component={Link}
                        to="/app/services"
                        variant="contained"
                    >
                        مشاهده خدمات <ArrowLeft size={19} />
                    </Button>
                    <span className="hero-caption">
                        {salon?.name}
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
                            خدمات سالن
                        </Typography>
                        <Typography color="text.secondary" variant="body2">
                            خدمت مناسب خودتان را پیدا کنید
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
            {(salon?.phone || salon?.address || socialLinks.length > 0) && <Paper variant="outlined" className="salon-public-contact"><Typography component="h2" variant="h3">ارتباط با {salon?.name}</Typography>{salon?.address && <p><MapPin size={18} /><span>{salon.address}</span></p>}{salon?.phone && <div className="salon-public-links"><a href={`tel:${salon.phone.replace(/[()\s-]/g, "")}`}><Phone size={17} /><bdi>{salon.phone}</bdi></a></div>}<div className="salon-public-links">{socialLinks.map(({url, label, Icon}) => <a key={label} href={url!} target="_blank" rel="noopener noreferrer"><Icon size={17} />{label}</a>)}</div></Paper>}

        </section>
    );
}
