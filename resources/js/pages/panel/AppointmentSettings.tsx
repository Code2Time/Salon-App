import { addDays, addMonths, addWeeks, eachDayOfInterval, endOfMonth, startOfMonth } from "date-fns-jalali";
import { Alert, Button, Chip, CircularProgress, Dialog, DialogActions, DialogContent, DialogTitle, FormControl, FormControlLabel, IconButton, InputLabel, MenuItem, Paper, Select, Switch, TextField, ToggleButton, ToggleButtonGroup, Typography } from "@mui/material";
import { CalendarDays, ChevronLeft, ChevronRight, Clock3, Pencil, Plus, RotateCcw, Save, Trash2 } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";
import { isAxiosError } from "axios";
import { toast } from "react-toastify";
import JalaliDatePicker from "../../components/forms/JalaliDatePicker";
import StyledTimePicker from "../../components/forms/StyledTimePicker";
import { fetchAppointmentSchedule, updateAppointmentSettings, updateSalonWorkingDay } from "../../services/api/appointmentSchedule";
import { readableApiError } from "../../services/api/client";
import type { AppointmentSettings as Settings, PlanningPeriod, SalonWorkingDay, WorkingDayInput, WorkingPeriod } from "../../types/appointmentSchedule";

const faDate = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long" });
const faWeekday = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long" });
const faDayMonth = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { day: "numeric", month: "long" });
const faMonth = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { month: "long", year: "numeric" });
const defaultSettings: Settings = { planning_period: "weekly", is_recurring: false, timezone: "Asia/Tehran", slot_interval_minutes: 15, cancellation_notice_hours: 5, deposit_enabled: false, deposit_type: null, deposit_value: null };
const defaultOpenDay: WorkingDayInput = { is_open: true, title: null, description: null, periods: [{ starts_at: "09:00", ends_at: "17:00" }] };

function dateString(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
}
function parseDate(value: string): Date {
    const [year, month, day] = value.split("-").map(Number);
    return new Date(year ?? 1970, (month ?? 1) - 1, day ?? 1);
}
function saturdayOf(date: Date): Date {
    return addDays(date, -((date.getDay() + 1) % 7));
}
function chunks<T>(values: T[], size: number): T[][] {
    return Array.from({ length: Math.ceil(values.length / size) }, (_, index) => values.slice(index * size, index * size + size));
}

export default function AppointmentSettings() {
    const [anchor, setAnchor] = useState(() => new Date());
    const [settings, setSettings] = useState(defaultSettings);
    const [days, setDays] = useState<SalonWorkingDay[]>([]);
    const [weekIndex, setWeekIndex] = useState(0);
    const [loading, setLoading] = useState(true);
    const [savingSettings, setSavingSettings] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [editingDate, setEditingDate] = useState<string | null>(null);
    const [form, setForm] = useState<WorkingDayInput>(defaultOpenDay);
    const [savingDay, setSavingDay] = useState(false);
    const [affectedEmployees, setAffectedEmployees] = useState<string[]>([]);

    const monthDays = useMemo(() => eachDayOfInterval({ start: startOfMonth(anchor), end: endOfMonth(anchor) }), [anchor]);
    const monthWeeks = useMemo(() => chunks(monthDays, 7), [monthDays]);
    const weeklyDays = useMemo(() => eachDayOfInterval({ start: saturdayOf(anchor), end: addDays(saturdayOf(anchor), 6) }), [anchor]);
    const visibleDays = settings.planning_period === "monthly" ? (monthWeeks[weekIndex] ?? monthWeeks[0] ?? []) : weeklyDays;
    const range = settings.planning_period === "monthly"
        ? { from: dateString(startOfMonth(anchor)), to: dateString(endOfMonth(anchor)) }
        : { from: dateString(weeklyDays[0] ?? anchor), to: dateString(weeklyDays[6] ?? anchor) };

    const load = useCallback(async () => {
        setLoading(true); setError(null);
        try {
            const result = await fetchAppointmentSchedule(range.from, range.to);
            setSettings(result.settings); setDays(result.days);
        } catch (caught) { setError(readableApiError(caught)); }
        finally { setLoading(false); }
    }, [range.from, range.to]);

    useEffect(() => { void load(); }, [load]);
    useEffect(() => { setWeekIndex(0); }, [anchor, settings.planning_period]);

    async function changeSettings(period: PlanningPeriod | null, recurring = settings.is_recurring) {
        if (!period) return;
        setSavingSettings(true);
        try {
            const updated = await updateAppointmentSettings({ planning_period: period, is_recurring: recurring });
            setSettings(updated); toast.success("تنظیمات نوبت‌دهی ذخیره شد.");
        } catch (caught) { toast.error(readableApiError(caught)); }
        finally { setSavingSettings(false); }
    }
    async function saveDeposit() {
        setSavingSettings(true);
        try {
            const updated = await updateAppointmentSettings({ planning_period: settings.planning_period, is_recurring: settings.is_recurring, deposit_enabled: settings.deposit_enabled, deposit_type: settings.deposit_enabled ? (settings.deposit_type ?? "fixed") : null, deposit_value: settings.deposit_enabled ? (settings.deposit_value ?? 1) : null });
            setSettings(updated); toast.success("تنظیمات پیش‌پرداخت ذخیره شد.");
        } catch (caught) { toast.error(readableApiError(caught)); } finally { setSavingSettings(false); }
    }

    function openDay(date: Date) {
        const key = dateString(date);
        const existing = days.find((day) => day.date === key);
        setEditingDate(key);
        setAffectedEmployees([]);
        setForm(existing ? { is_open: existing.is_open, title: existing.title, description: existing.description, periods: existing.periods.map(({ starts_at, ends_at }) => ({ starts_at, ends_at })) } : defaultOpenDay);
    }

    async function saveDay(confirmEmployeeImpact = false) {
        if (!editingDate) return;
        setSavingDay(true);
        try {
            const saved = await updateSalonWorkingDay(editingDate, { ...form, ...(confirmEmployeeImpact ? { confirm_employee_impact: true } : {}) });
            setDays((current) => [...current.filter((day) => day.date !== saved.date), saved]);
            setEditingDate(null); toast.success("برنامه این روز ذخیره شد.");
        } catch (caught) {
            if (isAxiosError(caught) && caught.response?.status === 409 && caught.response.data?.code === "employee_schedule_conflict") {
                const names = Array.isArray(caught.response.data.employees) ? caught.response.data.employees.map((employee: { name: string }) => employee.name) : [];
                setAffectedEmployees(names);
                toast.warning("این روز دارای برنامه حضور کارکنان است و به تأیید شما نیاز دارد.");
            } else toast.error(readableApiError(caught));
        }
        finally { setSavingDay(false); }
    }

    function move(direction: -1 | 1) {
        if (settings.planning_period === "weekly") return setAnchor((value) => addWeeks(value, direction));
        const next = weekIndex + direction;
        if (next >= 0 && next < monthWeeks.length) return setWeekIndex(next);
        setAnchor((value) => addMonths(value, direction));
        setWeekIndex(direction === -1 ? Math.max(0, monthWeeks.length - 1) : 0);
    }

    function updatePeriod(index: number, patch: Partial<WorkingPeriod>) {
        setForm((current) => ({ ...current, periods: current.periods.map((period, itemIndex) => itemIndex === index ? { ...period, ...patch } : period) }));
    }

    return <section className="appointment-settings-page">
        <div className="page-heading appointment-heading">
            <div><span className="eyebrow">چارچوب روزهای کاری</span><Typography variant="h2" component="h1">تنظیمات نوبت‌دهی</Typography><Typography color="text.secondary">روزهای باز و ساعات کاری سالن را برای برنامه‌ریزی حضور کارکنان مشخص کنید.</Typography></div>
        </div>

        <Paper variant="outlined" className="schedule-preferences">
            <div><Typography variant="h3">نوع برنامه‌ریزی</Typography><Typography color="text.secondary" variant="body2">این انتخاب فقط شیوه نمایش آینده را تغییر می‌دهد و سوابق را حذف نمی‌کند.</Typography></div>
            <ToggleButtonGroup exclusive value={settings.planning_period} disabled={savingSettings} onChange={(_event, value) => void changeSettings(value)} size="small">
                <ToggleButton value="weekly">هفتگی</ToggleButton><ToggleButton value="monthly">ماهانه</ToggleButton>
            </ToggleButtonGroup>
            <FormControlLabel control={<Switch checked={settings.is_recurring} disabled={savingSettings} onChange={(event) => void changeSettings(settings.planning_period, event.target.checked)} />} label="تکرار برنامه برای دوره‌های بعد" />
        </Paper>
        <Paper variant="outlined" className="schedule-preferences deposit-preferences">
            <div><Typography variant="h3">پیش‌پرداخت نوبت</Typography><Typography color="text.secondary" variant="body2">مبلغ موردنیاز برای هر نوبت محاسبه و برای اتصال درگاه ذخیره می‌شود.</Typography></div>
            <FormControlLabel control={<Switch checked={settings.deposit_enabled} onChange={(event) => setSettings((current) => ({ ...current, deposit_enabled: event.target.checked }))} />} label="پیش‌پرداخت فعال باشد" />
            {settings.deposit_enabled && <><FormControl size="small"><InputLabel>نوع مبلغ</InputLabel><Select label="نوع مبلغ" value={settings.deposit_type ?? "fixed"} onChange={(event) => setSettings((current) => ({ ...current, deposit_type: event.target.value as "fixed" | "percentage" }))}><MenuItem value="fixed">مبلغ ثابت</MenuItem><MenuItem value="percentage">درصد مبلغ نوبت</MenuItem></Select></FormControl><TextField size="small" type="number" label={settings.deposit_type === "percentage" ? "درصد" : "مبلغ (تومان)"} value={settings.deposit_value ?? ""} onChange={(event) => setSettings((current) => ({ ...current, deposit_value: Number(event.target.value) }))} /></>}
            <Button variant="contained" disabled={savingSettings} onClick={() => void saveDeposit()}>ذخیره پیش‌پرداخت</Button>
        </Paper>

        <Paper variant="outlined" className="schedule-calendar">
            <div className="schedule-toolbar">
                <div><IconButton onClick={() => move(-1)} aria-label="دوره قبل"><ChevronRight /></IconButton><IconButton onClick={() => move(1)} aria-label="دوره بعد"><ChevronLeft /></IconButton></div>
                <div className="schedule-title"><CalendarDays size={20} /><strong>{settings.planning_period === "monthly" ? faMonth.format(anchor) : `${faDate.format(visibleDays[0])} تا ${faDate.format(visibleDays.at(-1)!)}`}</strong>{settings.planning_period === "monthly" && <small>هفته {weekIndex + 1} از {monthWeeks.length}</small>}</div>
                <div className="schedule-jump"><JalaliDatePicker label="رفتن به تاریخ" value={dateString(anchor)} onChange={(value) => value && setAnchor(parseDate(value))} /><Button color="inherit" onClick={() => setAnchor(new Date())}><RotateCcw size={16} /> امروز</Button></div>
            </div>
            {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
            {loading ? <div className="schedule-loading"><CircularProgress /></div> : <div className="schedule-day-grid">
                {visibleDays.map((date) => {
                    const item = days.find((day) => day.date === dateString(date));
                    const isOpen = item?.is_open ?? true;
                    return <button type="button" className={`schedule-day-card ${isOpen ? "open" : "closed"}`} key={dateString(date)} onClick={() => openDay(date)}>
                        <span className="schedule-day-top">
                            <span className="schedule-date"><small>{faWeekday.format(date)}</small><strong>{faDayMonth.format(date)}</strong></span>
                            <Chip size="small" label={isOpen ? "باز" : "بسته"} className={isOpen ? "day-open-chip" : "day-closed-chip"} />
                        </span>
                        <span className="schedule-day-body">
                            <strong className="schedule-day-title">{item?.title || (isOpen ? "روز کاری عادی" : "روز تعطیل")}</strong>
                            <span className="schedule-periods">{item?.periods.length ? item.periods.map((period) => <span key={`${period.starts_at}-${period.ends_at}`}><Clock3 size={14} /> {period.starts_at} تا {period.ends_at}</span>) : <span>{isOpen ? "ساعت کاری را تنظیم کنید" : "بدون بازه کاری"}</span>}</span>
                            {item?.description && <small>{item.description}</small>}
                        </span>
                        <span className="schedule-card-action"><Pencil size={14} /> تنظیم این روز</span>
                    </button>;
                })}
            </div>}
        </Paper>

        <Dialog open={Boolean(editingDate)} onClose={() => !savingDay && setEditingDate(null)} fullWidth maxWidth="sm" slotProps={{ paper: { className: "working-day-dialog" } }}>
            <DialogTitle>برنامه {editingDate ? faDate.format(parseDate(editingDate)) : "روز"}</DialogTitle>
            <DialogContent className="working-day-form">
                {affectedEmployees.length > 0 && <Alert severity="warning"><strong>هشدار تغییر برنامه کارکنان</strong><br />با تعطیل‌کردن این روز، حضور {affectedEmployees.join("، ")} لغو می‌شود و برای آن‌ها اعلان ارسال خواهد شد.</Alert>}
                <FormControlLabel control={<Switch checked={form.is_open} onChange={(event) => setForm((current) => ({ ...current, is_open: event.target.checked, periods: event.target.checked && current.periods.length === 0 ? [{ starts_at: "09:00", ends_at: "17:00" }] : current.periods }))} />} label={form.is_open ? "سالن در این روز باز است" : "سالن در این روز بسته است"} />
                <TextField label="عنوان (اختیاری)" value={form.title ?? ""} onChange={(event) => setForm((current) => ({ ...current, title: event.target.value || null }))} placeholder="مثلاً تعطیل رسمی یا ساعت کاری ویژه" />
                <TextField label="توضیحات (اختیاری)" value={form.description ?? ""} onChange={(event) => setForm((current) => ({ ...current, description: event.target.value || null }))} multiline minRows={2} />
                {form.is_open && <div className="working-period-list"><div className="working-period-heading"><strong>بازه‌های کاری</strong><Button size="small" onClick={() => setForm((current) => ({ ...current, periods: [...current.periods, { starts_at: "09:00", ends_at: "17:00" }] }))}><Plus size={16} /> افزودن بازه</Button></div>{form.periods.map((period, index) => <div className="working-period-row" key={index}><StyledTimePicker label="شروع" value={period.starts_at} onChange={(value) => updatePeriod(index, { starts_at: value })} /><StyledTimePicker label="پایان" value={period.ends_at} onChange={(value) => updatePeriod(index, { ends_at: value })} /><IconButton color="error" aria-label="حذف بازه" onClick={() => setForm((current) => ({ ...current, periods: current.periods.filter((_period, itemIndex) => itemIndex !== index) }))}><Trash2 size={18} /></IconButton></div>)}</div>}
            </DialogContent>
            <DialogActions><Button color="inherit" disabled={savingDay} onClick={() => setEditingDate(null)}>انصراف</Button><Button variant="contained" color={affectedEmployees.length ? "warning" : "primary"} disabled={savingDay} onClick={() => void saveDay(affectedEmployees.length > 0)}><Save size={17} /> {savingDay ? "در حال ذخیره…" : affectedEmployees.length ? "تأیید تعطیلی و ارسال اعلان" : "ذخیره برنامه"}</Button></DialogActions>
        </Dialog>
    </section>;
}
