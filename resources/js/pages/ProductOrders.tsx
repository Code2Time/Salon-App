import { Alert, Button, Chip, CircularProgress, Dialog, DialogActions, DialogContent, DialogTitle, InputAdornment, MenuItem, Pagination, Paper, TextField, Typography } from "@mui/material";
import { ArrowRight, Check, Package, Search, ShoppingBag, Upload, UserRound, Phone, Wallet, Clock3, CheckCheck, ReceiptText } from "lucide-react";
import { useEffect, useState } from "react";
import { Link, useLocation, useParams } from "react-router";
import { fetchProductOrder, fetchProductOrders, saveOrderItemFulfillment } from "../services/api/products";
import { readableApiError } from "../services/api/client";
import type { ProductOrder } from "../types/product";
import { formatPriceInput as money } from "../utils/priceInput";

const date = (value: string) => new Intl.DateTimeFormat("fa-IR", { dateStyle: "medium", timeStyle: "short", timeZone: "Asia/Tehran" }).format(new Date(value));

type OrderLine = ProductOrder["items"][number];
type PaymentStatus = OrderLine["payment_status"];
type PaymentMethod = NonNullable<OrderLine["payment_method"]> | "";

function orderPaymentSummary(order: ProductOrder) {
    if (order.items.length && order.items.every((item) => item.payment_status === "paid")) return { label: "تسویه‌شده", color: "success" as const };
    if (order.items.some((item) => item.payment_status !== "unpaid")) return { label: "پرداخت بخشی", color: "warning" as const };
    return { label: "پرداخت‌نشده", color: "default" as const };
}

function FulfillmentEditor({ item, onSaved }: { item: OrderLine; onSaved: (order: ProductOrder) => void }) {
    const [open, setOpen] = useState(false);
    const [delivered, setDelivered] = useState(item.delivery_status === "delivered");
    const [paymentStatus, setPaymentStatus] = useState<PaymentStatus>(item.payment_status);
    const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>(item.payment_method ?? "");
    const [amount, setAmount] = useState(String(item.amount_received));
    const [receipt, setReceipt] = useState<File | null>(null);
    const [removeReceipt, setRemoveReceipt] = useState(false);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    function showEditor() {
        setDelivered(item.delivery_status === "delivered");
        setPaymentStatus(item.payment_status);
        setPaymentMethod(item.payment_method ?? "");
        setAmount(String(item.amount_received));
        setReceipt(null);
        setRemoveReceipt(false);
        setError(null);
        setOpen(true);
    }
    async function save() {
        setSaving(true); setError(null);
        try {
            const updated = await saveOrderItemFulfillment(item.id, {
                delivery_status: delivered ? "delivered" : "pending",
                payment_status: delivered ? paymentStatus : "unpaid",
                payment_method: delivered && paymentStatus !== "unpaid" ? paymentMethod : "",
                amount_received: delivered && paymentStatus !== "unpaid" ? Number(amount || 0) : 0,
                receipt,
                remove_receipt: removeReceipt || !delivered || paymentStatus === "unpaid",
            });
            onSaved(updated);
            setOpen(false);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally { setSaving(false); }
    }

    const paymentLabel = item.payment_status === "paid" ? "تسویه‌شده" : item.payment_status === "partial" ? "پرداخت بخشی" : "پرداخت‌نشده";
    const methodLabel = item.payment_method === "cash" ? "نقدی" : item.payment_method === "card" ? "کارت‌خوان" : item.payment_method === "transfer" ? "واریز" : item.payment_method === "other" ? "سایر" : "";
    return <>
        <div className="order-item-statuses">
            <Chip size="small" color={item.delivery_status === "delivered" ? "success" : "default"} label={item.delivery_status === "delivered" ? "تحویل شده" : "تحویل نشده"} />
            <Chip size="small" color={item.payment_status === "paid" ? "success" : item.payment_status === "partial" ? "warning" : "default"} label={paymentLabel} />
        </div>
        {item.amount_received > 0 && <small className="order-payment-detail">دریافتی: {money(item.amount_received)} تومان · {methodLabel}</small>}
        {item.receipt_url && <a className="order-receipt-link" href={item.receipt_url} target="_blank" rel="noreferrer">مشاهده رسید</a>}
        <Button className="order-fulfillment-button" variant={item.delivery_status === "delivered" ? "outlined" : "contained"} size="small" onClick={showEditor}>{item.delivery_status === "delivered" ? "ویرایش وضعیت" : "ثبت تحویل / دریافت"}</Button>
        <Dialog open={open} onClose={() => !saving && setOpen(false)} fullWidth maxWidth="xs" slotProps={{ paper: { className: "fulfillment-editor-dialog" } }}>
            <DialogTitle>وضعیت «{item.product_name}»</DialogTitle>
            <DialogContent className="fulfillment-dialog-content">
                <Typography color="text.secondary">{money(item.quantity)} عدد · مبلغ کل {money(item.total_amount)} تومان</Typography>
                    <TextField select label="وضعیت تحویل" value={delivered ? "delivered" : "pending"} onChange={(event) => { const next = event.target.value === "delivered"; setDelivered(next); if (!next) { setPaymentStatus("unpaid"); setPaymentMethod(""); setAmount("0"); setReceipt(null); } }}>
                    <MenuItem value="pending">تحویل نشده</MenuItem><MenuItem value="delivered">تحویل شده</MenuItem>
                </TextField>
                {delivered && <>
                    <TextField select label="وضعیت دریافت وجه" value={paymentStatus} onChange={(event) => { const next = event.target.value as PaymentStatus; setPaymentStatus(next); setAmount(next === "paid" ? String(item.total_amount) : next === "unpaid" ? "0" : ""); if (next === "unpaid") { setPaymentMethod(""); setReceipt(null); } }}>
                        <MenuItem value="unpaid">وجه دریافت نشده</MenuItem><MenuItem value="partial">بخشی دریافت شده</MenuItem><MenuItem value="paid">تسویه کامل</MenuItem>
                    </TextField>
                    {paymentStatus !== "unpaid" && <>
                        <TextField select label="روش دریافت وجه" value={paymentMethod} onChange={(event) => setPaymentMethod(event.target.value as PaymentMethod)}>
                            <MenuItem value="cash">نقدی</MenuItem><MenuItem value="card">کارت‌خوان</MenuItem><MenuItem value="transfer">کارت‌به‌کارت / واریز</MenuItem><MenuItem value="other">سایر</MenuItem>
                        </TextField>
                        <TextField type="number" label="مبلغ دریافت‌شده (تومان)" value={amount} onChange={(event) => setAmount(event.target.value)} slotProps={{ htmlInput: { min: 1, max: item.total_amount, dir: "ltr" } }} helperText={paymentStatus === "paid" ? "مبلغ کامل محصول ثبت می‌شود." : "مبلغ دریافتی را وارد کنید."} disabled={paymentStatus === "paid"} />
                        <Button component="label" className="receipt-upload" variant="outlined" startIcon={receipt ? <Check size={17} /> : <Upload size={17} />}>
                            {receipt ? receipt.name : item.receipt_url && !removeReceipt ? "جایگزینی رسید پرداخت" : "بارگذاری رسید (اختیاری)"}
                            <input hidden type="file" accept="image/jpeg,image/png,application/pdf" onChange={(event) => { setReceipt(event.target.files?.[0] ?? null); setRemoveReceipt(false); }} />
                        </Button>
                        {item.receipt_url && !removeReceipt && <Button color="error" size="small" onClick={() => { setRemoveReceipt(true); setReceipt(null); }}>حذف رسید قبلی</Button>}
                    </>}
                </>}
                {error && <Alert severity="error">{error}</Alert>}
            </DialogContent>
            <DialogActions><Button disabled={saving} onClick={() => setOpen(false)}>انصراف</Button><Button disabled={saving} variant="contained" onClick={() => void save()}>{saving ? "در حال ذخیره…" : "ذخیره وضعیت"}</Button></DialogActions>
        </Dialog>
    </>;
}

function PanelOrderDetails({ order, onSaved }: { order: ProductOrder; onSaved: (order: ProductOrder) => void }) {
    const received = order.items.reduce((sum, item) => sum + item.amount_received, 0);
    const remaining = Math.max(0, order.total_amount - received);
    const delivered = order.items.filter((item) => item.delivery_status === "delivered").length;
    const payment = orderPaymentSummary(order);
    return <div className="panel-order-detail">
        <div className="pod-overview">
            <div><span className="pod-metric-icon"><Wallet size={21} /></span><span>مبلغ سفارش<strong>{money(order.total_amount)} <small>تومان</small></strong></span></div>
            <div><span className="pod-metric-icon pod-success"><CheckCheck size={21} /></span><span>دریافت‌شده<strong>{money(received)} <small>تومان</small></strong></span></div>
            <div><span className="pod-metric-icon"><Package size={21} /></span><span>تحویل محصولات<strong>{money(delivered)} <small>از {money(order.items.length)} قلم</small></strong></span></div>
        </div>
        <div className="pod-layout">
            <section className="pod-products">
                <div className="pod-section-heading"><div><Typography component="h2" variant="h3">محصولات سفارش</Typography><p>تحویل و دریافت وجه هر محصول را جداگانه ثبت کنید.</p></div><span>{money(order.items.length)} قلم</span></div>
                {order.items.map((item, index) => <Paper component="article" variant="outlined" className="pod-product" key={item.id}>
                    <div className="pod-product-heading"><span className="pod-product-number">{money(index + 1)}</span><div><Typography component="h3" variant="h3">{item.product_name}</Typography><span>{money(item.quantity)} عدد <span aria-hidden="true">·</span> قیمت واحد {money(item.unit_price)} تومان</span></div><strong>{money(item.total_amount)} <small>تومان</small></strong></div>
                    <div className="pod-product-management"><FulfillmentEditor item={item} onSaved={onSaved} /></div>
                    {item.delivered_at && <div className="pod-delivery-date"><CheckCheck size={14} /> تحویل در {date(item.delivered_at)}</div>}
                </Paper>)}
            </section>
            <aside className="pod-sidebar">
                <Paper variant="outlined" className="pod-customer"><div className="pod-sidebar-title"><UserRound size={18} /><Typography component="h2" variant="h3">اطلاعات مشتری</Typography></div><div className="pod-customer-name"><span>{order.customer_name.trim().slice(0, 1)}</span><div><strong>{order.customer_name}</strong><small>سفارش #{money(order.id)}</small></div></div><a className="pod-phone" href={`tel:${order.customer_mobile}`}><Phone size={16} /><span dir="ltr">{order.customer_mobile}</span><small>تماس</small></a><div className="pod-registered"><Clock3 size={16} /><span>زمان ثبت<time>{date(order.created_at)}</time></span></div>{order.notes && <div className="pod-notes"><strong>یادداشت مشتری</strong><p>{order.notes}</p></div>}</Paper>
                <Paper variant="outlined" className="pod-payment"><div className="pod-sidebar-title"><ReceiptText size={18} /><Typography component="h2" variant="h3">خلاصه پرداخت</Typography></div><Chip size="small" label={payment.label} color={payment.color} /><dl><div><dt>مبلغ کل</dt><dd>{money(order.total_amount)} <small>تومان</small></dd></div><div><dt>دریافت‌شده</dt><dd>{money(received)} <small>تومان</small></dd></div><div className="pod-remaining"><dt>مانده قابل دریافت</dt><dd>{money(remaining)} <small>تومان</small></dd></div></dl><p>مبالغ بر اساس دریافت‌های ثبت‌شده برای محصولات محاسبه می‌شوند.</p></Paper>
            </aside>
        </div>
    </div>;
}

function CustomerOrderDetail({ order }: { order: ProductOrder }) {
    const delivered = order.items.filter((item) => item.delivery_status === "delivered").length;
    const received = order.items.reduce((sum, item) => sum + item.amount_received, 0);
    const payment = orderPaymentSummary(order);
    return <div className="co-detail">
        <Paper variant="outlined" className="co-progress"><span className="co-large-icon"><ShoppingBag size={27} /></span><div><span className="eyebrow">سفارش #{money(order.id)}</span><Typography component="h2" variant="h3">{delivered === order.items.length ? "محصولات به دست شما رسید" : delivered ? "بخشی از سفارش تحویل شد" : "سفارش شما در انتظار تحویل است"}</Typography><p>{date(order.created_at)}</p></div><div className="co-progress-track"><span className="is-done"><Check size={16} /> ثبت سفارش</span><i /><span className={delivered === order.items.length ? "is-done" : ""}><Package size={16} /> تحویل {money(delivered)} از {money(order.items.length)} قلم</span></div></Paper>
        <div className="co-detail-columns"><Paper variant="outlined" className="co-detail-items"><Typography component="h2" variant="h3">محصولات شما</Typography>{order.items.map((item) => <article className="co-detail-item" key={item.id}><span className="co-item-icon"><Package size={21} /></span><div><strong>{item.product_name}</strong><small>{money(item.quantity)} عدد · هر عدد {money(item.unit_price)} تومان</small><Chip size="small" color={item.delivery_status === "delivered" ? "success" : "default"} label={item.delivery_status === "delivered" ? "تحویل شده" : "در انتظار تحویل"} /></div><strong>{money(item.total_amount)} <small>تومان</small></strong></article>)}</Paper>
        <Paper variant="outlined" className="co-receipt"><div><ReceiptText size={20} /><Typography component="h2" variant="h3">صورت‌حساب سفارش</Typography></div><Chip label={payment.label} color={payment.color} size="small" /><dl><div><dt>مبلغ سفارش</dt><dd>{money(order.total_amount)} تومان</dd></div><div><dt>پرداخت‌شده</dt><dd>{money(received)} تومان</dd></div><div><dt>مانده پرداخت</dt><dd>{money(Math.max(0, order.total_amount - received))} تومان</dd></div></dl><p>محصولات از سالن تحویل داده می‌شوند. زمان دریافت و پرداخت را با سالن هماهنگ کنید.</p>{order.notes && <div className="co-customer-note"><strong>یادداشت شما</strong><p>{order.notes}</p></div>}</Paper></div>
    </div>;
}

function CustomerOrderCard({ order }: { order: ProductOrder }) {
    const delivered = order.items.filter((item) => item.delivery_status === "delivered").length;
    const payment = orderPaymentSummary(order);
    return <Paper variant="outlined" className="co-card"><div className="co-card-head"><span className="co-item-icon"><ShoppingBag size={21} /></span><div><strong>سفارش #{money(order.id)}</strong><time>{date(order.created_at)}</time></div><Chip size="small" label={delivered === order.items.length ? "تحویل شده" : "در انتظار تحویل"} color={delivered === order.items.length ? "success" : "default"} /></div><div className="co-card-products">{order.items.slice(0, 2).map((item) => <div key={item.id}><span>{item.product_name}</span><small>{money(item.quantity)} عدد</small></div>)}{order.items.length > 2 && <small>و {money(order.items.length - 2)} محصول دیگر</small>}</div><div className="co-card-footer"><div><small>{payment.label}</small><strong>{money(order.total_amount)} <small>تومان</small></strong></div><Button component={Link} to={`/app/orders/${order.id}`} variant="outlined">جزئیات سفارش <ArrowRight size={15} /></Button></div></Paper>;
}

export default function ProductOrders({ panel = false }: { panel?: boolean }) {
    const { orderId } = useParams();
    const location = useLocation();
    const base = panel ? "/panel/orders" : "/app/orders";
    const [orders, setOrders] = useState<ProductOrder[]>([]);
    const [order, setOrder] = useState<ProductOrder | null>(null);
    const [search, setSearch] = useState("");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [refresh, setRefresh] = useState(0);

    useEffect(() => {
        let active = true;
        setLoading(true); setError(null); setOrder(null);
        const timer = setTimeout(() => {
            const request = orderId
                ? fetchProductOrder(panel, orderId).then((result) => { if (active) setOrder(result); })
                : fetchProductOrders(panel, page, search.trim()).then((result) => { if (active) { setOrders(result.data); setLastPage(result.meta.last_page); setTotal(result.meta.total); } });
            void request.catch((caught) => { if (active) setError(readableApiError(caught)); }).finally(() => { if (active) setLoading(false); });
        }, 200);
        return () => { active = false; clearTimeout(timer); };
    }, [orderId, panel, page, search, refresh]);

    return (
        <section className={`order-page${panel && orderId ? " panel-order-page" : !panel ? " customer-orders" : ""}`}>
            <header className="shop-heading"><div><span className="eyebrow">{panel ? "پیگیری فروش محصولات" : "خریدهای شما از سالن"}</span><Typography component="h1" variant="h2">{orderId ? `جزئیات سفارش ${money(Number(orderId))}` : panel ? "سفارش محصولات" : "سفارش‌های من"}</Typography><Typography color="text.secondary">ثبت سفارش بدون پرداخت آنلاین؛ هماهنگی تحویل و تسویه با سالن.</Typography></div><Button component={Link} to={orderId ? base : panel ? "/panel/products" : "/app/products"}><ArrowRight size={17} />{orderId ? "فهرست سفارش‌ها" : "محصولات"}</Button></header>
            {orderId && (location.state as { created?: boolean } | null)?.created && <Alert severity="success">سفارش شما ثبت شد و مدیر سالن اعلان آن را دریافت کرد. هنوز پرداختی انجام نشده است.</Alert>}
            {!orderId && panel && <TextField fullWidth size="small" label="جستجوی نام یا موبایل مشتری" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} slotProps={{ input: { startAdornment: <InputAdornment position="start"><Search size={18} /></InputAdornment> } }} />}
            {error && <Alert severity="error" action={<Button onClick={() => setRefresh((value) => value + 1)}>تلاش دوباره</Button>}>{error}</Alert>}
            {loading ? <div className="shop-loading"><CircularProgress aria-label="دریافت سفارش‌ها" /></div> : !error && (order ? panel ? <PanelOrderDetails order={order} onSaved={setOrder} /> : <CustomerOrderDetail order={order} /> : <>
                {!panel && orders.length > 0 ? <><div className="co-list-label"><span>تاریخچه خرید شما</span><small>{money(total)} سفارش</small></div><div className="co-cards">{orders.map((item) => <CustomerOrderCard key={item.id} order={item} />)}</div></> : <>
                <Typography color="text.secondary">{money(total)} سفارش</Typography>
                {orders.length ? <div className="orders-list">{orders.map((item) => { const payment = orderPaymentSummary(item); const delivered = item.items.filter((line) => line.delivery_status === "delivered").length; return <Paper key={item.id} variant="outlined" className="order-list-card"><div className="order-list-head"><span><ShoppingBag size={18} /> سفارش #{money(item.id)}</span><Chip label={payment.label} color={payment.color} size="small" /></div>{panel && <Typography sx={{ fontWeight: 600 }}>{item.customer_name} <span className="order-mobile" dir="ltr">{item.customer_mobile}</span></Typography>}<span className="order-list-date">{date(item.created_at)}</span><p>{item.items.map((line) => `${line.product_name} (${money(line.quantity)})`).join("، ")}</p>{panel && <span className="order-list-date">تحویل {money(delivered)} از {money(item.items.length)} قلم</span>}<div className="order-list-footer"><strong>{money(item.total_amount)} تومان</strong><Button component={Link} to={`${base}/${item.id}`} variant="outlined" size="small">مشاهده جزئیات</Button></div></Paper>; })}</div> : <Paper variant="outlined" className="products-empty"><ShoppingBag size={32} /><Typography variant="h3">سفارشی پیدا نشد</Typography><Typography color="text.secondary">{panel ? "سفارش‌های مشتریان پس از ثبت در این قسمت نمایش داده می‌شوند." : "پس از انتخاب محصولات و ثبت سفارش، جزئیات آن را اینجا می‌بینید."}</Typography></Paper>}
                </>}
                {lastPage > 1 && <Pagination className="products-pagination" count={lastPage} page={page} onChange={(_, value) => setPage(value)} />}
            </>)}
        </section>
    );
}
