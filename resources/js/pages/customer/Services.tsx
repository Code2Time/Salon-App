import {
    Alert,
    Avatar,
    AvatarGroup,
    Button,
    Chip,
    CircularProgress,
    FormControl,
    InputLabel,
    MenuItem,
    Pagination,
    Paper,
    Select,
    TextField,
    Typography,
} from "@mui/material";
import { Clock3, Search, SlidersHorizontal, Sparkles, WalletCards } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import type { FormEvent } from "react";
import { defaultAvatarUrl } from "../../constants/assets";
import { readableApiError } from "../../services/api/client";
import { fetchCustomerServiceCategories, fetchCustomerServices } from "../../services/api/customerServices";
import type { CustomerService, CustomerServiceCategory, CustomerServiceFilters } from "../../types/customerService";

const numberFormat = new Intl.NumberFormat("fa-IR");

export default function CustomerServices() {
    const [services, setServices] = useState<CustomerService[]>([]);
    const [categories, setCategories] = useState<CustomerServiceCategory[]>([]);
    const [salonName, setSalonName] = useState("سالن آرا");
    const [search, setSearch] = useState("");
    const [query, setQuery] = useState("");
    const [categoryId, setCategoryId] = useState<number>();
    const [sort, setSort] = useState<NonNullable<CustomerServiceFilters["sort"]>>("newest");
    const [page, setPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [total, setTotal] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const result = await fetchCustomerServices({
                ...(query ? { search: query } : {}),
                ...(categoryId ? { category_id: categoryId } : {}),
                sort,
                page,
            });
            setServices(result.data);
            setSalonName(result.salon.name);
            setLastPage(result.meta.last_page);
            setTotal(result.meta.total);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, [categoryId, page, query, sort]);

    useEffect(() => { void load(); }, [load]);
    useEffect(() => {
        void fetchCustomerServiceCategories().then(setCategories).catch((caught) => setError(readableApiError(caught)));
    }, []);

    function submitSearch(event: FormEvent) {
        event.preventDefault();
        setPage(1);
        setQuery(search.trim());
    }

    return (
        <section className="customer-services-page">
            <header className="customer-services-heading">
                <div>
                    <span className="eyebrow">انتخاب برای حال خوب شما</span>
                    <Typography component="h1" variant="h2">سرویس‌های {salonName}</Typography>
                    <Typography color="text.secondary">سرویس مناسب خودتان را با جزئیات قیمت و زمان موردنیاز پیدا کنید.</Typography>
                </div>
                <span className="customer-services-count"><Sparkles size={17} /> {numberFormat.format(total)} سرویس فعال</span>
            </header>

            <div className="customer-category-strip" aria-label="فیلتر دسته‌بندی سرویس‌ها">
                <button type="button" className={!categoryId ? "active" : ""} onClick={() => { setCategoryId(undefined); setPage(1); }}><span><Sparkles size={18} /></span><strong>همه</strong><small>{numberFormat.format(total)} سرویس</small></button>
                {categories.map((category) => (
                    <button type="button" key={category.id} className={categoryId === category.id ? "active" : ""} onClick={() => { setCategoryId(category.id); setPage(1); }}>
                        <span>{category.image_url ? <img src={category.image_url} alt="" /> : <Sparkles size={18} />}</span>
                        <strong>{category.name}</strong>
                        <small>{numberFormat.format(category.services_count ?? 0)} سرویس</small>
                    </button>
                ))}
            </div>

            <Paper variant="outlined" className="customer-services-toolbar">
                <form onSubmit={submitSearch}>
                    <TextField size="small" label="جستجوی سرویس" value={search} onChange={(event) => setSearch(event.target.value)} />
                    <Button type="submit" color="inherit"><Search size={18} /> جستجو</Button>
                </form>
                <FormControl size="small">
                    <InputLabel>مرتب‌سازی</InputLabel>
                    <Select label="مرتب‌سازی" value={sort} onChange={(event) => { setSort(event.target.value as NonNullable<CustomerServiceFilters["sort"]>); setPage(1); }} startAdornment={<SlidersHorizontal size={16} />}>
                        <MenuItem value="newest">جدیدترین</MenuItem>
                        <MenuItem value="price_asc">کمترین قیمت</MenuItem>
                        <MenuItem value="price_desc">بیشترین قیمت</MenuItem>
                    </Select>
                </FormControl>
            </Paper>

            {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
            {loading ? <div className="customer-services-loading"><CircularProgress aria-label="در حال دریافت سرویس‌ها" /></div> : services.length === 0 ? (
                <Paper variant="outlined" className="customer-services-empty"><Search size={38} /><Typography variant="h3">سرویسی پیدا نشد</Typography><Typography color="text.secondary">عبارت جستجو یا دسته‌بندی را تغییر دهید.</Typography></Paper>
            ) : (
                <div className="customer-service-grid">
                    {services.map((service) => (
                        <Paper variant="outlined" className="customer-service-card" key={service.id}>
                            <div className="customer-service-accent"><Sparkles size={22} /><span>{service.categories[0]?.name}</span></div>
                            <div className="customer-service-copy">
                                <div className="customer-service-title"><Typography component="h2" variant="h3">{service.name}</Typography><div>{service.categories.map((category) => <Chip key={category.id} label={category.name} size="small" />)}</div></div>
                                <Typography color="text.secondary" variant="body2">{service.description || "توضیحات این سرویس به‌زودی تکمیل می‌شود."}</Typography>
                            </div>
                            <div className="customer-service-facts">
                                <span><Clock3 size={18} /><small>مدت سرویس</small><strong>{numberFormat.format(service.duration_minutes)} دقیقه</strong></span>
                                <span><WalletCards size={18} /><small>قیمت</small><strong>{numberFormat.format(service.price)} تومان</strong></span>
                            </div>
                            <div className="customer-service-providers">
                                {service.providers.length > 0 ? <><AvatarGroup max={4}>{service.providers.map((provider) => <Avatar key={provider.name} src={provider.image_url || defaultAvatarUrl} alt={provider.name} />)}</AvatarGroup><div><small>ارائه‌دهندگان</small><strong>{service.providers.map((provider) => provider.name).join("، ")}</strong></div></> : <Typography variant="body2" color="text.secondary">ارائه‌دهنده هنگام رزرو مشخص می‌شود.</Typography>}
                            </div>
                        </Paper>
                    ))}
                </div>
            )}

            {!loading && total > 0 && <div className="customer-services-pagination"><span>نمایش سرویس‌های فعال</span><Pagination count={lastPage} page={page} onChange={(_event, value) => { setPage(value); window.scrollTo({ top: 0, behavior: "smooth" }); }} color="primary" /></div>}
        </section>
    );
}
