import {
    Alert,
    Button,
    Chip,
    CircularProgress,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    InputLabel,
    MenuItem,
    Pagination,
    Paper,
    Select,
    Switch,
    TextField,
    Typography,
} from "@mui/material";
import { Eye, ImagePlus, Pencil, Plus, Search, Shapes, Sparkles, Trash2 } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import type { FormEvent } from "react";
import { toast } from "react-toastify";
import { useAuth } from "../../auth/AuthContext";
import { readableApiError } from "../../services/api/client";
import {
    createServiceCategory,
    deleteServiceCategory,
    fetchCategoryDeletionImpact,
    fetchServiceCategory,
    fetchServiceCategories,
    updateServiceCategory,
} from "../../services/api/serviceCategories";
import type { CategoryDeletionImpact, ServiceCategory, ServiceCategoryInput } from "../../types/serviceCategory";

const emptyForm: ServiceCategoryInput = {
    name: "",
    description: null,
    is_featured: false,
    is_active: true,
    sort_order: 0,
};

export default function ServiceCategories() {
    const { user } = useAuth();
    const membership = user?.memberships[0];
    const canManage = user?.system_role === "super_admin"
        || membership?.role === "salon_owner"
        || membership?.permissions.includes("manage_service_categories");
    const [categories, setCategories] = useState<ServiceCategory[]>([]);
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [status, setStatus] = useState("");
    const [featured, setFeatured] = useState("");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<ServiceCategory | null>(null);
    const [form, setForm] = useState<ServiceCategoryInput>(emptyForm);
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);
    const [deleteImpact, setDeleteImpact] = useState<CategoryDeletionImpact | null>(null);
    const [deleteLoading, setDeleteLoading] = useState(false);
    const [details, setDetails] = useState<ServiceCategory | null>(null);
    const [detailsLoading, setDetailsLoading] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const result = await fetchServiceCategories({
                ...(query ? { search: query } : {}),
                ...(status ? { status } : {}),
                ...(featured ? { featured: featured === "yes" } : {}),
                page,
            });
            setCategories(result.data);
            setLastPage(result.meta.last_page);
            setTotal(result.meta.total);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, [featured, page, query, status]);

    useEffect(() => { void load(); }, [load]);
    useEffect(() => () => {
        if (imagePreview?.startsWith("blob:")) URL.revokeObjectURL(imagePreview);
    }, [imagePreview]);

    function openCreate() {
        setEditing(null);
        setForm(emptyForm);
        setImagePreview(null);
        setDialogOpen(true);
    }

    function openEdit(category: ServiceCategory) {
        setEditing(category);
        setForm({
            name: category.name,
            description: category.description,
            is_featured: category.is_featured,
            is_active: category.is_active,
            sort_order: category.sort_order,
        });
        setImagePreview(category.image_url);
        setDialogOpen(true);
    }

    async function save(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        try {
            if (editing) {
                await updateServiceCategory(editing.id, form);
                toast.success("دسته‌بندی ویرایش شد.");
            } else {
                await createServiceCategory(form);
                toast.success("دسته‌بندی ایجاد شد.");
            }
            setDialogOpen(false);
            await load();
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setSaving(false);
        }
    }

    async function prepareDelete(category: ServiceCategory) {
        setDeleteLoading(true);
        try {
            setDeleteImpact(await fetchCategoryDeletionImpact(category.id));
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setDeleteLoading(false);
        }
    }

    async function openDetails(category: ServiceCategory) {
        setDetailsLoading(true);
        try {
            setDetails(await fetchServiceCategory(category.id));
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setDetailsLoading(false);
        }
    }

    async function confirmDelete() {
        if (!deleteImpact) return;
        setDeleteLoading(true);
        try {
            await deleteServiceCategory(deleteImpact.category.id);
            toast.success("دسته‌بندی حذف شد.");
            setDeleteImpact(null);
            await load();
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setDeleteLoading(false);
        }
    }

    return (
        <section>
            <div className="page-heading users-heading">
                <div>
                    <span className="eyebrow">ساختار سرویس‌های سالن</span>
                    <Typography variant="h2" component="h1">دسته‌بندی سرویس‌ها</Typography>
                    <Typography color="text.secondary">سرویس‌های مرتبط را در گروه‌های واضح و قابل نمایش برای مشتریان مرتب کنید.</Typography>
                </div>
                {canManage && <Button variant="contained" onClick={openCreate}><Plus size={18} /> افزودن دسته‌بندی</Button>}
            </div>

            {!canManage && <Alert severity="info" className="category-readonly-note">دسترسی شما فقط برای مشاهده دسته‌بندی‌هاست.</Alert>}

            <Paper variant="outlined" className="category-toolbar">
                <form onSubmit={(event) => { event.preventDefault(); setPage(1); setQuery(search.trim()); }} className="users-search">
                    <TextField size="small" label="جستجوی نام دسته‌بندی" value={search} onChange={(event) => setSearch(event.target.value)} />
                    <Button type="submit" color="inherit"><Search size={18} /> جستجو</Button>
                </form>
                <FormControl size="small">
                    <InputLabel>وضعیت</InputLabel>
                    <Select label="وضعیت" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}>
                        <MenuItem value="">همه</MenuItem><MenuItem value="active">فعال</MenuItem><MenuItem value="inactive">غیرفعال</MenuItem>
                    </Select>
                </FormControl>
                <FormControl size="small">
                    <InputLabel>نمایش ویژه</InputLabel>
                    <Select label="نمایش ویژه" value={featured} onChange={(event) => { setFeatured(event.target.value); setPage(1); }}>
                        <MenuItem value="">همه</MenuItem><MenuItem value="yes">ویژه</MenuItem><MenuItem value="no">عادی</MenuItem>
                    </Select>
                </FormControl>
            </Paper>

            {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
            {loading ? <div className="users-loading"><CircularProgress aria-label="در حال دریافت دسته‌بندی‌ها" /></div> : categories.length === 0 ? (
                <Paper variant="outlined" className="users-empty"><Shapes size={40} /><Typography variant="h3">دسته‌بندی‌ای پیدا نشد</Typography><Typography color="text.secondary">فیلترها را تغییر دهید{canManage ? " یا اولین دسته‌بندی را ایجاد کنید" : ""}.</Typography></Paper>
            ) : (
                <div className="category-grid">
                    {categories.map((category) => (
                        <Paper variant="outlined" className="category-card" key={category.id}>
                            <div className="category-cover">
                                {category.image_url ? <img src={category.image_url} alt="" /> : <Shapes size={42} strokeWidth={1.2} />}
                                <div className="category-badges">
                                    {category.is_featured && <Chip className="category-featured-chip" icon={<Sparkles size={14} />} label="ویژه" size="small" />}
                                    <Chip className={category.is_active ? "category-active-chip" : "category-inactive-chip"} label={category.is_active ? "فعال" : "غیرفعال"} size="small" />
                                </div>
                            </div>
                            <div className="category-card-body">
                                <div><Typography variant="h3">{category.name}</Typography><span>{category.services_count} سرویس · ترتیب {category.sort_order}</span></div>
                                <Typography color="text.secondary" variant="body2">{category.description || "توضیحی برای این دسته‌بندی ثبت نشده است."}</Typography>
                                <div className="category-actions">
                                    <Button color="inherit" disabled={detailsLoading} onClick={() => void openDetails(category)}><Eye size={16} /> جزئیات</Button>
                                    {canManage && <>
                                        <Button color="inherit" onClick={() => openEdit(category)}><Pencil size={16} /> ویرایش</Button>
                                        <Button color="error" disabled={deleteLoading} onClick={() => void prepareDelete(category)}><Trash2 size={16} /> حذف</Button>
                                    </>}
                                </div>
                            </div>
                        </Paper>
                    ))}
                </div>
            )}

            {!loading && total > 0 && <div className="users-pagination"><span>{total} دسته‌بندی</span><Pagination count={lastPage} page={page} onChange={(_event, value) => setPage(value)} color="primary" /></div>}

            <Dialog open={dialogOpen} onClose={() => !saving && setDialogOpen(false)} fullWidth maxWidth="sm">
                <form onSubmit={save}>
                    <DialogTitle>{editing ? "ویرایش دسته‌بندی" : "ایجاد دسته‌بندی"}</DialogTitle>
                    <DialogContent className="category-form">
                        <div className="category-image-field">
                            <div className="category-image-preview">{imagePreview ? <img src={imagePreview} alt="پیش‌نمایش دسته‌بندی" /> : <Shapes size={34} />}</div>
                            <div><Button component="label" color="inherit"><ImagePlus size={17} /> انتخاب تصویر<input hidden type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => { const file = event.target.files?.[0]; if (!file) return; setForm({ ...form, image: file }); setImagePreview(URL.createObjectURL(file)); }} /></Button><span>JPG، PNG یا WebP؛ حداکثر ۲ مگابایت</span></div>
                        </div>
                        <TextField label="نام دسته‌بندی" required value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
                        <TextField label="ترتیب نمایش" type="number" value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: Math.max(0, Number(event.target.value)) })} slotProps={{ htmlInput: { min: 0, max: 65535 } }} />
                        <TextField className="category-description" label="توضیحات (اختیاری)" multiline minRows={3} value={form.description ?? ""} onChange={(event) => setForm({ ...form, description: event.target.value || null })} />
                        <label className="category-switch"><Switch checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} /> فعال و قابل نمایش باشد</label>
                        <label className="category-switch"><Switch checked={form.is_featured} onChange={(event) => setForm({ ...form, is_featured: event.target.checked })} /> دسته‌بندی ویژه باشد</label>
                    </DialogContent>
                    <DialogActions><Button color="inherit" disabled={saving} onClick={() => setDialogOpen(false)}>انصراف</Button><Button type="submit" variant="contained" disabled={saving}>{saving ? "در حال ذخیره…" : "ذخیره"}</Button></DialogActions>
                </form>
            </Dialog>

            <Dialog open={Boolean(details)} onClose={() => setDetails(null)} fullWidth maxWidth="sm">
                <DialogTitle>جزئیات دسته‌بندی «{details?.name}»</DialogTitle>
                <DialogContent className="category-details-content">
                    {details?.description && <Typography color="text.secondary">{details.description}</Typography>}
                    <div className="category-details-meta">
                        <Chip className={details?.is_active ? "category-active-chip" : "category-inactive-chip"} label={details?.is_active ? "فعال" : "غیرفعال"} size="small" />
                        {details?.is_featured && <Chip className="category-featured-chip" icon={<Sparkles size={14} />} label="ویژه" size="small" />}
                        <span>ترتیب نمایش: {details?.sort_order}</span>
                    </div>
                    <Typography variant="h3" component="h3">سرویس‌های مرتبط</Typography>
                    {details?.services?.length ? (
                        <div className="category-service-list">
                            {details.services.map((service) => (
                                <div key={service.id}>
                                    <span><strong>{service.name}</strong><small>{service.duration_minutes} دقیقه</small></span>
                                    <Chip label={service.is_active ? "فعال" : "غیرفعال"} color={service.is_active ? "success" : "default"} variant="outlined" size="small" />
                                </div>
                            ))}
                        </div>
                    ) : <div className="category-services-empty"><Shapes size={28} /><span>هنوز سرویسی به این دسته‌بندی متصل نشده است.</span></div>}
                </DialogContent>
                <DialogActions><Button color="inherit" onClick={() => setDetails(null)}>بستن</Button></DialogActions>
            </Dialog>

            <Dialog open={Boolean(deleteImpact)} onClose={() => !deleteLoading && setDeleteImpact(null)} fullWidth maxWidth="sm">
                <DialogTitle>حذف دسته‌بندی «{deleteImpact?.category.name}»</DialogTitle>
                <DialogContent className="category-delete-content">
                    {deleteImpact?.services.length ? <>
                        <Alert severity="warning">ارتباط این دسته‌بندی با سرویس‌های زیر حذف می‌شود. سرویس‌های بدون دسته‌بندی دیگر نیز به‌صورت نرم حذف خواهند شد.</Alert>
                        <ul>{deleteImpact.services.map((service) => <li key={service.id}><strong>{service.name}</strong><span>{service.will_be_deleted ? "حذف نرم سرویس" : "باقی‌ماندن در دسته‌های دیگر"}</span></li>)}</ul>
                    </> : <Typography color="text.secondary">این دسته‌بندی سرویسی ندارد و با حذف آن، سرویس دیگری تغییر نمی‌کند.</Typography>}
                </DialogContent>
                <DialogActions><Button color="inherit" disabled={deleteLoading} onClick={() => setDeleteImpact(null)}>انصراف</Button><Button color="error" variant="contained" disabled={deleteLoading} onClick={() => void confirmDelete()}>{deleteLoading ? "در حال حذف…" : "تأیید حذف"}</Button></DialogActions>
            </Dialog>
        </section>
    );
}
