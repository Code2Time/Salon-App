import {
    Alert,
    Avatar,
    Button,
    Checkbox,
    Chip,
    CircularProgress,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    FormControlLabel,
    InputLabel,
    MenuItem,
    Pagination,
    Paper,
    Select,
    Switch,
    TextField,
    Typography,
} from "@mui/material";
import { ImagePlus, Pencil, Plus, Search, Trash2, UserRoundCog } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import type { FormEvent } from "react";
import { toast } from "react-toastify";
import { readableApiError } from "../../services/api/client";
import {
    createPanelUser,
    deletePanelUser,
    fetchPanelUsers,
    updatePanelUser,
} from "../../services/api/panelUsers";
import type { PanelPermission, SalonRole } from "../../types/auth";
import type { PanelUser, PanelUserInput } from "../../types/panelUser";
import { defaultAvatarUrl } from "../../constants/assets";

const roleLabels: Record<SalonRole, string> = {
    salon_owner: "مدیر سالن",
    employee: "کارکنان",
    customer: "مشتری",
};

const assignableRoles: SalonRole[] = ["salon_owner", "employee", "customer"];
const permissionOptions: Array<{ value: PanelPermission; label: string }> = [
    { value: "dashboard", label: "داشبورد" },
    { value: "service_categories", label: "مشاهده دسته‌بندی خدمات" },
    { value: "manage_service_categories", label: "مدیریت دسته‌بندی خدمات" },
    { value: "services", label: "خدمات" },
    { value: "appointments", label: "نوبت‌ها" },
    { value: "products", label: "محصولات" },
    { value: "reports", label: "گزارش‌ها" },
];

interface UsersProps {
    fixedRole?: Extract<SalonRole, "employee" | "customer">;
    title?: string;
    eyebrow?: string;
    description?: string;
}

const emptyForm: PanelUserInput & { mobile: string } = {
    first_name: "",
    last_name: "",
    mobile: "",
    email: null,
    role: "customer",
    permissions: [],
    is_active: true,
};

export default function Users({
    fixedRole,
    title = "کاربران پنل",
    eyebrow = "تیم مدیریتی سالن",
    description = "مدیران، کارکنان و مشتریان سالن را مدیریت کنید.",
}: UsersProps) {
    const [users, setUsers] = useState<PanelUser[]>([]);
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [role, setRole] = useState("");
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [editing, setEditing] = useState<PanelUser | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [form, setForm] = useState(emptyForm);
    const [saving, setSaving] = useState(false);
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const effectiveRole = fixedRole ?? role;
    const entityLabel = fixedRole === "customer"
        ? "مشتری"
        : fixedRole === "employee"
            ? "کارمند"
            : "کاربر پنل";
    const emptyTitle = fixedRole === "customer"
        ? "مشتری‌ای پیدا نشد"
        : fixedRole === "employee"
            ? "کارمندی پیدا نشد"
            : "کاربر پنلی پیدا نشد";

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const result = await fetchPanelUsers({
                ...(query ? { search: query } : {}),
                ...(effectiveRole ? { role: effectiveRole } : {}),
                ...(status ? { status } : {}),
                page,
            });
            setUsers(result.data);
            setLastPage(result.meta.last_page);
            setTotal(result.meta.total);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, [effectiveRole, page, query, status]);

    useEffect(() => { void load(); }, [load]);
    useEffect(() => () => {
        if (imagePreview?.startsWith("blob:")) URL.revokeObjectURL(imagePreview);
    }, [imagePreview]);

    function openCreate() {
        setEditing(null);
        const nextRole = fixedRole ?? emptyForm.role;
        setForm({
            ...emptyForm,
            role: nextRole,
            permissions: nextRole === "employee" ? ["dashboard", "appointments"] : [],
        });
        setImagePreview(null);
        setDialogOpen(true);
    }

    function openEdit(user: PanelUser) {
        setEditing(user);
        setImagePreview(user.image_url);
        setForm({
            first_name: user.first_name ?? "",
            last_name: user.last_name ?? "",
            mobile: user.mobile,
            email: user.email,
            role: fixedRole ?? user.role,
            permissions: user.permissions,
            is_active: user.is_active,
        });
        setDialogOpen(true);
    }

    async function save(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        try {
            if (editing) {
                const { mobile: _mobile, ...input } = form;
                await updatePanelUser(editing.id, input);
                toast.success(`اطلاعات ${entityLabel} ویرایش شد.`);
            } else {
                await createPanelUser(form);
                toast.success(`${entityLabel} به سالن اضافه شد.`);
            }
            setDialogOpen(false);
            await load();
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setSaving(false);
        }
    }

    async function remove(user: PanelUser) {
        if (!window.confirm(`${entityLabel} «${user.name}» حذف شود؟ این عملیات به‌صورت حذف نرم انجام می‌شود.`)) return;
        try {
            await deletePanelUser(user.id);
            toast.success(`${entityLabel} حذف شد.`);
            await load();
        } catch (caught) {
            toast.error(readableApiError(caught));
        }
    }

    return (
        <section>
            <div className="page-heading users-heading">
                <div>
                    <span className="eyebrow">{eyebrow}</span>
                    <Typography variant="h2" component="h1">{title}</Typography>
                    <Typography color="text.secondary">{description}</Typography>
                </div>
                <Button variant="contained" onClick={openCreate}><Plus size={18} /> افزودن {entityLabel}</Button>
            </div>

            <Paper variant="outlined" className="users-toolbar">
                <form onSubmit={(event) => { event.preventDefault(); setPage(1); setQuery(search.trim()); }} className="users-search">
                    <TextField size="small" label="جستجوی نام، موبایل یا ایمیل" value={search} onChange={(event) => setSearch(event.target.value)} />
                    <Button type="submit" color="inherit"><Search size={18} /> جستجو</Button>
                </form>
                {fixedRole ? (
                    <div className="fixed-role-filter"><span>نقش</span><strong>{roleLabels[fixedRole]}</strong></div>
                ) : (
                    <FormControl size="small">
                        <InputLabel>نقش</InputLabel>
                        <Select label="نقش" value={role} onChange={(event) => { setRole(event.target.value); setPage(1); }}>
                            <MenuItem value="">همه نقش‌ها</MenuItem>
                            {assignableRoles.map((value) => <MenuItem key={value} value={value}>{roleLabels[value]}</MenuItem>)}
                        </Select>
                    </FormControl>
                )}
                <FormControl size="small">
                    <InputLabel>وضعیت</InputLabel>
                    <Select label="وضعیت" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}>
                        <MenuItem value="">همه وضعیت‌ها</MenuItem>
                        <MenuItem value="active">فعال</MenuItem>
                        <MenuItem value="inactive">غیرفعال</MenuItem>
                    </Select>
                </FormControl>
            </Paper>

            {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
            {loading ? <div className="users-loading"><CircularProgress aria-label="در حال دریافت کاربران" /></div> : users.length === 0 ? (
                <Paper variant="outlined" className="users-empty"><UserRoundCog size={38} /><Typography variant="h3">{emptyTitle}</Typography><Typography color="text.secondary">فیلترها را تغییر دهید یا اولین {entityLabel} را اضافه کنید.</Typography></Paper>
            ) : (
                <div className="users-list">
                    {users.map((user) => (
                        <Paper variant="outlined" className="user-row" key={user.id}>
                            <Avatar src={user.image_url ?? defaultAvatarUrl} alt={user.name} />
                            <div className="user-identity"><strong>{user.name}</strong><span dir="ltr">{user.mobile}</span></div>
                            <Chip label={roleLabels[user.role]} size="small" color={user.role === "salon_owner" ? "secondary" : "default"} />
                            <Chip label={user.is_active ? "فعال" : "غیرفعال"} size="small" color={user.is_active ? "success" : "default"} variant="outlined" />
                            <div className="user-actions">
                                <Button color="inherit" onClick={() => openEdit(user)} aria-label={`ویرایش ${user.name}`}><Pencil size={17} /> ویرایش</Button>
                                <Button color="error" onClick={() => void remove(user)} aria-label={`حذف ${user.name}`}><Trash2 size={17} /> حذف</Button>
                            </div>
                        </Paper>
                    ))}
                </div>
            )}

            {!loading && total > 0 && <div className="users-pagination"><span>{total} کاربر</span><Pagination count={lastPage} page={page} onChange={(_event, value) => setPage(value)} color="primary" /></div>}

            <Dialog open={dialogOpen} onClose={() => !saving && setDialogOpen(false)} fullWidth maxWidth="sm">
                <form onSubmit={save}>
                    <DialogTitle>{editing ? `ویرایش ${entityLabel}` : `افزودن ${entityLabel}`}</DialogTitle>
                    <DialogContent className="user-form">
                        <div className="user-image-field">
                            <Avatar src={imagePreview ?? defaultAvatarUrl} alt="پیش‌نمایش تصویر کاربر" />
                            <Button component="label" color="inherit">
                                <ImagePlus size={18} /> انتخاب تصویر
                                <input
                                    hidden
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(event) => {
                                        const file = event.target.files?.[0];
                                        if (!file) return;
                                        setForm({ ...form, image: file });
                                        setImagePreview(URL.createObjectURL(file));
                                    }}
                                />
                            </Button>
                            <span>JPG، PNG یا WebP؛ حداکثر ۲ مگابایت</span>
                        </div>
                        <TextField label="نام" required value={form.first_name} onChange={(event) => setForm({ ...form, first_name: event.target.value })} />
                        <TextField label="نام خانوادگی" required value={form.last_name} onChange={(event) => setForm({ ...form, last_name: event.target.value })} />
                        <TextField label="شماره موبایل" required disabled={Boolean(editing)} value={form.mobile} onChange={(event) => setForm({ ...form, mobile: event.target.value })} slotProps={{ htmlInput: { dir: "ltr", maxLength: 11, inputMode: "tel" } }} />
                        <TextField label="ایمیل (اختیاری)" type="email" value={form.email ?? ""} onChange={(event) => setForm({ ...form, email: event.target.value || null })} slotProps={{ htmlInput: { dir: "ltr" } }} />
                        {fixedRole ? (
                            <TextField label="نقش" value={roleLabels[fixedRole]} disabled />
                        ) : (
                            <FormControl>
                                <InputLabel>نقش</InputLabel>
                                <Select label="نقش" value={form.role} onChange={(event) => {
                                    const nextRole = event.target.value as SalonRole;
                                    setForm({
                                        ...form,
                                        role: nextRole,
                                        permissions: nextRole === "employee" ? ["dashboard", "appointments"] : [],
                                    });
                                }}>
                                    {assignableRoles.map((value) => <MenuItem key={value} value={value}>{roleLabels[value]}</MenuItem>)}
                                </Select>
                            </FormControl>
                        )}
                        {form.role === "employee" && (
                            <fieldset className="user-permissions">
                                <legend>دسترسی کارکنان به منوها</legend>
                                {permissionOptions.map((option) => (
                                    <FormControlLabel
                                        key={option.value}
                                        label={option.label}
                                        control={
                                            <Checkbox
                                                checked={form.permissions.includes(option.value)}
                                                onChange={(event) => {
                                                    let permissions = event.target.checked
                                                        ? [...form.permissions, option.value]
                                                        : form.permissions.filter((permission) => permission !== option.value);
                                                    if (option.value === "manage_service_categories" && event.target.checked) {
                                                        permissions = [...permissions, "service_categories"];
                                                    }
                                                    if (option.value === "service_categories" && !event.target.checked) {
                                                        permissions = permissions.filter((permission) => permission !== "manage_service_categories");
                                                    }
                                                    setForm({ ...form, permissions: [...new Set(permissions)] });
                                                }}
                                            />
                                        }
                                    />
                                ))}
                            </fieldset>
                        )}
                        <label className="user-active-switch"><Switch checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} /> عضویت فعال باشد</label>
                    </DialogContent>
                    <DialogActions><Button color="inherit" onClick={() => setDialogOpen(false)} disabled={saving}>انصراف</Button><Button type="submit" variant="contained" disabled={saving}>{saving ? "در حال ذخیره…" : "ذخیره"}</Button></DialogActions>
                </form>
            </Dialog>
        </section>
    );
}
