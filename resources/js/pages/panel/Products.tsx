import { Alert, Button, Chip, CircularProgress, Dialog, DialogActions, DialogContent, DialogTitle, InputAdornment, MenuItem, Pagination, Paper, TextField, Typography } from "@mui/material";
import { Boxes, ImageOff, Package, Pencil, Plus, Search, Scissors, Sparkles, Trash2 } from "lucide-react";
import { useCallback, useEffect, useRef, useState } from "react";
import { useAuth } from "../../auth/AuthContext";
import ProductDraftForm from "../../components/forms/ProductDraftForm";
import { fetchProducts, fetchProductServices, saveProduct, deleteProduct } from "../../services/api/products";
import { toast } from "react-toastify";
import { readableApiError } from "../../services/api/client";
import type { ProductDraft, ProductServiceOption } from "../../types/product";
import { formatPriceInput } from "../../utils/priceInput";

export default function Products() {
    const { user } = useAuth();
    const canManage = user?.system_role === "super_admin" || user?.memberships[0]?.role === "salon_owner";
    const [products, setProducts] = useState<ProductDraft[]>([]);
    const [editing, setEditing] = useState<ProductDraft | null>(null);
    const [deleting, setDeleting] = useState<ProductDraft | null>(null);
    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("all");
    const [stock, setStock] = useState("all");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const [query, setQuery] = useState("");
    const loadVersion = useRef(0);
    const [services, setServices] = useState<ProductServiceOption[]>([]);
    const [servicesLoading, setServicesLoading] = useState(false);
    const [servicesRequested, setServicesRequested] = useState(false);
    const [servicesError, setServicesError] = useState<string | null>(null);

    const loadServices = useCallback(async () => {
        setServicesRequested(true);
        setServicesLoading(true);
        setServicesError(null);
        try {
            setServices(await fetchProductServices());
        } catch (error) {
            setServicesError(readableApiError(error));
        } finally {
            setServicesLoading(false);
        }
    }, []);

    useEffect(() => { if (editing && !servicesRequested) void loadServices(); }, [editing, servicesRequested, loadServices]);

    useEffect(() => { const timer = setTimeout(() => { setQuery(search.trim()); setPage(1); }, 300); return () => clearTimeout(timer); }, [search]);
    const load = useCallback(async () => {
        const version = ++loadVersion.current;
        setLoading(true);
        setError(null);
        try {
            const result = await fetchProducts({ page, search: query, ...(status !== "all" ? { status } : {}), ...(stock !== "all" ? { stock } : {}) });
            if (version !== loadVersion.current) return;
            if (page > result.meta.last_page) { setPage(Math.max(1, result.meta.last_page)); return; }
            setProducts(result.data);
            setTotal(result.meta.total);
            setLastPage(result.meta.last_page);
        } catch (caught) { if (version === loadVersion.current) setError(readableApiError(caught)); } finally { if (version === loadVersion.current) setLoading(false); }
    }, [page, query, status, stock]);
    useEffect(() => { void load(); }, [load]);
    const visible = products;

    function create() {
        setEditing({ id: 0, name: "", description: "", price: 0, stock: 0, is_active: true, image_url: null, recommendations: [] });
    }

    async function save(draft: ProductDraft) {
        await saveProduct(draft);
        setEditing(null);
        toast.success("محصول ذخیره شد.");
        if (page !== 1) setPage(1); else await load();
    }

    async function remove() {
        if (!deleting || busy) return;
        setBusy(true);
        try { await deleteProduct(deleting.id); setDeleting(null); await load(); toast.success("محصول حذف شد."); } catch (caught) { toast.error(readableApiError(caught)); } finally { setBusy(false); }
    }

    return (
        <section className="products-page">
            <Paper variant="outlined" className="products-hero"><span className="products-hero-icon"><Package size={32} /></span><div><span className="eyebrow">مراقبت، فراتر از سالن</span><Typography component="h1" variant="h2">محصولات سالن</Typography><Typography color="text.secondary">محصولات مراقبتی را معرفی کنید و برای هر خدمت، پیشنهاد مناسب بسازید.</Typography></div>{canManage && <Button variant="contained" onClick={create}><Plus size={18} /> محصول جدید</Button>}</Paper>
            {error && <Alert severity="error" action={<Button onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
            <div className="products-metrics">
                {[{ label: "نتایج", value: total, icon: Package }, { label: "فعال در این صفحه", value: products.filter((item) => item.is_active).length, icon: Boxes }, { label: "دارای پیشنهاد در این صفحه", value: products.filter((item) => item.recommendations.some((link) => link.recommendation_enabled)).length, icon: Sparkles }].map(({ label, value, icon: Icon }) => <Paper key={label} variant="outlined"><Icon size={17} /><span>{label}<strong>{formatPriceInput(value)}</strong></span></Paper>)}
            </div>
            <Paper variant="outlined" className="products-filters">
                <TextField label="جستجوی نام محصول" size="small" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} slotProps={{ input: { startAdornment: <InputAdornment position="start"><Search size={18} /></InputAdornment> } }} />
                <TextField select label="وضعیت" size="small" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}><MenuItem value="all">همه وضعیت‌ها</MenuItem><MenuItem value="active">فعال</MenuItem><MenuItem value="inactive">غیرفعال</MenuItem></TextField>
                <TextField select label="موجودی" size="small" value={stock} onChange={(event) => { setStock(event.target.value); setPage(1); }}><MenuItem value="all">همه محصولات</MenuItem><MenuItem value="available">موجود</MenuItem><MenuItem value="empty">ناموجود</MenuItem></TextField>
            </Paper>
            {loading ? <div className="shop-loading"><CircularProgress aria-label="دریافت محصولات" /></div> : error ? null : visible.length ? <div className="products-grid">{visible.map((product) => (
                <Paper variant="outlined" key={product.id} className="product-card">
                    <div className="product-card-image">{product.image_url ? <img src={product.image_url} alt={product.name} /> : <ImageOff size={26} />}</div>
                    <div className="product-card-content"><div className="product-card-heading"><Typography variant="h3" component="h2">{product.name}</Typography><Chip label={product.is_active ? "فعال" : "غیرفعال"} color={product.is_active ? "success" : "default"} size="small" /></div><p>{product.description || "توضیحی برای این محصول ثبت نشده است."}</p>
                        <div className="product-card-facts"><span>قیمت<strong>{formatPriceInput(product.price)} <small>تومان</small></strong></span><span>موجودی<strong>{product.stock > 0 ? `${formatPriceInput(product.stock)} عدد` : "ناموجود"}</strong></span></div>
                        <div className="product-card-links"><Scissors size={16} /><span>{product.recommendations.length ? `${formatPriceInput(product.recommendations.length)} خدمت مرتبط` : "بدون خدمت مرتبط"}</span></div>
                        <div className="product-service-chips">{product.recommendations.slice(0, 2).map((item) => <Chip size="small" key={item.service_id} label={`${item.service_name}${item.recommendation_enabled && item.discount_enabled ? ` · ${formatPriceInput(item.discount_percent)}٪ تخفیف` : ""}`} />)}{product.recommendations.length > 2 && <Chip size="small" label={`+${formatPriceInput(product.recommendations.length - 2)} خدمت`} />}</div>
                        {canManage && <div className="product-card-actions"><Button size="small" onClick={() => setEditing(product)}><Pencil size={15} /> ویرایش</Button><Button size="small" color="error" onClick={() => setDeleting(product)}><Trash2 size={15} /> حذف</Button></div>}
                    </div>
                </Paper>
            ))}</div> : <Paper variant="outlined" className="products-empty"><span><Package size={28} /></span><Typography variant="h3">محصولی پیدا نشد</Typography><Typography color="text.secondary">محصول جدید اضافه کنید یا فیلترها را تغییر دهید.</Typography>{canManage && <Button variant="outlined" onClick={create}><Plus size={17} /> افزودن محصول</Button>}<Button onClick={() => { setSearch(""); setStatus("all"); setStock("all"); setPage(1); }}>پاک‌کردن فیلترها</Button></Paper>}
            {lastPage > 1 && <Pagination className="products-pagination" count={lastPage} page={page} onChange={(_, value) => setPage(value)} color="primary" />}
            {editing && <ProductDraftForm initial={editing} services={services} servicesLoading={servicesLoading} servicesError={servicesError} onRetryServices={() => void loadServices()} onClose={() => setEditing(null)} onSave={save} />}
            <Dialog open={Boolean(deleting)} onClose={busy ? undefined : () => setDeleting(null)} fullWidth maxWidth="xs"><DialogTitle>حذف محصول</DialogTitle><DialogContent>«{deleting?.name}» از فهرست حذف شود؟ سوابق سفارش‌های قبلی حفظ می‌شوند.</DialogContent><DialogActions><Button disabled={busy} onClick={() => setDeleting(null)}>انصراف</Button><Button disabled={busy} color="error" onClick={() => void remove()}>حذف محصول</Button></DialogActions></Dialog>
        </section>
    );
}
