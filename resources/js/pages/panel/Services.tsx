import {
    Alert,
    Autocomplete,
    Avatar,
    Button,
    Chip,
    CircularProgress,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    InputAdornment,
    InputLabel,
    MenuItem,
    Pagination,
    Paper,
    Select,
    Switch,
    TextField,
    Typography,
} from "@mui/material";
import { Clock3, Pencil, Plus, Search, Scissors, Sparkles, Trash2, UsersRound, WalletCards } from "lucide-react";
import { useCallback, useEffect, useMemo, useState } from "react";
import type { FormEvent } from "react";
import { toast } from "react-toastify";
import { useAuth } from "../../auth/AuthContext";
import { defaultAvatarUrl, logoUrl } from "../../constants/assets";
import { readableApiError } from "../../services/api/client";
import { createService, deleteService, fetchServiceOptions, fetchServices, updateService } from "../../services/api/services";
import type { SalonService, ServiceInput, ServiceOptions } from "../../types/service";
import { formatPriceInput, parsePriceInput, priceInputRule } from "../../utils/priceInput";

const emptyForm: ServiceInput = {
    name: "",
    description: null,
    price: 0,
    duration_minutes: 30,
    is_active: true,
    category_ids: [],
    provider_membership_ids: [],
};

const numberFormat = new Intl.NumberFormat("fa-IR");

export default function Services() {
    const { user } = useAuth();
    const membership = user?.memberships[0];
    const canManage = user?.system_role === "super_admin"
        || membership?.role === "salon_owner"
        || membership?.permissions.includes("manage_services");
    const [services, setServices] = useState<SalonService[]>([]);
    const [options, setOptions] = useState<ServiceOptions>({ categories: [], providers: [] });
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [status, setStatus] = useState("");
    const [categoryId, setCategoryId] = useState("");
    const [providerId, setProviderId] = useState("");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<SalonService | null>(null);
    const [form, setForm] = useState<ServiceInput>(emptyForm);
    const [priceInput, setPriceInput] = useState("");
    const [categoryError, setCategoryError] = useState(false);
    const [saving, setSaving] = useState(false);
    const [deleting, setDeleting] = useState<SalonService | null>(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const result = await fetchServices({
                ...(query ? { search: query } : {}),
                ...(status ? { status } : {}),
                ...(categoryId ? { category_id: Number(categoryId) } : {}),
                ...(providerId ? { provider_membership_id: Number(providerId) } : {}),
                page,
            });
            setServices(result.data);
            setLastPage(result.meta.last_page);
            setTotal(result.meta.total);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, [categoryId, page, providerId, query, status]);

    useEffect(() => { void load(); }, [load]);
    useEffect(() => {
        void fetchServiceOptions().then(setOptions).catch((caught) => setError(readableApiError(caught)));
    }, []);

    const activeCategoryOptions = useMemo(() => options.categories.filter((item) => item.is_active || form.category_ids.includes(item.id)), [form.category_ids, options.categories]);

    function openCreate() {
        setEditing(null);
        setForm(emptyForm);
        setPriceInput("");
        setCategoryError(false);
        setDialogOpen(true);
    }

    function openEdit(service: SalonService) {
        setEditing(service);
        setForm({
            name: service.name,
            description: service.description,
            price: service.price,
            duration_minutes: service.duration_minutes,
            is_active: service.is_active,
            category_ids: service.categories.map((item) => item.id),
            provider_membership_ids: service.providers.map((item) => item.membership_id),
        });
        setPriceInput(formatPriceInput(service.price));
        setCategoryError(false);
        setDialogOpen(true);
    }

    async function save(event: FormEvent) {
        event.preventDefault();
        if (form.category_ids.length === 0) {
            setCategoryError(true);
            toast.error("حداقل یک دسته‌بندی انتخاب کنید.");
            return;
        }
        setSaving(true);
        try {
            if (editing) {
                await updateService(editing.id, form);
                toast.success("سرویس ویرایش شد.");
            } else {
                await createService(form);
                toast.success("سرویس ایجاد شد.");
            }
            setDialogOpen(false);
            await load();
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setSaving(false);
        }
    }

    async function confirmDelete() {
        if (!deleting) return;
        setSaving(true);
        try {
            await deleteService(deleting.id);
            toast.success("سرویس حذف شد.");
            setDeleting(null);
            await load();
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setSaving(false);
        }
    }

    return (
        <section className="services-page">
            <div className="page-heading users-heading">
                <div>
                    <span className="eyebrow">منوی حرفه‌ای سالن</span>
                    <Typography variant="h2" component="h1">سرویس‌ها</Typography>
                    <Typography color="text.secondary">قیمت، زمان، دسته‌بندی و اعضای ارائه‌دهنده هر سرویس را یکجا مدیریت کنید.</Typography>
                </div>
                {canManage && <Button variant="contained" onClick={openCreate}><Plus size={18} /> افزودن سرویس</Button>}
            </div>

            {!canManage && <Alert severity="info" className="service-readonly-note">دسترسی شما برای این بخش فقط مشاهده است.</Alert>}

            <Paper variant="outlined" className="service-toolbar">
                <form onSubmit={(event) => { event.preventDefault(); setPage(1); setQuery(search.trim()); }} className="users-search">
                    <TextField size="small" label="جستجوی نام سرویس" value={search} onChange={(event) => setSearch(event.target.value)} />
                    <Button type="submit" color="inherit"><Search size={18} /> جستجو</Button>
                </form>
                <FormControl size="small"><InputLabel>وضعیت</InputLabel><Select label="وضعیت" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}><MenuItem value="">همه</MenuItem><MenuItem value="active">فعال</MenuItem><MenuItem value="inactive">غیرفعال</MenuItem></Select></FormControl>
                <FormControl size="small"><InputLabel>دسته‌بندی</InputLabel><Select label="دسته‌بندی" value={categoryId} onChange={(event) => { setCategoryId(event.target.value); setPage(1); }}><MenuItem value="">همه</MenuItem>{options.categories.map((item) => <MenuItem key={item.id} value={item.id}>{item.name}</MenuItem>)}</Select></FormControl>
                <FormControl size="small"><InputLabel>ارائه‌دهنده</InputLabel><Select label="ارائه‌دهنده" value={providerId} onChange={(event) => { setProviderId(event.target.value); setPage(1); }}><MenuItem value="">همه</MenuItem>{options.providers.map((item) => <MenuItem key={item.membership_id} value={item.membership_id}>{item.name}</MenuItem>)}</Select></FormControl>
            </Paper>

            {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
            {loading ? <div className="users-loading"><CircularProgress aria-label="در حال دریافت سرویس‌ها" /></div> : services.length === 0 ? (
                <Paper variant="outlined" className="users-empty service-empty"><Scissors size={42} /><Typography variant="h3">سرویسی پیدا نشد</Typography><Typography color="text.secondary">فیلترها را تغییر دهید{canManage ? " یا اولین سرویس سالن را ایجاد کنید" : ""}.</Typography></Paper>
            ) : (
                <div className="service-grid">
                    {services.map((service) => (
                        <Paper variant="outlined" className="service-card" key={service.id}>
                            <div className={`service-card-cover${service.categories[0]?.image_url ? "" : " is-default"}`}>
                                <img src={service.categories[0]?.image_url || logoUrl} alt={service.categories[0]?.image_url ? `تصویر دسته‌بندی ${service.categories[0].name}` : "تصویر پیش‌فرض سرویس"} />
                                <div className="service-card-cover-badges">
                                    <Chip className={service.is_active ? "category-active-chip" : "category-inactive-chip"} label={service.is_active ? "فعال" : "غیرفعال"} size="small" />
                                    {service.categories[0] && <span>{service.categories[0].name}</span>}
                                </div>
                            </div>
                            <div className="service-card-title"><Typography variant="h3">{service.name}</Typography><Typography variant="body2" color="text.secondary">{service.description || "توضیحی برای این سرویس ثبت نشده است."}</Typography></div>
                            <div className="service-metrics">
                                <div><WalletCards size={18} /><span>قیمت</span><strong>{numberFormat.format(service.price)} تومان</strong></div>
                                <div><Clock3 size={18} /><span>مدت</span><strong>{numberFormat.format(service.duration_minutes)} دقیقه</strong></div>
                            </div>
                            <div className="service-section"><span>دسته‌بندی‌ها</span><div className="service-chip-list">{service.categories.map((item) => <Chip key={item.id} size="small" label={item.name} />)}</div></div>
                            <div className="service-section"><span><UsersRound size={15} /> ارائه‌دهندگان</span>{service.providers.length ? <div className="service-provider-list">{service.providers.map((provider) => <div key={provider.membership_id}><Avatar src={provider.image_url || defaultAvatarUrl}>{provider.name[0]}</Avatar><span>{provider.name}</span></div>)}</div> : <small>هنوز ارائه‌دهنده‌ای انتخاب نشده است.</small>}</div>
                            {canManage && <div className="service-actions"><Button color="inherit" onClick={() => openEdit(service)}><Pencil size={16} /> ویرایش</Button><Button color="error" onClick={() => setDeleting(service)}><Trash2 size={16} /> حذف</Button></div>}
                        </Paper>
                    ))}
                </div>
            )}

            {!loading && total > 0 && <div className="users-pagination"><span>{numberFormat.format(total)} سرویس</span><Pagination count={lastPage} page={page} onChange={(_event, value) => setPage(value)} color="primary" /></div>}

            <Dialog open={dialogOpen} onClose={() => !saving && setDialogOpen(false)} fullWidth maxWidth="md">
                <form onSubmit={save}>
                    <DialogTitle>{editing ? `ویرایش «${editing.name}»` : "ایجاد سرویس جدید"}</DialogTitle>
                    <DialogContent className="service-form">
                        <div className="service-form-intro"><span><Scissors size={22} /></span><div><strong>مشخصات سرویس</strong><small>اطلاعاتی را وارد کنید که برای مدیریت سالن و رزرو مشتری قابل استفاده باشد.</small></div></div>
                        <TextField label="نام سرویس" required value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
                        <div className="service-form-row">
                            <TextField label="قیمت" required value={priceInput} onChange={(event) => { const price = parsePriceInput(event.target.value); setPriceInput(event.target.value ? formatPriceInput(price) : ""); setForm({ ...form, price }); }} slotProps={{ input: { endAdornment: <InputAdornment position="end">تومان</InputAdornment> }, htmlInput: priceInputRule }} />
                            <TextField label="مدت‌زمان" required type="number" value={form.duration_minutes} onChange={(event) => setForm({ ...form, duration_minutes: Math.max(5, Number(event.target.value)) })} slotProps={{ input: { endAdornment: <InputAdornment position="end">دقیقه</InputAdornment> }, htmlInput: { min: 5, max: 1440, step: 5 } }} />
                        </div>
                        <Autocomplete multiple options={activeCategoryOptions} getOptionLabel={(option) => option.name} value={activeCategoryOptions.filter((item) => form.category_ids.includes(item.id))} onChange={(_event, value) => { setCategoryError(value.length === 0); setForm({ ...form, category_ids: value.map((item) => item.id) }); }} renderInput={(params) => <TextField {...params} error={categoryError} label="دسته‌بندی‌ها" helperText={categoryError ? "حداقل یک دسته‌بندی انتخاب کنید." : "امکان انتخاب چند دسته‌بندی وجود دارد."} />} />
                        <Autocomplete multiple options={options.providers} getOptionLabel={(option) => option.name} value={options.providers.filter((item) => form.provider_membership_ids.includes(item.membership_id))} onChange={(_event, value) => setForm({ ...form, provider_membership_ids: value.map((item) => item.membership_id) })} renderInput={(params) => <TextField {...params} label="کارکنان ارائه‌دهنده" helperText="انتخاب ارائه‌دهنده اختیاری است و بعداً نیز قابل تغییر خواهد بود." />} />
                        <TextField className="service-description" label="توضیحات (اختیاری)" multiline minRows={3} value={form.description ?? ""} onChange={(event) => setForm({ ...form, description: event.target.value || null })} />
                        <label className="category-switch"><Switch checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} /> سرویس فعال و قابل ارائه باشد</label>
                    </DialogContent>
                    <DialogActions><Button color="inherit" disabled={saving} onClick={() => setDialogOpen(false)}>انصراف</Button><Button type="submit" variant="contained" disabled={saving}>{saving ? "در حال ذخیره…" : "ذخیره سرویس"}</Button></DialogActions>
                </form>
            </Dialog>

            <Dialog open={Boolean(deleting)} onClose={() => !saving && setDeleting(null)} fullWidth maxWidth="xs">
                <DialogTitle>حذف سرویس</DialogTitle>
                <DialogContent className="service-delete-content"><Alert severity="warning">سرویس «{deleting?.name}» به‌صورت نرم حذف می‌شود و در فهرست و رزروهای جدید نمایش داده نخواهد شد.</Alert><Typography>ارتباط با دسته‌بندی‌ها و کارکنان برای حفظ تاریخچه باقی می‌ماند.</Typography></DialogContent>
                <DialogActions><Button color="inherit" disabled={saving} onClick={() => setDeleting(null)}>انصراف</Button><Button color="error" variant="contained" disabled={saving} onClick={() => void confirmDelete()}>{saving ? "در حال حذف…" : "حذف سرویس"}</Button></DialogActions>
            </Dialog>
        </section>
    );
}
