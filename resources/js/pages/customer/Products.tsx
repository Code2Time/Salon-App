import { readCart, storageKey, uuid } from "../../cart/productCart";
import type { Cart } from "../../cart/productCart";
import { Alert, Button, Chip, CircularProgress, Dialog, DialogActions, DialogContent, DialogTitle, IconButton, InputAdornment, Pagination, Paper, TextField, Typography } from "@mui/material";
import { ArrowLeft, ImageOff, Minus, Package, Plus, Search, ShoppingBag, Trash2, X } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router";
import { useAuth } from "../../auth/AuthContext";
import { fetchCatalogProducts, placeOrder, quoteOrder } from "../../services/api/products";
import type { PlaceOrderInput } from "../../services/api/products";
import { readableApiError } from "../../services/api/client";
import type { CatalogProduct, OrderQuote } from "../../types/product";
import { formatPriceInput as money } from "../../utils/priceInput";

export default function CustomerProducts() {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [products, setProducts] = useState<CatalogProduct[]>([]);
    const [salon, setSalon] = useState({ id: 0, name: "" });
    const [cart, setCart] = useState<Cart>(readCart);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [refresh, setRefresh] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [params] = useSearchParams();
    const [cartOpen, setCartOpen] = useState(params.get("cart") === "1");
    const [codeInput, setCodeInput] = useState(cart.discount_code ?? "");
    const [quote, setQuote] = useState<OrderQuote | null>(null);
    const [quoting, setQuoting] = useState(false);
    const [quoteError, setQuoteError] = useState<string | null>(null);
    const [quoteRefresh, setQuoteRefresh] = useState(0);
    const [detail, setDetail] = useState<CatalogProduct | null>(null);
    const [saving, setSaving] = useState(false);
    const [orderError, setOrderError] = useState<string | null>(null);
    const submitting = useRef(false);

    useEffect(() => {
        let active = true;
        const timer = setTimeout(() => {
            setLoading(true);
            setError(null);
            void fetchCatalogProducts({ page, search: search.trim() }).then((result) => {
                if (!active) return;
                setProducts(result.data); setLastPage(result.meta.last_page); if (result.salon) setSalon(result.salon);
            }).catch((caught) => { if (active) setError(readableApiError(caught)); }).finally(() => { if (active) setLoading(false); });
        }, 250);
        return () => { active = false; clearTimeout(timer); };
    }, [page, search, refresh]);
    useEffect(() => { try { sessionStorage.setItem(storageKey, JSON.stringify(cart)); } catch { /* Cart stays usable in memory. */ } }, [cart]);

    useEffect(() => {
        let active = true;
        setQuote(null); setQuoteError(null);
        if (!cart.discount_code || !cart.lines.length || !user) { setQuoting(false); return; }
        setQuoting(true);
        const timer = window.setTimeout(() => {
            void quoteOrder({ request_id: cart.request_id, salon_id: cart.salon_id, notes: cart.notes, discount_code: cart.discount_code ?? "", items: cart.lines.map(line => ({ product_id: line.product.id, quantity: line.quantity, expected_unit_price: line.product.price })) })
                .then(result => { if (active) setQuote(result); })
                .catch(caught => { if (active) setQuoteError(readableApiError(caught)); })
                .finally(() => { if (active) setQuoting(false); });
        }, 200);
        return () => { active = false; window.clearTimeout(timer); };
    }, [cart.discount_code, cart.lines, cart.salon_id, user?.id, quoteRefresh]);

    const count = cart.lines.reduce((sum, line) => sum + line.quantity, 0);
    const total = cart.lines.reduce((sum, line) => sum + line.product.price * line.quantity, 0);
    function add(product: CatalogProduct) {
        if (saving || product.stock < 1) return;
        setOrderError(null);
        setCart((current) => {
            const lines = current.salon_id === salon.id ? current.lines : [];
            const existing = lines.find((line) => line.product.id === product.id);
            if (!existing && lines.length >= 20) { setOrderError("در هر سفارش حداکثر ۲۰ محصول مختلف انتخاب کنید."); return current; }
            return { ...current, salon_id: salon.id, request_id: uuid(), lines: existing ? lines.map((line) => line.product.id === product.id ? { product, quantity: Math.min(line.quantity + 1, product.stock, 99) } : line) : [...lines, { product, quantity: 1 }] };
        });
    }
    function change(id: number, amount: number) {
        setOrderError(null);
        setCart((current) => ({ ...current, request_id: uuid(), lines: current.lines.map((line) => line.product.id === id ? { ...line, quantity: Math.min(99, line.product.stock, line.quantity + amount) } : line).filter((line) => line.quantity > 0) }));
    }
    async function submit() {
        if (!user) { navigate("/login", { state: { from: "/app/products" } }); return; }
        if (submitting.current || !cart.lines.length || (cart.discount_code && (!quote || quoting || quoteError))) return;
        submitting.current = true; setSaving(true); setOrderError(null);
        const input: PlaceOrderInput = { discount_code: cart.discount_code || "", request_id: cart.request_id, salon_id: cart.salon_id, notes: cart.notes, items: cart.lines.map((line) => ({ product_id: line.product.id, quantity: line.quantity, expected_unit_price: line.product.price })) };
        try {
            const order = await placeOrder(input);
            try { sessionStorage.removeItem(storageKey); } catch { /* Successful orders must not depend on browser storage. */ }
            setCart({ salon_id: salon.id, lines: [], notes: "", request_id: uuid() });
            setCartOpen(false);
            navigate(`/app/orders/${order.id}`, { state: { created: true } });
        } catch (caught) { setOrderError(readableApiError(caught)); setRefresh((value) => value + 1); } finally { submitting.current = false; setSaving(false); }
    }
    return (
        <section className="customer-shop customer-store">
            <header className="store-heading"><div><span className="eyebrow">فروشگاه {salon.name || "سالن"}</span><Typography component="h1" variant="h2">مراقبت، بعد از سالن</Typography><p>انتخاب‌های کوچک برای ادامه زیبایی شما.</p></div><Button component={Link} to="/app/recommendations" variant="outlined">پیشنهادهای من</Button><Button component={Link} to="/app/orders" variant="outlined"><Package size={17} /> سفارش‌های من</Button></header>
            <div className="shop-toolbar"><div className="shop-search"><TextField fullWidth size="small" placeholder="نام محصول را جستجو کنید…" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} slotProps={{ input: { startAdornment: <InputAdornment position="start"><Search size={19} /></InputAdornment> } }} /></div><Button className="shop-cart-button" variant="contained" onClick={() => setCartOpen(true)}><ShoppingBag size={18} /> سبد خرید <span>{money(count)}</span></Button></div>
            <div className="shop-section-heading" id="shop-products"><div><span className="eyebrow">فروشگاه سالن</span><Typography component="h2" variant="h2">محصولات مراقبتی</Typography></div><span className="shop-result-count">{loading ? "در حال دریافت…" : `${money(products.length)} محصول`}</span></div>
            <div className="store-delivery-note"><Package size={18} /><span>تحویل از سالن <small>پرداخت و زمان دریافت با هماهنگی سالن</small></span></div>
            {error && <Alert severity="error" action={<Button onClick={() => setRefresh((value) => value + 1)}>تلاش دوباره</Button>}>{error}</Alert>}
            {orderError && !cartOpen && <Alert severity="warning">{orderError}</Alert>}
            {loading ? <div className="shop-loading"><CircularProgress aria-label="دریافت محصولات" /></div> : !error && (products.length ? <div className="shop-grid">{products.map((product) => <Paper variant="outlined" className="shop-product" key={product.id}><button className="shop-product-visual" onClick={() => setDetail(product)} aria-label={`جزئیات ${product.name}`}>{product.image_url ? <img src={product.image_url} alt={product.name} /> : <ImageOff size={38} />}{!product.stock && <Chip label="ناموجود" size="small" />}</button><div className="shop-product-copy"><div className="shop-product-title"><Typography component="h3" variant="h3">{product.name}</Typography><button onClick={() => setDetail(product)} aria-label={`جزئیات ${product.name}`}><ArrowLeft size={17}/></button></div><p>{product.description || "برای اطلاعات بیشتر با سالن هماهنگ کنید."}</p><div className="shop-product-buy"><strong>{money(product.price)} <small>تومان</small></strong><Button variant="contained" disabled={!product.stock || saving} onClick={() => add(product)}><Plus size={17} /> افزودن</Button></div></div></Paper>)}</div> : <Paper variant="outlined" className="products-empty"><Package size={32} /><Typography variant="h3">محصولی پیدا نشد</Typography><Typography color="text.secondary">{search ? "عبارت دیگری را جستجو کنید." : "محصولات فعال سالن در این بخش نمایش داده می‌شوند."}</Typography></Paper>)}
            {lastPage > 1 && <Pagination className="products-pagination" page={page} count={lastPage} onChange={(_, value) => setPage(value)} />}
            {count > 0 && <div className="shop-cart-bar"><span>{money(count)} کالا<strong>{money(quote?.total_amount ?? total)} تومان</strong></span><Button variant="contained" onClick={() => setCartOpen(true)}>بررسی و ثبت سفارش</Button></div>}
            <Dialog open={Boolean(detail)} onClose={() => setDetail(null)} fullWidth maxWidth="sm" aria-labelledby="shop-detail-title" slotProps={{ paper: { className: "shop-detail-dialog" } }}>
                <DialogTitle className="shop-detail-header" id="shop-detail-title"><span>{detail?.name}</span><IconButton aria-label="بستن جزئیات" onClick={() => setDetail(null)}><X size={20} /></IconButton></DialogTitle>
                <DialogContent className="shop-detail-content">
                    <div className="shop-detail-image">{detail?.image_url ? <img src={detail.image_url} alt={detail.name} /> : <Package size={45} />}</div>
                    <div className="shop-detail-about"><span className="eyebrow">درباره محصول</span><Typography className="shop-detail-description">{detail?.description || "برای اطلاعات بیشتر درباره این محصول با سالن هماهنگ کنید."}</Typography><span className="shop-detail-delivery"><Package size={16} /> {detail?.stock ? "تحویل از سالن با هماهنگی" : "این محصول فعلاً موجود نیست"}</span></div>
                </DialogContent>
                <DialogActions className="shop-detail-footer"><div><small>قیمت محصول</small><strong>{money(detail?.price ?? 0)} <span>تومان</span></strong></div><Button variant="contained" disabled={!detail?.stock || saving} onClick={() => { if (detail) add(detail); setDetail(null); }}><Plus size={18} /> {detail?.stock ? "افزودن به سبد" : "ناموجود"}</Button></DialogActions>
            </Dialog>
            <Dialog open={cartOpen} onClose={saving ? undefined : () => setCartOpen(false)} fullWidth maxWidth="sm"><DialogTitle className="product-dialog-title">مرور سفارش<IconButton disabled={saving} aria-label="بستن سبد" onClick={() => setCartOpen(false)}><X size={20} /></IconButton></DialogTitle><DialogContent className="shop-cart-content">{orderError && <Alert severity="error">{orderError}</Alert>}{cart.lines.length ? <>{cart.lines.map((line) => <div className="shop-cart-line" key={line.product.id}><div><strong>{line.product.name}</strong><small>هر عدد {money(line.product.price)} تومان</small></div><div className="shop-quantity"><IconButton disabled={saving} aria-label={`کاهش تعداد ${line.product.name}`} onClick={() => change(line.product.id, -1)}>{line.quantity === 1 ? <Trash2 size={17} /> : <Minus size={17} />}</IconButton><span>{money(line.quantity)}</span><IconButton disabled={saving || line.quantity >= Math.min(99, line.product.stock)} aria-label={`افزایش تعداد ${line.product.name}`} onClick={() => change(line.product.id, 1)}><Plus size={17} /></IconButton></div><strong>{money(line.quantity * line.product.price)} تومان</strong></div>)}<TextField disabled={saving} fullWidth multiline minRows={2} label="توضیحات سفارش (اختیاری)" value={cart.notes} onChange={(event) => setCart({ ...cart, notes: event.target.value, request_id: uuid() })} slotProps={{ htmlInput: { maxLength: 1000 } }} /><div className="shop-coupon"><div className="shop-coupon-entry"><TextField size="small" disabled={saving} label="کد تخفیف اختصاصی" value={codeInput} onChange={event => setCodeInput(event.target.value)} slotProps={{ htmlInput: { dir: "ltr", maxLength: 40 } }} /><Button disabled={saving || !user || !codeInput.trim() || quoting} variant="outlined" onClick={() => { setCart(current => ({ ...current, discount_code: codeInput.trim().toUpperCase(), request_id: uuid() })); setQuoteRefresh(value => value + 1); }}>اعمال کد</Button></div>{!user && <small>برای بررسی کد، ابتدا وارد حساب شوید.</small>}{cart.discount_code && <Button size="small" disabled={saving} color="inherit" onClick={() => { setCodeInput(""); setCart(current => ({ ...current, discount_code: "", request_id: uuid() })); }}>حذف کد از سفارش</Button>}{quoting && <span>در حال بررسی تخفیف…</span>}{quoteError && <Alert severity="warning">{quoteError}</Alert>}{quote?.discount_code && <Alert severity="success">{money(quote.discount_percent)}٪ تخفیف روی محصول مرتبط اعمال شد. کد فقط با ثبت موفق این سفارش مصرف می‌شود.</Alert>}</div>{quote?.discount_amount ? <><div className="shop-coupon-summary"><span>جمع قبل از تخفیف</span><strong>{money(quote.subtotal_amount)} تومان</strong></div><div className="shop-coupon-summary"><span>تخفیف اختصاصی</span><strong>{money(quote.discount_amount)} تومان</strong></div></> : null}<div className="shop-order-total"><span>مبلغ سفارش</span><strong>{money(quote?.total_amount ?? total)} تومان</strong></div><Alert severity="info">پرداخت و دریافت محصول با هماهنگی سالن انجام می‌شود. پس از ثبت، سفارش برای مدیر سالن ارسال خواهد شد.</Alert></> : <Typography>سبد شما خالی است.</Typography>}</DialogContent><DialogActions className="product-form-actions"><Button disabled={saving} onClick={() => setCartOpen(false)}>ادامه انتخاب</Button><Button variant="contained" disabled={saving || !cart.lines.length || (Boolean(user && cart.discount_code) && (quoting || !quote || Boolean(quoteError)))} onClick={() => void submit()}>{saving ? "در حال ثبت…" : user ? "ثبت نهایی سفارش" : "ورود و ثبت سفارش"}</Button></DialogActions></Dialog>
        </section>
    );
}
