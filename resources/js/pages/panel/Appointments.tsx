import { Alert, Button, CircularProgress, Dialog, DialogActions, DialogContent, DialogTitle, FormControl, IconButton, InputLabel, Menu, MenuItem, Pagination, Paper, Select, Typography } from "@mui/material";
import { Banknote, CalendarClock, CalendarDays, CalendarPlus, ChevronLeft, ChevronRight, Clock3, Plus, RotateCcw, Scissors, SlidersHorizontal, Trash2, UserX, X } from "lucide-react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { toast } from "react-toastify";
import PanelAppointmentCard from "../../components/appointments/PanelAppointmentCard";
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
    { value: "pending", label: "در انتظار" },
    { value: "completed", label: "انجام‌شده" },
    { value: "cancelled", label: "لغوشده" },
    { value: "no_show", label: "عدم حضور" },
    { value: "rejected", label: "ردشده" },
    { value: "expired", label: "باطل‌شده" },
];
const number = new Intl.NumberFormat("fa-IR");
const money = new Intl.NumberFormat("fa-IR");

function dateLabel(value: string): string {
    return new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long", year: "numeric" })
        .format(new Date(`${value.slice(0, 10)}T12:00:00`));
}

export default function Appointments() {
    const loadRequest = useRef(0);
    const availabilityRequest = useRef(0);
    const [appointments, setAppointments] = useState<Appointment[]>([]);
    const [options, setOptions] = useState<AppointmentOptions>({ customers: [], services: [], can_view_all: false });
    const canDelete = options.can_view_all;
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [open, setOpen] = useState(false);
    const [customerId, setCustomerId] = useState(0);
    const [date, setDate] = useState(tomorrow);
    const [items, setItems] = useState<BookingItemInput[]>([]);
    const [starts, setStarts] = useState<string[]>([]);
    const [start, setStart] = useState("");
    const [checking, setChecking] = useState(false);
    const [availabilityChecked, setAvailabilityChecked] = useState(false);
    const [saving, setSaving] = useState(false);
    const [editing, setEditing] = useState<Appointment | null>(null);
    const [deleting, setDeleting] = useState<Appointment | null>(null);
    const [deleteLoading, setDeleteLoading] = useState(false);
    const [filterDate, setFilterDate] = useState("");
    const [filterStatus, setFilterStatus] = useState("");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [actionMenu, setActionMenu] = useState<{ anchor: HTMLElement; appointment: Appointment } | null>(null);
    const [updatingId, setUpdatingId] = useState<number | null>(null);

    const load = useCallback(async () => {
        const request = ++loadRequest.current;
        setLoading(true);
        try {
            const [result, values] = await Promise.all([
                fetchPanelAppointments({ ...(filterDate ? { date: filterDate } : {}), ...(filterStatus ? { status: filterStatus } : {}), page }),
                fetchAppointmentOptions(),
            ]);
            if (request !== loadRequest.current) return;
            setAppointments(result.data);
            setLastPage(result.meta.last_page);
            setTotal(result.meta.total);
            setOptions(values);
            setError(null);
        } catch (caught) {
            if (request === loadRequest.current) setError(readableApiError(caught));
        } finally {
            if (request === loadRequest.current) setLoading(false);
        }
    }, [filterDate, filterStatus, page]);

    useEffect(() => { void load(); }, [load]);

    const selectedTotal = useMemo(() => items.reduce((sum, item) => sum + (options.services.find((service) => service.id === item.service_id)?.price ?? 0), 0), [items, options.services]);
    const selectedDuration = useMemo(() => items.reduce((sum, item) => sum + (options.services.find((service) => service.id === item.service_id)?.duration_minutes ?? 0), 0), [items, options.services]);

    function openCreate() {
        availabilityRequest.current += 1;
        setEditing(null);
        setCustomerId(options.customers[0]?.id ?? 0);
        setDate(tomorrow());
        setItems([]);
        setStarts([]);
        setStart("");
        setAvailabilityChecked(false);
        setChecking(false);
        setOpen(true);
    }
    function invalidateAvailability() {
        availabilityRequest.current += 1;
        setStarts([]);
        setStart("");
        setChecking(false);
        setAvailabilityChecked(false);
    }
    function addItem() {
        const service = options.services[0];
        if (service) {
            setItems((current) => [...current, { service_id: service.id, employee_membership_id: service.providers[0]?.membership_id ?? 0 }]);
            invalidateAvailability();
        }
    }
    function changeService(index: number, id: number) {
        const service = options.services.find((value) => value.id === id);
        setItems((current) => current.map((item, currentIndex) => currentIndex === index ? { service_id: id, employee_membership_id: service?.providers[0]?.membership_id ?? 0 } : item));
        invalidateAvailability();
    }
    async function check() {
        const request = ++availabilityRequest.current;
        setStart("");
        setStarts([]);
        setChecking(true);
        setAvailabilityChecked(false);
        try {
            const result = await fetchAvailability(date, items, true);
            if (request === availabilityRequest.current) { setStarts(result); setAvailabilityChecked(true); }
        } catch (caught) {
            if (request === availabilityRequest.current) toast.error(readableApiError(caught));
        } finally {
            if (request === availabilityRequest.current) setChecking(false);
        }
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
        availabilityRequest.current += 1;
        setEditing(item);
        setCustomerId(item.customer?.id ?? 0);
        setDate(item.starts_at.slice(0, 10));
        setStart(item.starts_at.slice(11, 16));
        setItems(item.items.flatMap((part) => part.service_id ? [{ service_id: part.service_id, employee_membership_id: part.employee_membership_id }] : []));
        setStarts([item.starts_at.slice(11, 16)]);
        setAvailabilityChecked(true);
        setChecking(false);
        setOpen(true);
    }
    async function status(id: number, value: "completed" | "no_show" | "cancelled") {
        if (updatingId !== null) return;
        setUpdatingId(id);
        try { await updateAppointmentStatus(id, value, value === "cancelled" ? "لغو توسط مدیر سالن" : undefined); toast.success("وضعیت نوبت تغییر کرد."); await load(); } catch (caught) { toast.error(readableApiError(caught)); } finally { setUpdatingId(null); }
    }
    async function remove() {
        if (!deleting) return;
        setDeleteLoading(true);
        try { await deleteAppointment(deleting.id); toast.success("نوبت حذف شد و زمان کارمند آزاد شد."); setDeleting(null); await load(); } catch (caught) { toast.error(readableApiError(caught)); } finally { setDeleteLoading(false); }
    }
    function changeDay(offset: number) {
        setFilterDate((current) => {
            const value = current ? new Date(`${current}T12:00:00`) : new Date();
            value.setDate(value.getDate() + offset);
            return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, "0")}-${String(value.getDate()).padStart(2, "0")}`;
        });
        setPage(1);
    }
    function resetFilters() { setFilterDate(""); setFilterStatus(""); setPage(1); }
    function setToday() {
        const value = new Date();
        setFilterDate(`${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, "0")}-${String(value.getDate()).padStart(2, "0")}`);
        setPage(1);
    }
    function closeBooking() { availabilityRequest.current += 1; setOpen(false); setEditing(null); }
    return <section className="appointments-page">
        <header className="appointments-hero">
            <div><span className="eyebrow">برنامه سالن</span><Typography variant="h2" component="h1">مدیریت نوبت‌ها</Typography><Typography color="text.secondary">{options.can_view_all ? "زمان، متخصص و وضعیت تمام نوبت‌های سالن را در یک نگاه ببینید." : "نوبت‌هایی که برای شما رزرو شده‌اند در این صفحه نمایش داده می‌شوند."}</Typography></div>
            <Button variant="contained" onClick={openCreate}><CalendarPlus size={18} /> ثبت نوبت حضوری</Button>
        </header>

        <Paper variant="outlined" className="appointments-filter-panel">
            <div className="appointments-filter-title"><span><SlidersHorizontal size={18} /></span><div><strong>نمایش نوبت‌ها</strong><small>تاریخ و وضعیت موردنظر را انتخاب کنید</small></div>{(filterDate || filterStatus) && <Button size="small" color="inherit" onClick={resetFilters}><RotateCcw size={14} /> پاک‌کردن فیلترها</Button>}</div>
            <div className="appointments-filter-controls"><div className="appointments-date-navigation" role="group" aria-label="جابه‌جایی روز نوبت‌ها">
                <IconButton aria-label="روز قبل" title="روز قبل" onClick={() => changeDay(-1)}><ChevronRight size={20} /></IconButton>
                <JalaliDatePicker label="تاریخ نوبت" value={filterDate} onChange={(value) => { setFilterDate(value); setPage(1); }} />
                <IconButton aria-label="روز بعد" title="روز بعد" onClick={() => changeDay(1)}><ChevronLeft size={20} /></IconButton>
            </div><div className="appointments-date-shortcuts"><Button variant={filterDate ? "text" : "contained"} onClick={() => { setFilterDate(""); setPage(1); }}>همه روزها</Button><Button variant="text" onClick={setToday}>امروز</Button></div></div>
            <div className="appointments-status-filter" role="group" aria-label="فیلتر وضعیت">{statusFilters.map((item) => <button key={item.value} type="button" aria-pressed={filterStatus === item.value} className={filterStatus === item.value ? "active" : ""} onClick={() => { setFilterStatus(item.value); setPage(1); }}>{item.label}</button>)}</div>
        </Paper>

        {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
        <div className="appointments-results-heading"><div><span className="appointments-results-icon"><CalendarDays size={19} /></span><div><strong>{filterDate ? dateLabel(filterDate) : options.can_view_all ? "همه نوبت‌های سالن" : "نوبت‌های من"}</strong><span>{filterStatus ? labels[filterStatus as Appointment["status"]] : "نوبت‌های ثبت‌شده"}</span></div></div><span>{loading ? "در حال بارگذاری…" : error ? "نتایج در دسترس نیست" : `${number.format(total)} نوبت`}</span></div>
        {loading ? <div className="appointments-loading"><CircularProgress /><span>در حال دریافت برنامه سالن…</span></div> : error ? null : appointments.length ? <>
            <div className="panel-appointment-list">{appointments.map((item) => <PanelAppointmentCard
                key={item.id} appointment={item} canDelete={canDelete} busy={updatingId !== null}
                onEdit={edit} onComplete={(id) => void status(id, "completed")}
                onMore={(anchor, appointment) => setActionMenu({ anchor, appointment })}
            />)}</div>
            {lastPage > 1 && <div className="appointments-pagination"><Pagination count={lastPage} page={page} onChange={(_event, value) => setPage(value)} color="primary" shape="rounded" /></div>}
        </> : <Paper variant="outlined" className="appointments-empty"><span><CalendarPlus size={32} /></span><Typography variant="h3">نوبتی پیدا نشد</Typography><Typography color="text.secondary">فیلترها را تغییر دهید یا اولین نوبت حضوری را ثبت کنید.</Typography><div>{(filterDate || filterStatus) && <Button variant="outlined" onClick={resetFilters}>حذف فیلترها</Button>}<Button variant="contained" onClick={openCreate}>ثبت نوبت جدید</Button></div></Paper>}

        <Menu anchorEl={actionMenu?.anchor ?? null} open={Boolean(actionMenu)} onClose={() => setActionMenu(null)} slotProps={{ paper: { className: "appointments-action-menu" } }}>{actionMenu?.appointment.status === "confirmed" && <><MenuItem onClick={() => { const item = actionMenu?.appointment; setActionMenu(null); if (item) void status(item.id, "no_show"); }}><UserX size={17} /> ثبت عدم حضور</MenuItem><MenuItem onClick={() => { const item = actionMenu?.appointment; setActionMenu(null); if (item) void status(item.id, "cancelled"); }}><X size={17} /> لغو نوبت</MenuItem></>}{canDelete && <MenuItem className="appointment-danger-action" onClick={() => { setDeleting(actionMenu?.appointment ?? null); setActionMenu(null); }}><Trash2 size={17} /> حذف دائمی</MenuItem>}</Menu>

        <Dialog open={open} onClose={closeBooking} fullWidth maxWidth="md" slotProps={{ paper: { className: "panel-booking-modal" } }}>
            <DialogTitle className="panel-booking-title"><span><CalendarPlus size={22} /></span><div><strong>{editing ? "جابه‌جایی نوبت" : "ثبت نوبت حضوری"}</strong><small>{editing ? "زمان یا متخصص نوبت را با حفظ اطلاعات مشتری تغییر دهید." : "مشتری، سرویس و زمان مناسب را برای قرار جدید انتخاب کنید."}</small></div></DialogTitle>
            <DialogContent className="panel-booking-dialog">
                <div className="panel-booking-section"><span className="panel-booking-section-title">۱. مشتری و خدمات</span><FormControl disabled={Boolean(editing)}><InputLabel>مشتری</InputLabel><Select label="مشتری" value={customerId} onChange={(event) => setCustomerId(Number(event.target.value))}>{options.customers.map((customer) => <MenuItem key={customer.id} value={customer.id}>{customer.name} · {customer.mobile}</MenuItem>)}</Select></FormControl>{!options.customers.length && <Alert severity="info">برای ثبت نوبت حضوری، ابتدا مشتری را در بخش کاربران ایجاد کنید.</Alert>}<div className="booking-items-heading"><div><strong>سرویس‌های نوبت</strong><small>{items.length ? `${number.format(items.length)} سرویس انتخاب شده` : "حداقل یک سرویس اضافه کنید"}</small></div><Button variant="outlined" disabled={!options.services.length} onClick={addItem}><Plus size={16} /> افزودن سرویس</Button></div>{!options.services.length && <Alert severity="info">سرویس فعالی برای رزرو وجود ندارد.</Alert>}{items.length ? items.map((item, index) => { const service = options.services.find((value) => value.id === item.service_id); return <div className="booking-item-row" key={`${index}-${item.service_id}`}><span>{number.format(index + 1)}</span><FormControl><InputLabel>سرویس</InputLabel><Select label="سرویس" value={item.service_id} onChange={(event) => changeService(index, Number(event.target.value))}>{options.services.map((value) => <MenuItem key={value.id} value={value.id}>{value.name}</MenuItem>)}</Select></FormControl><FormControl><InputLabel>متخصص</InputLabel><Select label="متخصص" value={item.employee_membership_id} onChange={(event) => { setItems((current) => current.map((value, currentIndex) => currentIndex === index ? { ...value, employee_membership_id: Number(event.target.value) } : value)); invalidateAvailability(); }}>{service?.providers.map((provider) => <MenuItem key={provider.membership_id} value={provider.membership_id}>{provider.name}</MenuItem>)}</Select></FormControl><IconButton color="error" aria-label="حذف سرویس" onClick={() => { setItems((current) => current.filter((_value, currentIndex) => currentIndex !== index)); invalidateAvailability(); }}><Trash2 size={18} /></IconButton></div>; }) : <div className="panel-booking-items-empty"><Scissors size={24} /><span>هنوز سرویسی به این نوبت اضافه نشده است.</span></div>}</div>
                <div className="panel-booking-section"><span className="panel-booking-section-title">۲. تاریخ و ساعت</span><div className="panel-booking-date-row"><JalaliDatePicker label="تاریخ نوبت" value={date} onChange={(value) => { setDate(value); invalidateAvailability(); }} /><Button variant="outlined" disabled={checking || !date || !items.length || items.some((item) => !item.employee_membership_id)} onClick={() => void check()}>{checking ? <CircularProgress size={17} /> : <Clock3 size={16} />} {checking ? "در حال بررسی…" : "نمایش زمان‌های آزاد"}</Button></div>{starts.length ? <><small className="booking-slots-label">یک ساعت شروع را انتخاب کنید</small><div className="booking-slots">{starts.map((value) => <Button key={value} variant={start === value ? "contained" : "outlined"} onClick={() => setStart(value)}>{value}</Button>)}</div></> : <div className="panel-booking-slots-empty"><CalendarClock size={22} /><span>{checking ? "زمان‌های آزاد در حال بررسی‌اند…" : availabilityChecked ? "برای این تاریخ، زمان آزادی پیدا نشد. تاریخ یا متخصص را تغییر دهید." : "پس از انتخاب سرویس و متخصص، زمان‌های آزاد را ببینید."}</span></div>}</div>
                {items.length > 0 && <div className="panel-booking-summary"><span><Clock3 size={17} /> مدت تقریبی <strong>{number.format(selectedDuration)} دقیقه</strong></span><span><Banknote size={17} /> مبلغ کل <strong>{money.format(selectedTotal)} تومان</strong></span></div>}
            </DialogContent>
            <DialogActions className="panel-booking-actions"><Button onClick={closeBooking}>انصراف</Button><Button variant="contained" disabled={!customerId || !start || !items.length || saving || checking} onClick={() => void save()}>{saving ? "در حال ذخیره…" : editing ? "ذخیره جابه‌جایی" : "ثبت نوبت"}</Button></DialogActions>
        </Dialog>

        <Dialog open={Boolean(deleting)} onClose={() => !deleteLoading && setDeleting(null)} fullWidth maxWidth="xs"><DialogTitle>حذف نوبت</DialogTitle><DialogContent className="appointment-delete-dialog"><Alert severity="warning">این عملیات نوبت «{deleting?.customer?.name}» را برای همیشه حذف و زمان کارکنان را آزاد می‌کند.</Alert><Typography color="text.secondary">آیتم‌های نوبت و اطلاعات رزرو آن قابل بازیابی نخواهند بود و مشتری اعلان دریافت می‌کند.</Typography></DialogContent><DialogActions><Button color="inherit" disabled={deleteLoading} onClick={() => setDeleting(null)}>انصراف</Button><Button color="error" variant="contained" disabled={deleteLoading} onClick={() => void remove()}>{deleteLoading ? "در حال حذف…" : "حذف نوبت"}</Button></DialogActions></Dialog>
    </section>;
}
