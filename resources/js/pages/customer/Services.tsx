import { Alert, Avatar, Button, Chip, CircularProgress, Dialog, DialogActions, DialogContent, IconButton, Paper, Typography } from "@mui/material";
import { ArrowLeft, Clock3, Sparkles, UserRound, WalletCards, X } from "lucide-react";
import { useCallback, useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { defaultAvatarUrl, logoUrl } from "../../constants/assets";
import { readableApiError } from "../../services/api/client";
import { fetchCustomerServices } from "../../services/api/customerServices";
import type { CustomerService } from "../../types/customerService";

const numberFormat = new Intl.NumberFormat("fa-IR");

export default function CustomerServices() {
    const navigate = useNavigate();
    const [services, setServices] = useState<CustomerService[]>([]);
    const [salonName, setSalonName] = useState("سالن آرا");
    const [selected, setSelected] = useState<CustomerService | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const result = await fetchCustomerServices({ per_page: 100 });
            setServices(result.data);
            setSalonName(result.salon.name);
        } catch (caught) {
            setError(readableApiError(caught));
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { void load(); }, [load]);

    return (
        <section className="customer-services-page">
            <header className="customer-services-heading">
                <div>
                    <span className="eyebrow">منوی زیبایی شما</span>
                    <Typography component="h1" variant="h2">خدمات {salonName}</Typography>
                    <Typography color="text.secondary">برای خودتان وقت بگذارید؛ جزئیات هر خدمت را ببینید و انتخاب کنید.</Typography>
                </div>
                <span className="customer-services-count"><Sparkles size={17} /> {numberFormat.format(services.length)} انتخاب برای شما</span>
            </header>

            {error && <Alert severity="error" action={<Button color="inherit" onClick={() => void load()}>تلاش دوباره</Button>}>{error}</Alert>}
            {loading ? <div className="customer-services-loading"><CircularProgress aria-label="در حال دریافت خدمات" /></div> : services.length === 0 ? (
                <Paper variant="outlined" className="customer-services-empty"><Sparkles size={38} /><Typography variant="h3">هنوز خدمتی ثبت نشده است</Typography><Typography color="text.secondary">به‌زودی انتخاب‌های تازه‌ای اینجا خواهید دید.</Typography></Paper>
            ) : (
                <div className="customer-service-grid">
                    {services.map((service) => (
                        <Paper variant="outlined" className="customer-service-card" key={service.id}>
                            <div
                                className={`customer-service-visual${service.categories[0]?.image_url ? " has-image" : ""}`}
                                style={service.categories[0]?.image_url ? { backgroundImage: `url(${service.categories[0].image_url})` } : undefined}
                            >
                                <span>{service.categories[0]?.name || "خدمات زیبایی"}</span>
                                {!service.categories[0]?.image_url && <img className="customer-service-default-logo" src={logoUrl} alt="لوگوی آرا" />}
                            </div>
                            <div className="customer-service-copy">
                                <div className="customer-service-title">
                                    <div><Typography component="h2" variant="h3">{service.name}</Typography></div>
                                    <Sparkles size={20} />
                                </div>
                                <Typography color="text.secondary" variant="body2">{service.description || "جزئیات این خدمت به‌زودی تکمیل می‌شود."}</Typography>
                            </div>
                            <div className="customer-service-footer">
                                <div><small>شروع قیمت از</small><strong>{numberFormat.format(service.price)} <span>تومان</span></strong></div>
                                <Button color="inherit" onClick={() => setSelected(service)}>مشاهده جزئیات <ArrowLeft size={17} /></Button>
                            </div>
                        </Paper>
                    ))}
                </div>
            )}

            <Dialog open={Boolean(selected)} onClose={() => setSelected(null)} fullWidth maxWidth="sm" slotProps={{ paper: { className: "customer-service-dialog" } }}>
                <div
                    className={`customer-service-dialog-hero${selected?.categories[0]?.image_url ? " has-image" : ""}`}
                    style={selected?.categories[0]?.image_url ? { backgroundImage: `url(${selected.categories[0].image_url})` } : undefined}
                >
                    {!selected?.categories[0]?.image_url && <img className="customer-service-default-logo" src={logoUrl} alt="لوگوی آرا" />}
                    <IconButton aria-label="بستن" onClick={() => setSelected(null)}><X size={19} /></IconButton>
                    <div className="customer-service-dialog-heading">
                        <span>معرفی خدمت</span>
                        <Typography component="h2" variant="h2">{selected?.name}</Typography>
                        <div>{selected?.categories.map((category) => <Chip key={category.id} label={category.name} size="small" />)}</div>
                    </div>
                </div>
                <DialogContent>
                    <section className="customer-service-dialog-about">
                        <span className="customer-service-detail-label">درباره این خدمت</span>
                        <Typography className="customer-service-dialog-description" color="text.secondary">{selected?.description || "توضیحات این خدمت به‌زودی تکمیل می‌شود."}</Typography>
                    </section>
                    <div className="customer-service-dialog-facts">
                        <div><span className="customer-service-fact-icon"><WalletCards size={20} /></span><span><small>هزینه خدمت</small><strong>{numberFormat.format(selected?.price ?? 0)} تومان</strong></span></div>
                        <div><span className="customer-service-fact-icon"><Clock3 size={20} /></span><span><small>زمان موردنیاز</small><strong>{numberFormat.format(selected?.duration_minutes ?? 0)} دقیقه</strong></span></div>
                    </div>
                    <div className="customer-service-dialog-providers">
                        <div className="customer-service-dialog-section-title"><span><UserRound size={19} /></span><div><strong>متخصصان این خدمت</strong><small>انتخاب متخصص در مرحله رزرو فعال خواهد شد.</small></div></div>
                        {selected?.providers.length ? <div className="customer-service-provider-grid">{selected.providers.map((provider) => <div key={provider.name}><Avatar src={provider.image_url || defaultAvatarUrl} alt={provider.name} /><span>{provider.name}</span></div>)}</div> : <Typography variant="body2" color="text.secondary">متخصص مناسب هنگام رزرو مشخص می‌شود.</Typography>}
                    </div>
                </DialogContent>
                <DialogActions><Typography variant="body2">متخصص و زمان مناسب را در مرحله بعد انتخاب کنید.</Typography><Button variant="contained" disabled={!selected?.providers.length} onClick={() => selected && navigate(`/app/booking?service=${selected.id}`)}>انتخاب متخصص و رزرو</Button></DialogActions>
            </Dialog>
        </section>
    );
}
