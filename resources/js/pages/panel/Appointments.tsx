import { Alert, Button, Chip, CircularProgress, Dialog, DialogActions, DialogContent, DialogTitle, FormControl, IconButton, InputLabel, MenuItem, Pagination, Paper, Select, Typography } from "@mui/material";
import { Banknote, CalendarClock, CalendarDays, CalendarPlus, Check, Clock3, Pencil, Plus, RotateCcw, Scissors, SlidersHorizontal, Smartphone, Store, Trash2, UserRound, UserX, X } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";
import { toast } from "react-toastify";
import { useAuth } from "../../auth/AuthContext";
import JalaliDatePicker from "../../components/forms/JalaliDatePicker";
import { createAppointment, deleteAppointment, fetchAppointmentOptions, fetchAvailability, fetchPanelAppointments, rescheduleAppointment, updateAppointmentStatus } from "../../services/api/appointments";
import type { AppointmentOptions } from "../../services/api/appointments";
import { readableApiError } from "../../services/api/client";
import type { Appointment, BookingItemInput } from "../../types/appointment";

const tomorrow = () => {
    const value = new Date();
    value.setDate(value.getDate() + 1);
    return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, "0")}-${String(value.getDate()).padStart(2, "0")}`;
};
const labels: Record<Appointment["status"], string> = { confirmed: "تأییدشده", completed: "انجام‌شده", cancelled: "لغوشده", no_show: "عدم حضور", pending: "در انتظار", rejected: "ردشده", expired: "باطل‌شده" };
const statusFilters = [
    { value: "", label: "همه" },
    { value: "confirmed", label: "تأییدشده" },
    { value: "completed", label: "انجام‌شده" },
    { value: "cancelled", label: "لغوشده" },
    { value: "no_show", label: "عدم حضور" },
    { value: "expired", label: "باطل‌شده" },
];
const number = new Intl.NumberFormat("fa-IR");
const money = new Intl.NumberFormat("fa-IR");

function dateLabel(value: string): string {
    return new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long", year: "numeric" })
        .format(new Date(`${value.slice(0, 10)}T12:00:00`));
}

export default function Appointments() {
    const { user } = useAuth();
    const canDelete = user?.system_role === "super_admin" || user?.memberships.some((membership) => membership.role === "salon_owner");
    const [appointments, setAppointments] = useState<Appointment[]>([]);
    const [options, setOptions] = useState<AppointmentOptions>({ customers: [], services: [] });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [open, setOpen] = useState(false);
    const [customerId, setCustomerId] = useState(0);
    const [date, setDate] = useState(tomorrow);
    const [items, setItems] = useState<BookingItemInput[]>([]);
    const [starts, setStarts] = useState<string[]>([]);
    const [start, setStart] = useState("");
    const [saving, setSaving] = useState(false);
    const [editing, setEditing] = useState<Appointment | null>(null);
    const [deleting, setDeleting] = useState<Appointment | null>(null);
    const [deleteLoading, setDeleteLoading] = useState(false);
    const [filterDate, setFilterDate] = useState("");
    const [filterStatus, setFilterStatus] = useState("");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const [result, values] = await Promise.all([
                fetchPanelAppointments({ ...(filterDate ? { date: filterDate } : {}), ...(filterStatus ? { status: filterStatus } : {}), page }),
                fetchAppointmentOptions(),
            ]);
            setAppointments(result.data);
            setLastPage(result.meta.last_page);
            setTotal(result.meta.total);
            setOptions(values);
            setError(null);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, [filterDate, filterStatus, page]);

    useEffect(() => { void load(); }, [load]);

    const selectedTotal = useMemo(() => items.reduce((sum, item) => sum + (options.services.find((service) => service.id === item.service_id)?.price ?? 0), 0), [items, options.services]);
    const selectedDuration = useMemo(() => items.reduce((sum, item) => sum + (options.services.find((service) => service.id === item.service_id)?.duration_minutes ?? 0), 0), [items, options.services]);

    function openCreate() {
        setEditing(null);
        setCustomerId(options.customers[0]?.id ?? 0);
        setDate(tomorrow());
        setItems([]);
        setStarts([]);
        setStart("");
        setOpen(true);
    }
    function addItem() {
        const service = options.services[0];
        if (service) setItems((current) => [...current, { service_id: service.id, employee_membership_id: service.providers[0]?.membership_id ?? 0 }]);
    }
    function changeService(index: number, id: number) {
        const service = options.services.find((value) => value.id === id);
        setItems((current) => current.map((item, currentIndex) => currentIndex === index ? { service_id: id, employee_membership_id: service?.providers[0]?.membership_id ?? 0 } : item));
        setStarts([]);
        setStart("");
    }
    async function check() {
        setStart("");
        try { setStarts(await fetchAvailability(date, items, true)); } catch (caught) { toast.error(readableApiError(caught)); }
    }
    async function save() {
        setSaving(true);
        try {
            if (editing) {
                await rescheduleAppointment(editing.id, { date, starts_at: start, items });
                toast.success("نوبت جابه‌جا شد و مشتری اعلان دریافت کرد.");
            } else {
                await createAppointment({ customer_id: customerId, date, starts_at: start, items }, true);
                toast.success("نوبت حضوری ثبت شد.");
            }
            setOpen(false);
            setEditing(null);
            if (page !== 1) setPage(1); else await load();
        } catch (caught) { toast.error(readableApiError(caught)); } finally { setSaving(false); }
    }
    function edit(item: Appointment) {
        setEditing(item);
        setCustomerId(item.customer?.id ?? 0);
        setDate(item.starts_at.slice(0, 10));
        setStart(item.starts_at.slice(11, 16));
        setItems(item.items.flatMap((part) => part.service_id ? [{ service_id: part.service_id, employee_membership_id: part.employee_membership_id }] : []));
        setStarts([item.starts_at.slice(11, 16)]);
        setOpen(true);
    }
    async function status(id: number, value: "completed" | "no_show" | "cancelled") {
        try { await updateAppointmentStatus(id, value, value === "cancelled" ? "لغو توسط مدیر سالن" : undefined); toast.success("وضعیت نوبت تغییر کرد."); await load(); } catch (caught) { toast.error(readableApiError(caught)); }
    }
    async function remove() {
        if (!deleting) return;
        setDeleteLoading(true);
        try { await deleteAppointment(deleting.id); toast.success("نوبت حذف شد و زمان کارمند آزاد شد."); setDeleting(null); await load(); } catch (caught) { toast.error(readableApiError(caught)); } finally { setDeleteLoading(false); }
    }
    function resetFilters() { setFilterDate(""); setFilterStatus(""); setPage(1); }

    return <section className="appointments-page">
        <Paper variant="outlined" className="appointments-hero">
            <div><span className="eyebrow">مرکز مدیریت قرارها</span><Typography variant="h2" component="h1">نوبت‌های سالن</Typography><Typography color="text.secondary">برنامه روزانه را مرور کنید و هر نوبت را از ثبت تا پایان مدیریت کنید.</Typography><span className="appointments-total"><CalendarClock size={15} /> {number.format(total)} نوبت در نتایج فعلی</span></div>
            <span className="appointments-hero-art" aria-hidden="true"><CalendarDays /></span>
            <Button variant="contained" onClick={openCreate}><CalendarPlus size={18} /> نوبت حضوری جدید</Button>
        </Paper>

        <Paper variant="outlined" className="appointments-filter-panel">
            <div className="appointments-filter-title"><span><SlidersHorizontal size={18} /></span><div><strong>فیلتر نوبت‌ها</strong><small>نمایش را بر اساس تاریخ یا وضعیت محدود کنید</small></div>{(filterDate || filterStatus) && <Button size="small" color="inherit" onClick={resetFilters}><RotateCcw size={14} /> پاک‌کردن</Button>}</div>
            <div className="appointments-filter-controls"><JalaliDatePicker label="تاریخ نوبت" value={filterDate} onChange={(value) => { setFilterDate(value); setPage(1); }} /><div className="appointments-status-filter" role="group" aria-label="فیلتر وضعیت">{statusFilters.map((item) => <button key={item.value} type="button" className={filterStatus === item.value ? "active" : ""} onClick={() => { setFilterStatus(item.value); setPage(1); }}>{item.label}</button>)}</div></div>
        </Paper>

        {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
        {loading ? <div className="appointments-loading"><CircularProgress /><span>در حال دریافت برنامه سالن…</span></div> : appointments.length ? <>
            <div className="panel-appointment-list">{appointments.map((item) => (
                <Paper variant="outlined" key={item.id} className={`panel-appointment-card status-${item.status}`}>
                    <div className="appointment-card-rail"><span className="appointment-card-time"><Clock3 size={16} /><strong>{item.starts_at.slice(11, 16)}</strong></span><small>تا {item.ends_at.slice(11, 16)}</small><i /></div>
                    <div className="appointment-card-main">
                        <div className="appointment-card-head">
                            <div className="appointment-customer"><span>{item.customer?.name.trim().slice(0, 1) || "م"}</span><div><strong>{item.customer?.name ?? "مشتری"}</strong><small><Smartphone size={12} /> {item.customer?.mobile ?? "بدون شماره"}</small></div></div>
                            <div className="appointment-card-meta"><Chip size="small" className={`appointment-status status-${item.status}`} label={labels[item.status]} /><span className="appointment-source">{item.source === "panel" ? <Store size={13} /> : <Smartphone size={13} />}{item.source === "panel" ? "ثبت سالن" : "رزرو مشتری"}</span></div>
                        </div>
                        <div className="appointment-date-line"><CalendarDays size={15} /><strong>{dateLabel(item.starts_at)}</strong><span>کد نوبت #{number.format(item.id)}</span></div>
                        <div className="appointment-service-list">{item.items.map((part) => (
                            <div key={part.id}><span className="appointment-service-icon"><Scissors size={17} /></span><span><strong>{part.service_name}</strong><small><UserRound size={12} /> {part.employee_name}</small></span><span className="appointment-service-facts"><small>{number.format(part.duration_minutes)} دقیقه</small><strong>{money.format(part.price)} <i>تومان</i></strong></span></div>
                        ))}</div>
                        <div className="appointment-card-footer">
                            <span className="appointment-total-price"><Banknote size={17} /><small>مبلغ کل</small><strong>{money.format(item.total_price)} <i>تومان</i></strong></span>
                            <div className="appointment-actions">{item.status === "confirmed" && <><Button variant="outlined" onClick={() => edit(item)}><Pencil size={15} /> جابه‌جایی</Button><Button className="appointment-complete" onClick={() => void status(item.id, "completed")}><Check size={15} /> انجام شد</Button><Button color="warning" onClick={() => void status(item.id, "no_show")}><UserX size={15} /> عدم حضور</Button><Button color="error" onClick={() => void status(item.id, "cancelled")}><X size={15} /> لغو</Button></>}{canDelete && <IconButton className="appointment-delete-button" color="error" aria-label={`حذف نوبت ${item.customer?.name ?? "مشتری"}`} onClick={() => setDeleting(item)}><Trash2 size={17} /></IconButton>}</div>
                        </div>
                    </div>
                </Paper>
            ))}</div>
            {lastPage > 1 && <div className="appointments-pagination"><Pagination count={lastPage} page={page} onChange={(_event, value) => setPage(value)} color="primary" shape="rounded" /></div>}
        </> : <Paper variant="outlined" className="appointments-empty"><span><CalendarPlus size={32} /></span><Typography variant="h3">نوبتی پیدا نشد</Typography><Typography color="text.secondary">فیلترها را تغییر دهید یا اولین نوبت حضوری را ثبت کنید.</Typography><div>{(filterDate || filterStatus) && <Button variant="outlined" onClick={resetFilters}>حذف فیلترها</Button>}<Button variant="contained" onClick={openCreate}>ثبت نوبت جدید</Button></div></Paper>}

        <Dialog open={open} onClose={() => { setOpen(false); setEditing(null); }} fullWidth maxWidth="md" slotProps={{ paper: { className: "panel-booking-modal" } }}>
            <DialogTitle className="panel-booking-title"><span><CalendarPlus size={22} /></span><div><strong>{editing ? "جابه‌جایی نوبت" : "ثبت نوبت حضوری"}</strong><small>{editing ? "زمان یا متخصص نوبت را با حفظ اطلاعات مشتری تغییر دهید." : "مشتری، سرویس و زمان مناسب را برای قرار جدید انتخاب کنید."}</small></div></DialogTitle>
            <DialogContent className="panel-booking-dialog">
                <div className="panel-booking-section"><span className="panel-booking-section-title">۱. مشتری و خدمات</span><FormControl disabled={Boolean(editing)}><InputLabel>مشتری</InputLabel><Select label="مشتری" value={customerId} onChange={(event) => setCustomerId(Number(event.target.value))}>{options.customers.map((customer) => <MenuItem key={customer.id} value={customer.id}>{customer.name} · {customer.mobile}</MenuItem>)}</Select></FormControl><div className="booking-items-heading"><div><strong>سرویس‌های نوبت</strong><small>{items.length ? `${number.format(items.length)} سرویس انتخاب شده` : "حداقل یک سرویس اضافه کنید"}</small></div><Button variant="outlined" onClick={addItem}><Plus size={16} /> افزودن سرویس</Button></div>{items.length ? items.map((item, index) => { const service = options.services.find((value) => value.id === item.service_id); return <div className="booking-item-row" key={`${index}-${item.service_id}`}><span>{number.format(index + 1)}</span><FormControl><InputLabel>سرویس</InputLabel><Select label="سرویس" value={item.service_id} onChange={(event) => changeService(index, Number(event.target.value))}>{options.services.map((value) => <MenuItem key={value.id} value={value.id}>{value.name}</MenuItem>)}</Select></FormControl><FormControl><InputLabel>متخصص</InputLabel><Select label="متخصص" value={item.employee_membership_id} onChange={(event) => { setItems((current) => current.map((value, currentIndex) => currentIndex === index ? { ...value, employee_membership_id: Number(event.target.value) } : value)); setStarts([]); setStart(""); }}>{service?.providers.map((provider) => <MenuItem key={provider.membership_id} value={provider.membership_id}>{provider.name}</MenuItem>)}</Select></FormControl><IconButton color="error" aria-label="حذف سرویس" onClick={() => { setItems((current) => current.filter((_value, currentIndex) => currentIndex !== index)); setStarts([]); setStart(""); }}><Trash2 size={18} /></IconButton></div>; }) : <div className="panel-booking-items-empty"><Scissors size={24} /><span>هنوز سرویسی به این نوبت اضافه نشده است.</span></div>}</div>
                <div className="panel-booking-section"><span className="panel-booking-section-title">۲. تاریخ و ساعت</span><div className="panel-booking-date-row"><JalaliDatePicker label="تاریخ نوبت" value={date} onChange={(value) => { setDate(value); setStarts([]); setStart(""); }} /><Button variant="outlined" disabled={!items.length || items.some((item) => !item.employee_membership_id)} onClick={() => void check()}><Clock3 size={16} /> دریافت زمان‌های آزاد</Button></div>{starts.length ? <div className="booking-slots">{starts.map((value) => <Button key={value} variant={start === value ? "contained" : "outlined"} onClick={() => setStart(value)}>{value}</Button>)}</div> : <div className="panel-booking-slots-empty"><CalendarClock size={22} /><span>پس از انتخاب خدمات، زمان‌های آزاد را دریافت کنید.</span></div>}</div>
                {items.length > 0 && <div className="panel-booking-summary"><span><Clock3 size={17} /> مدت تقریبی <strong>{number.format(selectedDuration)} دقیقه</strong></span><span><Banknote size={17} /> مبلغ کل <strong>{money.format(selectedTotal)} تومان</strong></span></div>}
            </DialogContent>
            <DialogActions className="panel-booking-actions"><Button onClick={() => { setOpen(false); setEditing(null); }}>انصراف</Button><Button variant="contained" disabled={!customerId || !start || saving} onClick={() => void save()}>{saving ? "در حال ذخیره…" : editing ? "ذخیره جابه‌جایی" : "ثبت نوبت"}</Button></DialogActions>
        </Dialog>

        <Dialog open={Boolean(deleting)} onClose={() => !deleteLoading && setDeleting(null)} fullWidth maxWidth="xs"><DialogTitle>حذف نوبت</DialogTitle><DialogContent className="appointment-delete-dialog"><Alert severity="warning">این عملیات نوبت «{deleting?.customer?.name}» را برای همیشه حذف و زمان کارکنان را آزاد می‌کند.</Alert><Typography color="text.secondary">آیتم‌های نوبت و اطلاعات رزرو آن قابل بازیابی نخواهند بود و مشتری اعلان دریافت می‌کند.</Typography></DialogContent><DialogActions><Button color="inherit" disabled={deleteLoading} onClick={() => setDeleting(null)}>انصراف</Button><Button color="error" variant="contained" disabled={deleteLoading} onClick={() => void remove()}>{deleteLoading ? "در حال حذف…" : "حذف نوبت"}</Button></DialogActions></Dialog>
    </section>;
}
