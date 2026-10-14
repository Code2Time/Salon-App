import { addDays, addWeeks, eachDayOfInterval } from "date-fns-jalali";
import { Alert, Button, Chip, CircularProgress, Dialog, DialogActions, DialogContent, DialogTitle, FormControl, FormControlLabel, InputLabel, MenuItem, Paper, Select, Switch, Typography, IconButton } from "@mui/material";
import { ChevronLeft, ChevronRight, Clock3, Plus, Save, Trash2, UsersRound } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";
import { toast } from "react-toastify";
import StyledTimePicker from "../../components/forms/StyledTimePicker";
import { fetchEmployeeSchedule, fetchEmployees, saveEmployeeDay } from "../../services/api/employeeSchedule";
import { readableApiError } from "../../services/api/client";
import type { WorkingPeriod } from "../../types/appointmentSchedule";
import type { EmployeeOption, EmployeeSchedule, EmployeeScheduleDay } from "../../types/employeeSchedule";

const faDate = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long" });
function saturdayOf(date: Date) { return addDays(date, -((date.getDay() + 1) % 7)); }
function dateString(date: Date) { return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`; }

export default function EmployeeSchedules({ management = false }: { management?: boolean }) {
    const [anchor, setAnchor] = useState(new Date());
    const [employees, setEmployees] = useState<EmployeeOption[]>([]);
    const [membershipId, setMembershipId] = useState<number | undefined>();
    const [schedule, setSchedule] = useState<EmployeeSchedule | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [editing, setEditing] = useState<EmployeeScheduleDay | null>(null);
    const [present, setPresent] = useState(false);
    const [periods, setPeriods] = useState<WorkingPeriod[]>([]);
    const [saving, setSaving] = useState(false);
    const week = useMemo(() => eachDayOfInterval({ start: saturdayOf(anchor), end: addDays(saturdayOf(anchor), 6) }), [anchor]);

    useEffect(() => { if (management) void fetchEmployees().then((items) => { setEmployees(items); setMembershipId((current) => current ?? items[0]?.membership_id); }).catch((caught) => setError(readableApiError(caught))); }, [management]);
    const load = useCallback(async () => {
        if (management && !membershipId) { setLoading(false); return; }
        setLoading(true); setError(null);
        try { setSchedule(await fetchEmployeeSchedule(dateString(week[0]!), dateString(week[6]!), membershipId)); }
        catch (caught) { setError(readableApiError(caught)); }
        finally { setLoading(false); }
    }, [management, membershipId, week]);
    useEffect(() => { void load(); }, [load]);

    function open(day: EmployeeScheduleDay) {
        if (!day.salon_is_open) return;
        setEditing(day); setPresent(day.is_present); setPeriods(day.periods.length ? day.periods : day.salon_periods.slice(0, 1).map((item) => ({ ...item })));
    }
    function updatePeriod(index: number, patch: Partial<WorkingPeriod>) { setPeriods((items) => items.map((item, current) => current === index ? { ...item, ...patch } : item)); }
    async function save() {
        if (!editing) return; setSaving(true);
        try { await saveEmployeeDay(editing.date, { is_present: present, periods: present ? periods : [], ...(membershipId ? { membership_id: membershipId } : {}) }); toast.success(management ? "برنامه کارمند ویرایش و اعلان ارسال شد." : "برنامه کاری شما ذخیره شد."); setEditing(null); await load(); }
        catch (caught) { toast.error(readableApiError(caught)); } finally { setSaving(false); }
    }

    return <section className="employee-schedule-page">
        <div className="page-heading"><span className="eyebrow">{management ? "نظارت بر حضور تیم" : "برنامه حضور من"}</span><Typography variant="h2" component="h1">{management ? "برنامه کارکنان" : "برنامه کاری من"}</Typography><Typography color="text.secondary">روز موردنظر را انتخاب و بازه‌های حضور را ساده و دقیق ثبت کنید.</Typography></div>
        <Paper variant="outlined" className="employee-schedule-toolbar">
            {management && <FormControl size="small"><InputLabel>کارمند</InputLabel><Select label="کارمند" value={membershipId ?? ""} onChange={(event) => setMembershipId(Number(event.target.value))}>{employees.map((employee) => <MenuItem key={employee.membership_id} value={employee.membership_id}>{employee.name}</MenuItem>)}</Select></FormControl>}
            <div className="employee-week-navigation"><IconButton onClick={() => setAnchor((value) => addWeeks(value, -1))}><ChevronRight /></IconButton><strong>{faDate.format(week[0])} تا {faDate.format(week[6])}</strong><IconButton onClick={() => setAnchor((value) => addWeeks(value, 1))}><ChevronLeft /></IconButton></div>
        </Paper>
        {error && <Alert severity="error" action={<Button onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
        {loading ? <div className="schedule-loading"><CircularProgress /></div> : !schedule ? <Paper className="users-empty"><UsersRound /><Typography>کارمند فعالی وجود ندارد.</Typography></Paper> : <div className="employee-day-grid">{schedule.days.map((day) => <button key={day.date} type="button" disabled={!day.salon_is_open} className={`employee-day-card ${day.is_present ? "present" : "absent"}`} onClick={() => open(day)}><span><strong>{faDate.format(new Date(`${day.date}T00:00:00`))}</strong><Chip size="small" label={!day.salon_is_open ? "تعطیل" : day.is_present ? "حاضر" : "ثبت نشده"} /></span><small>{day.salon_title || (day.salon_is_open ? `ساعات سالن: ${day.salon_periods.map((item) => `${item.starts_at}-${item.ends_at}`).join("، ")}` : "سالن بسته است")}</small><div>{day.periods.map((item) => <b key={item.starts_at}><Clock3 size={14} /> {item.starts_at} تا {item.ends_at}</b>)}</div></button>)}</div>}
        <Dialog open={Boolean(editing)} onClose={() => !saving && setEditing(null)} fullWidth maxWidth="sm" slotProps={{ paper: { className: "working-day-dialog" } }}><DialogTitle>حضور در {editing && faDate.format(new Date(`${editing.date}T00:00:00`))}</DialogTitle><DialogContent className="working-day-form"><Alert severity="info">ساعات باز سالن: {editing?.salon_periods.map((item) => `${item.starts_at} تا ${item.ends_at}`).join("، ")}</Alert><FormControlLabel control={<Switch checked={present} onChange={(event) => setPresent(event.target.checked)} />} label={present ? "در سالن حضور دارم" : "در سالن حضور ندارم"} />{present && <div className="working-period-list"><div className="working-period-heading"><strong>بازه‌های حضور</strong><Button onClick={() => setPeriods((items) => [...items, { starts_at: "09:00", ends_at: "17:00" }])}><Plus size={16} /> افزودن</Button></div>{periods.map((period, index) => <div className="working-period-row" key={index}><StyledTimePicker label="شروع" value={period.starts_at} onChange={(value) => updatePeriod(index, { starts_at: value })} /><StyledTimePicker label="پایان" value={period.ends_at} onChange={(value) => updatePeriod(index, { ends_at: value })} /><IconButton color="error" onClick={() => setPeriods((items) => items.filter((_item, current) => current !== index))}><Trash2 /></IconButton></div>)}</div>}</DialogContent><DialogActions><Button color="inherit" onClick={() => setEditing(null)}>انصراف</Button><Button variant="contained" disabled={saving} onClick={() => void save()}><Save size={16} /> ذخیره</Button></DialogActions></Dialog>
    </section>;
}
