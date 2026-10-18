import { Alert, Button, Chip, CircularProgress, Paper, Typography } from "@mui/material";
import { CalendarDays, CheckCircle2, Clock3, Scissors } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { useSearchParams } from "react-router";
import { toast } from "react-toastify";
import { cancelMyAppointment, fetchMyAppointments } from "../../services/api/appointments";
import { readableApiError } from "../../services/api/client";
import type { Appointment } from "../../types/appointment";

const labels: Record<Appointment["status"], string> = { confirmed: "تأییدشده", completed: "انجام‌شده", cancelled: "لغوشده", no_show: "عدم حضور", pending: "در انتظار", rejected: "ردشده", expired: "باطل‌شده" };
const persianDate = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long", year: "numeric" });
const faDigits = (value: string) => value.replace(/\d/g, (digit) => "۰۱۲۳۴۵۶۷۸۹"[Number(digit)] ?? digit);

function appointmentDate(value: string): string {
    const match = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2})$/.exec(value);
    if (!match) return value;
    return `${persianDate.format(new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])))}، ساعت ${faDigits(match[4] ?? "")}`;
}

export default function CustomerAppointments() {
    const [searchParams] = useSearchParams();
    const createdId = Number(searchParams.get("created"));
    const [items, setItems] = useState<Appointment[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            setItems((await fetchMyAppointments()).data);
            setError(null);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { void load(); }, [load]);

    async function cancel(id: number) {
        try {
            await cancelMyAppointment(id, "لغو توسط مشتری");
            toast.success("نوبت لغو شد.");
            await load();
        } catch (caught) {
            toast.error(readableApiError(caught));
        }
    }

    return <section className="customer-appointments-page">
        <div className="page-heading"><span className="eyebrow">قرارهای زیبایی من</span><Typography variant="h2" component="h1">نوبت‌های من</Typography><Typography color="text.secondary">زمان و جزئیات نوبت‌های ثبت‌شده را اینجا دنبال کنید.</Typography></div>
        {createdId > 0 && <Alert severity="success" icon={<CheckCircle2 />}>نوبت شما با موفقیت ثبت و تأیید شد. جزئیات آن در ابتدای فهرست نمایش داده می‌شود.</Alert>}
        {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
        {loading ? <div className="schedule-loading"><CircularProgress /></div> : items.length ? <div className="customer-appointment-list">{items.map((item) => <Paper key={item.id} variant="outlined" className={`customer-appointment-card${item.id === createdId ? " newly-created" : ""}`}>
            <div><span className="customer-appointment-icon"><CalendarDays /></span><span><strong>{appointmentDate(item.starts_at)}</strong><small>{item.items.map((part) => part.service_name).join("، ")}</small></span><Chip label={labels[item.status]} color={item.status === "confirmed" ? "success" : "default"} variant="outlined" /></div>
            <div>{item.items.map((part) => <span key={part.id}><Scissors size={14} /> {part.service_name} با {part.employee_name}<small><Clock3 size={12} /> {faDigits(part.starts_at.slice(11))}</small></span>)}</div>
            {item.status === "confirmed" && <Button color="error" onClick={() => void cancel(item.id)}>لغو نوبت</Button>}
        </Paper>)}</div> : <Paper className="users-empty"><CalendarDays /><Typography>هنوز نوبتی ندارید.</Typography></Paper>}
    </section>;
}
