import { useEffect, useState } from "react";
import { Alert, Button, Chip, CircularProgress, Pagination, Paper, Typography } from "@mui/material";
import { Gift, Package, Plus, Sparkles } from "lucide-react";
import { Link, useNavigate } from "react-router";
import { toast } from "react-toastify";
import { fetchRecommendations } from "../../services/api/products";
import { readableApiError } from "../../services/api/client";
import type { AftercareRecommendation } from "../../types/product";
import { readCart, storageKey, uuid } from "../../cart/productCart";
import { formatPriceInput as money } from "../../utils/priceInput";
import "../../../css/aftercare.css";

const dateLabel = (value: string) => new Intl.DateTimeFormat("fa-IR", { dateStyle: "medium", timeStyle: "short", timeZone: "Asia/Tehran" }).format(new Date(value));

export default function Recommendations() {
    const [items, setItems] = useState<AftercareRecommendation[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [refresh, setRefresh] = useState(0);
    const navigate = useNavigate();
    useEffect(() => {
        let active = true;
        setLoading(true);
        void fetchRecommendations(page).then(result => { if (active) { setItems(result.data); setLastPage(result.meta.last_page); setError(null); } })
            .catch(caught => { if (active) setError(readableApiError(caught)); }).finally(() => { if (active) setLoading(false); });
        return () => { active = false; };
    }, [page, refresh]);

    function addToCart(offer: AftercareRecommendation) {
        const cart = readCart();
        if (cart.lines.length && cart.salon_id !== offer.salon_id) { toast.info("ابتدا سفارش سبد فعلی را ثبت یا خالی کنید؛ محصولات دو سالن در یک سفارش قرار نمی‌گیرند."); return; }
        const existing = cart.lines.find(line => line.product.id === offer.product.id);
        if (!existing && cart.lines.length >= 20) { toast.info("سبد حداکثر ۲۰ محصول مختلف دارد."); return; }
        const updated = { ...cart, salon_id: offer.salon_id, request_id: uuid(), discount_code: offer.discount_available ? offer.discount_code ?? "" : "",
            lines: existing ? cart.lines.map(line => line.product.id === offer.product.id ? { product: offer.product, quantity: Math.min(line.quantity + 1, offer.product.stock, 99) } : line) : [...cart.lines, { product: offer.product, quantity: 1 }] };
        try { sessionStorage.setItem(storageKey, JSON.stringify(updated)); }
        catch { toast.error("سبد در مرورگر ذخیره نشد؛ محصول را از فروشگاه انتخاب و کد پیشنهاد را وارد کنید."); return; }
        navigate("/app/products?cart=1");
    }

    return <section className="aftercare-page"><header className="aftercare-heading"><span className="eyebrow">مراقبت بعد از سالن</span><Typography component="h1" variant="h2">پیشنهادهای مخصوص شما</Typography><Typography color="text.secondary">محصولات مرتبط با خدماتی که دریافت کرده‌اید، همراه توصیه سالن.</Typography></header>
        <Alert severity="info" icon={<Gift size={22} />}>کدهای تخفیف شخصی و یک‌بارمصرف‌اند. در هر سفارش فقط یک کد روی محصول مرتبط اعمال می‌شود.</Alert>
        {error && <Alert severity="error" action={<Button onClick={() => setRefresh(value => value + 1)}>تلاش دوباره</Button>}>{error}</Alert>}
        {loading ? <div className="aftercare-loading"><CircularProgress /><span>در حال دریافت پیشنهادها…</span></div> : !error && (items.length ? <div className="aftercare-grid">{items.map(offer => <Paper variant="outlined" component="article" className="aftercare-card" key={offer.id}>
            <div className="aftercare-product-heading"><div className="aftercare-product-image">{offer.product.image_url ? <img src={offer.product.image_url} alt={offer.product.name} /> : <Package size={35} />}</div><div><small>{offer.salon_name} · {dateLabel(offer.created_at)}</small><Typography variant="h3" component="h2">{offer.product.name}</Typography><span>{offer.discount_available && <del>{money(offer.product.price)}</del>} <strong>{money(offer.discounted_price)}</strong> تومان</span></div>{offer.discount_available && <Chip size="small" color="primary" label={`${money(offer.discount_percent)}٪ تخفیف`} />}</div>
            {offer.product.description && <p className="aftercare-description">{offer.product.description}</p>}
            <div className="aftercare-instructions">{offer.care_instructions.map((instruction, index) => <div key={index}><strong><Sparkles size={14} />بعد از {instruction.service_name}</strong><p>{instruction.text || "برای روش مصرف با سالن مشورت کنید."}</p></div>)}</div>
            {offer.discount_code && <div className={`aftercare-coupon${offer.discount_available ? "" : " is-inactive"}`}><span>{offer.redeemed_at ? "تخفیف استفاده شده" : offer.discount_available ? "کد اختصاصی شما" : "اعتبار تخفیف تمام شده"}</span><code dir="ltr">{offer.discount_code}</code>{offer.expires_at && <small>اعتبار تا {dateLabel(offer.expires_at)}</small>}</div>}
            <Button variant="contained" startIcon={<Plus size={16} />} onClick={() => addToCart(offer)}>{offer.discount_available ? "افزودن به سبد با تخفیف" : "افزودن به سبد با قیمت عادی"}</Button>
        </Paper>)}</div> : <Paper variant="outlined" className="aftercare-empty"><Sparkles size={36} /><Typography component="h2" variant="h3">هنوز پیشنهادی ندارید</Typography><p>پس از انجام خدمت و ثبت آن توسط سالن، محصولات مراقبتی مرتبط اینجا نمایش داده می‌شوند.</p><Button component={Link} to="/app/products" variant="outlined">مشاهده محصولات سالن</Button></Paper>)}
        {lastPage > 1 && <Pagination count={lastPage} page={page} onChange={(_, value) => setPage(value)} />}
    </section>;
}
