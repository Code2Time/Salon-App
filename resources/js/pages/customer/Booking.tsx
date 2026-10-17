import { Alert, Avatar, Button, CircularProgress, IconButton, MenuItem, Paper, Select, TextField, Typography } from "@mui/material";
import { ArrowDown, ArrowLeft, ArrowUp, CalendarDays, Check, CheckCircle2, Clock3, Plus, Scissors, Sparkles, Trash2, UserRound, WalletCards } from "lucide-react";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router";
import { toast } from "react-toastify";
import JalaliDatePicker from "../../components/forms/JalaliDatePicker";
import { defaultAvatarUrl, logoUrl } from "../../constants/assets";
import { createAppointment, fetchAvailability } from "../../services/api/appointments";
import { readableApiError } from "../../services/api/client";
import { fetchCustomerServices } from "../../services/api/customerServices";
import type { BookingItemInput } from "../../types/appointment";
import type { CustomerService } from "../../types/customerService";

const numberFormat = new Intl.NumberFormat("fa-IR");
const persianDate = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long", year: "numeric" });
const faDigits = (value: string) => value.replace(/\d/g, (digit) => "۰۱۲۳۴۵۶۷۸۹"[Number(digit)] ?? digit);

function displayDate(value: string): string {
    if (!value) return "انتخاب نشده";
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) return "انتخاب نشده";
    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    return persianDate.format(new Date(year, month - 1, day));
}

export default function CustomerBooking() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const requestedServiceId = Number(searchParams.get("service"));
    const [services, setServices] = useState<CustomerService[]>([]);
    const [items, setItems] = useState<BookingItemInput[]>([]);
    const [date, setDate] = useState("");
    const [starts, setStarts] = useState<string[]>([]);
    const [selectedStart, setSelectedStart] = useState("");
    const [notes, setNotes] = useState("");
    const [loading, setLoading] = useState(true);
    const [checking, setChecking] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [availabilityError, setAvailabilityError] = useState<string | null>(null);
    const availabilityRequest = useRef(0);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const result = await fetchCustomerServices({ per_page: 100 });
            setServices(result.data);
            const initial = result.data.find((service) => service.id === requestedServiceId);
            if (!initial) {
                setError("خدمت انتخاب‌شده پیدا نشد یا دیگر قابل رزرو نیست.");
                setItems([]);
                return;
            }
            setItems([{ service_id: initial.id, employee_membership_id: 0 }]);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, [requestedServiceId]);

    useEffect(() => { void load(); }, [load]);

    const selectedServices = useMemo(() => items.map((item) => services.find((service) => service.id === item.service_id)).filter(Boolean) as CustomerService[], [items, services]);
    const totalPrice = selectedServices.reduce((sum, service) => sum + service.price, 0);
    const totalDuration = selectedServices.reduce((sum, service) => sum + service.duration_minutes, 0);
    const selectionComplete = items.length > 0 && items.every((item) => item.service_id > 0 && item.employee_membership_id > 0);
    const selectedServiceIds = new Set(items.map((item) => item.service_id));

    useEffect(() => {
        if (!date || !selectionComplete) return;
        const timer = window.setTimeout(() => { void checkAvailability(); }, 250);
        return () => window.clearTimeout(timer);
    }, [date, items, selectionComplete]);

    function invalidateAvailability() {
        availabilityRequest.current += 1;
        setChecking(false);
        setStarts([]);
        setSelectedStart("");
        setAvailabilityError(null);
    }

    function updateItem(index: number, next: BookingItemInput) {
        setItems((current) => current.map((item, itemIndex) => itemIndex === index ? next : item));
        invalidateAvailability();
    }

    function addService(serviceId: number) {
        if (!serviceId || selectedServiceIds.has(serviceId)) return;
        setItems((current) => [...current, { service_id: serviceId, employee_membership_id: 0 }]);
        invalidateAvailability();
    }

    function removeItem(index: number) {
        setItems((current) => current.filter((_item, itemIndex) => itemIndex !== index));
        invalidateAvailability();
    }

    function moveItem(index: number, direction: -1 | 1) {
        const target = index + direction;
        if (target < 0 || target >= items.length) return;
        setItems((current) => {
            const next = [...current];
            const currentItem = next[index];
            const targetItem = next[target];
            if (!currentItem || !targetItem) return current;
            next[index] = targetItem;
            next[target] = currentItem;
            return next;
        });
        invalidateAvailability();
    }

    async function checkAvailability() {
        if (!selectionComplete || !date) return;
        const request = ++availabilityRequest.current;
        setChecking(true);
        setAvailabilityError(null);
        setSelectedStart("");
        try {
            const available = await fetchAvailability(date, items);
            if (request !== availabilityRequest.current) return;
            setStarts(available);
            if (!available.length) setAvailabilityError("در این روز زمان آزادی برای ترکیب انتخاب‌شده وجود ندارد. تاریخ یا متخصص دیگری را امتحان کنید.");
        } catch (caught) {
            if (request !== availabilityRequest.current) return;
            setStarts([]);
            setAvailabilityError(readableApiError(caught));
        } finally {
            if (request === availabilityRequest.current) setChecking(false);
        }
    }

    async function submit() {
        if (!selectedStart || saving) return;
        setSaving(true);
        try {
            const appointment = await createAppointment({ date, starts_at: selectedStart, items, ...(notes.trim() ? { notes: notes.trim() } : {}) });
            toast.success("نوبت شما با موفقیت ثبت و تأیید شد.");
            navigate(`/app/appointments?created=${appointment.id}`, { replace: true });
        } catch (caught) {
            toast.error(readableApiError(caught));
            setSelectedStart("");
            try {
                const available = await fetchAvailability(date, items);
                setStarts(available);
                setAvailabilityError(available.length ? "زمان‌های آزاد به‌روز شدند؛ لطفاً دوباره یک ساعت انتخاب کنید." : "این زمان‌ها دیگر آزاد نیستند؛ تاریخ یا متخصص دیگری را انتخاب کنید.");
            } catch {
                setStarts([]);
            }
        } finally {
            setSaving(false);
        }
    }

    if (loading) return <div className="booking-loading"><CircularProgress /><span>در حال آماده‌کردن رزرو شما…</span></div>;

    if (error) return <Paper variant="outlined" className="booking-state-card"><Scissors size={38} /><Typography variant="h2">امکان شروع رزرو نیست</Typography><Typography color="text.secondary">{error}</Typography><div><Button component={Link} to="/app/services" variant="contained">بازگشت به خدمات</Button><Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button></div></Paper>;

    return (
        <section className="customer-booking-page">
            <header className="booking-heading">
                <div><span className="eyebrow">رزرو آنلاین سالن</span><Typography component="h1" variant="h2">وقت زیبایی شما</Typography><Typography color="text.secondary">قدم‌به‌قدم همراهتان هستیم؛ خدمت، متخصص و زمان مناسب را انتخاب کنید.</Typography></div>
                <Button component={Link} to="/app/services" color="inherit"><ArrowLeft size={17} /> بازگشت به خدمات</Button>
            </header>

            <div className="booking-progress" aria-label="مراحل رزرو">
                <div className={selectionComplete ? "done" : "active"}><span>{selectionComplete ? <Check size={16} /> : "۱"}</span><div><strong>خدمت و متخصص</strong><small>انتخاب سلیقه شما</small></div></div>
                <i />
                <div className={selectedStart ? "done" : selectionComplete ? "active" : ""}><span>{selectedStart ? <Check size={16} /> : "۲"}</span><div><strong>تاریخ و ساعت</strong><small>زمان آزاد سالن</small></div></div>
                <i />
                <div className={selectedStart ? "active" : ""}><span>۳</span><div><strong>تأیید نهایی</strong><small>مرور و ثبت نوبت</small></div></div>
            </div>

            <div className="customer-booking-layout">
                <div className="booking-main-column">
                    <Paper variant="outlined" className="booking-section-card">
                        <div className="booking-section-title"><span><Scissors size={20} /></span><div><Typography variant="h3">خدمات و متخصصان</Typography><Typography color="text.secondary">برای هر خدمت، متخصص موردنظرتان را انتخاب کنید.</Typography></div></div>
                        <div className="booking-service-list">
                            {items.map((item, index) => {
                                const service = services.find((entry) => entry.id === item.service_id)!;
                                return <article className="booking-service-item" key={`${item.service_id}-${index}`}>
                                    <div className="booking-service-head">
                                        <div className="booking-service-image"><img src={service.categories[0]?.image_url || logoUrl} alt={service.categories[0]?.image_url ? service.name : "لوگوی آرا"} /></div>
                                        <div><span>خدمت {numberFormat.format(index + 1)}</span><Typography variant="h3">{service.name}</Typography><small>{numberFormat.format(service.duration_minutes)} دقیقه · {numberFormat.format(service.price)} تومان</small></div>
                                        <div className="booking-item-actions">
                                            <IconButton aria-label="انتقال به بالا" disabled={index === 0} onClick={() => moveItem(index, -1)}><ArrowUp size={17} /></IconButton>
                                            <IconButton aria-label="انتقال به پایین" disabled={index === items.length - 1} onClick={() => moveItem(index, 1)}><ArrowDown size={17} /></IconButton>
                                            <IconButton aria-label={`حذف ${service.name}`} color="error" disabled={items.length === 1} onClick={() => removeItem(index)}><Trash2 size={17} /></IconButton>
                                        </div>
                                    </div>
                                    <div className="booking-provider-label"><UserRound size={17} /><strong>متخصص این خدمت</strong><small>انتخاب متخصص الزامی است</small></div>
                                    {service.providers.length ? <div className="booking-provider-grid">{service.providers.map((provider) => {
                                        const selected = item.employee_membership_id === provider.membership_id;
                                        return <button type="button" className={selected ? "selected" : ""} key={provider.membership_id} onClick={() => updateItem(index, { ...item, employee_membership_id: provider.membership_id })}><Avatar src={provider.image_url || defaultAvatarUrl} alt={provider.name} /><span><strong>{provider.name}</strong><small>{selected ? "انتخاب‌شده" : "انتخاب متخصص"}</small></span>{selected && <CheckCircle2 size={20} />}</button>;
                                    })}</div> : <Alert severity="warning">برای این خدمت متخصص فعالی تعریف نشده است.</Alert>}
                                </article>;
                            })}
                        </div>
                        {services.some((service) => !selectedServiceIds.has(service.id)) && <div className="booking-add-service"><div><Plus size={19} /><span><strong>خدمت دیگری نیاز دارید؟</strong><small>خدمات به‌ترتیب و بدون فاصله انجام می‌شوند.</small></span></div><Select size="small" displayEmpty value="" inputProps={{ "aria-label": "افزودن خدمت دیگر" }} onChange={(event) => addService(Number(event.target.value))} renderValue={() => "افزودن خدمت"}>{services.filter((service) => !selectedServiceIds.has(service.id)).map((service) => <MenuItem key={service.id} value={service.id}>{service.name} · {numberFormat.format(service.duration_minutes)} دقیقه</MenuItem>)}</Select></div>}
                    </Paper>

                    <Paper variant="outlined" className={`booking-section-card${selectionComplete ? "" : " muted"}`}>
                        <div className="booking-section-title"><span><CalendarDays size={20} /></span><div><Typography variant="h3">تاریخ و ساعت</Typography><Typography color="text.secondary">پس از انتخاب متخصصان، زمان‌های واقعاً آزاد نمایش داده می‌شوند.</Typography></div></div>
                        {!selectionComplete && <Alert severity="info">ابتدا برای همه خدمات یک متخصص انتخاب کنید.</Alert>}
                        <div className="booking-date-row">
                            <JalaliDatePicker label="تاریخ نوبت" value={date} disablePast onChange={(value) => { setDate(value); invalidateAvailability(); }} />
                            <div className={`booking-auto-status${checking ? " loading" : ""}`}><Clock3 size={18} />{checking ? <span><strong>در حال پیدا کردن زمان‌های آزاد…</strong><small>چند لحظه صبر کنید</small></span> : date && selectionComplete ? <span><strong>ساعت‌ها خودکار به‌روز می‌شوند</strong><small>با تغییر تاریخ یا متخصص نیازی به دکمه نیست</small></span> : <span><strong>انتخاب تاریخ</strong><small>زمان‌های آزاد خودکار نمایش داده می‌شوند</small></span>}</div>
                        </div>
                        {availabilityError && <Alert severity={starts.length ? "info" : "warning"}>{availabilityError}</Alert>}
                        {starts.length > 0 && <div className="booking-time-area"><div><strong>ساعت شروع را انتخاب کنید</strong><small>{numberFormat.format(starts.length)} زمان آزاد برای {displayDate(date)}</small></div><div className="booking-time-grid">{starts.map((time) => <button type="button" className={selectedStart === time ? "selected" : ""} key={time} onClick={() => setSelectedStart(time)}><Clock3 size={15} /><span>{faDigits(time)}</span>{selectedStart === time && <Check size={15} />}</button>)}</div></div>}
                    </Paper>

                    <Paper variant="outlined" className="booking-section-card booking-notes-card">
                        <div className="booking-section-title"><span><Sparkles size={20} /></span><div><Typography variant="h3">توضیح برای سالن</Typography><Typography color="text.secondary">اگر نکته‌ای هست که متخصص باید بداند، اینجا بنویسید.</Typography></div></div>
                        <TextField multiline minRows={3} value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="مثلاً حساسیت پوستی یا توضیح کوتاه درباره نتیجه دلخواه…" slotProps={{ htmlInput: { maxLength: 1000 } }} />
                    </Paper>
                </div>

                <aside className="booking-review-column">
                    <Paper variant="outlined" className="booking-review-card">
                        <div className="booking-review-header"><span><Sparkles size={20} /></span><div><Typography variant="h3">خلاصه نوبت</Typography><small>پیش از ثبت، انتخاب‌ها را مرور کنید</small></div></div>
                        <div className="booking-review-services">{items.map((item, index) => { const service = services.find((entry) => entry.id === item.service_id)!; const provider = service.providers.find((entry) => entry.membership_id === item.employee_membership_id); return <div key={`${item.service_id}-${index}`}><span>{numberFormat.format(index + 1)}</span><div><strong>{service.name}</strong><small>{provider ? `با ${provider.name}` : "متخصص انتخاب نشده"}</small></div><b>{numberFormat.format(service.price)}</b></div>; })}</div>
                        <div className="booking-review-facts"><div><CalendarDays size={17} /><span><small>تاریخ</small><strong>{displayDate(date)}</strong></span></div><div><Clock3 size={17} /><span><small>ساعت شروع</small><strong>{selectedStart ? faDigits(selectedStart) : "انتخاب نشده"}</strong></span></div><div><Scissors size={17} /><span><small>مدت کل</small><strong>{numberFormat.format(totalDuration)} دقیقه</strong></span></div></div>
                        <div className="booking-price-row"><span><WalletCards size={18} /> مبلغ کل</span><strong>{numberFormat.format(totalPrice)} <small>تومان</small></strong></div>
                        <Button variant="contained" size="large" disabled={!selectedStart || saving} onClick={() => void submit()}>{saving ? <><CircularProgress size={19} color="inherit" /> در حال ثبت نوبت…</> : <>تأیید و ثبت نوبت <ArrowLeft size={18} /></>}</Button>
                        <p><CheckCircle2 size={15} /> نوبت دارای زمان آزاد، بلافاصله تأیید می‌شود.</p>
                    </Paper>
                </aside>
            </div>
        </section>
    );
}
