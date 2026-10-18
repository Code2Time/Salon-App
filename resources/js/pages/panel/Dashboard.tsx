import { Alert, Button, Chip, CircularProgress, Paper, Typography } from "@mui/material";
import { ArrowLeft, Ban, CalendarCheck2, CalendarDays, CalendarPlus, CheckCircle2, Clock3, Scissors, Sparkles, UserRoundCheck, UsersRound, XCircle } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";
import { Link } from "react-router";
import { useAuth } from "../../auth/AuthContext";
import { readableApiError } from "../../services/api/client";
import { fetchDashboard } from "../../services/api/dashboard";
import type { DashboardData } from "../../types/dashboard";

const number = new Intl.NumberFormat("fa-IR");

function appointmentDate(value: string): string {
    return new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long" })
        .format(new Date(`${value.replace(" ", "T")}:00`));
}

function todayLabel(): string {
    return new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long", year: "numeric" }).format(new Date());
}

export default function Dashboard() {
    const { user } = useAuth();
    const [dashboard, setDashboard] = useState<DashboardData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            setDashboard(await fetchDashboard());
            setError(null);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { void load(); }, [load]);

    const shortcuts = useMemo(() => {
        const membership = user?.memberships[0];
        const owner = user?.system_role === "super_admin" || membership?.role === "salon_owner";
        const permissions = membership?.permissions ?? [];
        return [
            { title: "ثبت و مدیریت نوبت", description: "تقویم قرارهای سالن", path: "/panel/appointments", icon: CalendarDays, visible: owner || permissions.includes("appointments") },
            { title: "مشتریان سالن", description: "مدیریت اطلاعات مشتریان", path: "/panel/customers", icon: UsersRound, visible: owner },
            { title: "سرویس‌های سالن", description: "ویرایش خدمات و ارائه‌دهندگان", path: "/panel/services", icon: Scissors, visible: owner || permissions.includes("services") },
        ].filter((item) => item.visible);
    }, [user]);

    if (loading) return <div className="dashboard-loading"><CircularProgress /><span>در حال آماده‌سازی نمای امروز سالن…</span></div>;
    if (error || !dashboard) return <section><Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error ?? "اطلاعات داشبورد دریافت نشد."}</Alert></section>;

    const { summary } = dashboard;
    const metrics = [
        { label: "نوبت‌های امروز", value: summary.today_appointments, hint: `${number.format(summary.today_confirmed)} نوبت تأییدشده`, icon: CalendarCheck2, tone: "rose" },
        { label: "مشتریان فعال", value: summary.active_customers, hint: "عضو فعال این سالن", icon: UsersRound, tone: "mauve" },
        { label: "کارکنان فعال", value: summary.active_employees, hint: `${number.format(dashboard.employees.filter((item) => item.schedule_status === "present").length)} نفر حاضر امروز`, icon: UserRoundCheck, tone: "gold" },
        { label: "سرویس‌های فعال", value: summary.active_services, hint: "قابل ارائه به مشتری", icon: Scissors, tone: "green" },
    ];

    return (
        <section className="dashboard-page">
            <Paper variant="outlined" className="dashboard-hero">
                <div className="dashboard-hero-copy"><span className="eyebrow"><Sparkles size={13} /> نمای زنده سالن</span><Typography variant="h2" component="h1">همه‌چیز برای یک روز منظم آماده است</Typography><Typography color="text.secondary">برنامه امروز، وضعیت تیم و نزدیک‌ترین قرارها آماده مرور هستند.</Typography><span className="dashboard-today"><CalendarDays size={15} /> {todayLabel()}</span></div>
                <div className="dashboard-hero-visual" aria-hidden="true"><span /><span /><CalendarCheck2 /></div>
                <Button component={Link} to="/panel/appointments" variant="contained"><CalendarPlus size={18} /> مدیریت نوبت‌ها</Button>
            </Paper>

            <div className="dashboard-section-title"><div><span>خلاصه امروز</span><Typography variant="h3">نبض سالن در یک نگاه</Typography></div><small>اطلاعات به‌صورت زنده از سیستم خوانده می‌شوند</small></div>

            <div className="dashboard-metrics">{metrics.map(({ label, value, hint, icon: Icon, tone }) => (
                <Paper variant="outlined" className={`dashboard-metric ${tone}`} key={label}><span className="dashboard-metric-icon"><Icon size={22} /></span><div><small>{label}</small><strong>{number.format(value)}</strong><span>{hint}</span></div></Paper>
            ))}</div>

            <div className="dashboard-content-grid">
                <Paper variant="outlined" className="dashboard-panel dashboard-appointments-panel">
                    <div className="dashboard-panel-heading"><div><Typography variant="h3">نوبت‌های پیش رو</Typography><span>پنج قرار نزدیک سالن</span></div><Button component={Link} to="/panel/appointments" size="small" endIcon={<ArrowLeft size={15} />}>مشاهده همه</Button></div>
                    {dashboard.upcoming_appointments.length ? <div className="dashboard-appointment-list">{dashboard.upcoming_appointments.map((appointment) => (
                        <div className="dashboard-appointment" key={appointment.id}><span className="dashboard-time"><strong>{appointment.starts_at.slice(11)}</strong><small>{appointmentDate(appointment.starts_at)}</small></span><span className="dashboard-appointment-copy"><strong>{appointment.customer.name}</strong><small>{appointment.services.join("، ")}</small><em>{appointment.employees.join("، ")}</em></span><Chip size="small" variant="outlined" color={appointment.status === "confirmed" ? "success" : "warning"} label={appointment.status === "confirmed" ? "تأییدشده" : "در انتظار"} /></div>
                    ))}</div> : <div className="dashboard-inline-empty"><CalendarDays size={28} /><span>نوبت آینده‌ای ثبت نشده است.</span></div>}
                </Paper>

                <div className="dashboard-side-column">
                    <Paper variant="outlined" className="dashboard-panel dashboard-today-status"><div className="dashboard-panel-heading"><div><Typography variant="h3">وضعیت امروز</Typography><span>خلاصه نتیجه نوبت‌ها</span></div><span className="dashboard-live-badge">زنده</span></div><div className="dashboard-status-row confirmed"><span><Clock3 size={17} /> تأییدشده</span><strong>{number.format(summary.today_confirmed)}</strong></div><div className="dashboard-status-row completed"><span><CheckCircle2 size={17} /> انجام‌شده</span><strong>{number.format(summary.today_completed)}</strong></div><div className="dashboard-status-row cancelled"><span><XCircle size={17} /> لغوشده</span><strong>{number.format(summary.today_cancelled)}</strong></div><div className="dashboard-status-row expired"><span><Ban size={17} /> باطل‌شده</span><strong>{number.format(summary.today_expired)}</strong></div></Paper>
                    <Paper variant="outlined" className="dashboard-panel"><div className="dashboard-panel-heading"><div><Typography variant="h3">حضور تیم امروز</Typography><span>بر اساس برنامه ثبت‌شده</span></div></div>{dashboard.employees.length ? <div className="dashboard-team-list">{dashboard.employees.map((employee) => (
                        <div key={employee.membership_id}><span className="dashboard-avatar">{employee.name.trim().slice(0, 1)}</span><span><strong>{employee.name}</strong><small>{employee.periods.length ? employee.periods.map((period) => `${period.starts_at} تا ${period.ends_at}`).join("، ") : employee.schedule_status === "absent" ? "امروز حضور ندارد" : "برنامه امروز ثبت نشده"}</small></span><em className={`team-status-label ${employee.schedule_status}`}>{employee.schedule_status === "present" ? "حاضر" : employee.schedule_status === "absent" ? "غایب" : "ثبت‌نشده"}</em></div>
                    ))}</div> : <div className="dashboard-inline-empty"><UsersRound size={26} /><span>کارمند فعالی وجود ندارد.</span></div>}</Paper>
                </div>
            </div>

            {shortcuts.length > 0 && <><div className="section-heading"><Typography variant="h3" component="h2">دسترسی‌های سریع</Typography><span>کارهای پرتکرار سالن</span></div><div className="shortcut-grid">{shortcuts.map(({ title, description, path, icon: Icon }) => (
                <Paper key={path} component={Link} to={path} variant="outlined" className="shortcut-card"><span className="shortcut-icon"><Icon size={25} strokeWidth={1.5} /></span><Typography variant="h3" component="h3">{title}</Typography><Typography variant="body2" color="text.secondary">{description}</Typography><ArrowLeft size={18} className="shortcut-arrow" /></Paper>
            ))}</div></>}
        </section>
    );
}
