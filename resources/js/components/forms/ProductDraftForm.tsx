import { Alert, Autocomplete, Button, Chip, Dialog, DialogActions, DialogContent, DialogTitle, FormControlLabel, IconButton, Switch, TextField, Typography } from "@mui/material";
import { ImagePlus, Percent, Scissors, X } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import type { FormEvent } from "react";
import type { ProductDraft, ProductServiceOption } from "../../types/product";
import { formatPriceInput, parsePriceInput, priceInputRule } from "../../utils/priceInput";
import { readableApiError } from "../../services/api/client";

interface Props {
    initial: ProductDraft;
    services: ProductServiceOption[];
    servicesLoading: boolean;
    servicesError: string | null;
    onRetryServices: () => void;
    onClose: () => void;
    onSave: (draft: ProductDraft) => Promise<void>;
}

export default function ProductDraftForm({ initial, services, servicesLoading, servicesError, onRetryServices, onClose, onSave }: Props) {
    const [form, setForm] = useState(initial);
    const [imageError, setImageError] = useState<string | null>(null);
    const [imageLoading, setImageLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | null>(null);
    const imageRead = useRef(0);
    useEffect(() => () => { imageRead.current += 1; }, []);

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!form.name.trim() || imageLoading) return;
        if (saving) return;
        setSaving(true);
        setSaveError(null);
        try {
            await onSave({ ...form, name: form.name.trim(), recommendations: form.recommendations.map((item) => ({ ...item, recommendation_text: item.recommendation_text.trim(), discount_percent: item.discount_enabled ? item.discount_percent : 0 })) });
        } catch (error) {
            setSaveError(readableApiError(error));
        } finally {
            setSaving(false);
        }
    }

    function updateRecommendation(id: number, changes: Partial<ProductDraft["recommendations"][number]>) {
        setForm((current) => ({ ...current, recommendations: current.recommendations.map((item) => item.service_id === id ? { ...item, ...changes } : item) }));
    }

    function chooseImage(file?: File) {
        if (!file) return;
        setImageError(null);
        if (!["image/jpeg", "image/png", "image/webp"].includes(file.type) || file.size > 2 * 1024 * 1024) {
            setImageError("تصویر JPG، PNG یا WebP تا ۲ مگابایت انتخاب کنید.");
            return;
        }
        const token = ++imageRead.current;
        const reader = new FileReader();
        setImageLoading(true);
        reader.onload = () => {
            if (token !== imageRead.current) return;
            setForm((current) => ({ ...current, image_url: String(reader.result), image_file: file, remove_image: false }));
            setImageLoading(false);
        };
        reader.onerror = () => {
            if (token !== imageRead.current) return;
            setImageError("تصویر خوانده نشد؛ دوباره انتخاب کنید.");
            setImageLoading(false);
        };
        reader.readAsDataURL(file);
    }

    return (
        <Dialog open onClose={saving ? undefined : onClose} fullWidth maxWidth="md" aria-labelledby="product-form-title" slotProps={{ paper: { className: "product-form-dialog" } }}>
            <form onSubmit={submit}>
                <DialogTitle id="product-form-title" className="product-dialog-title">
                    <span><Typography component="span" variant="h3">{initial.name ? "ویرایش محصول" : "محصول جدید"}</Typography><small>اطلاعات محصول و مراقبت‌های پس از خدمت</small></span>
                    <IconButton disabled={saving} aria-label="بستن فرم محصول" onClick={onClose}><X size={20} /></IconButton>
                </DialogTitle>
                <DialogContent className="product-form-content">
                    {saveError && <Alert severity="error">{saveError}</Alert>}
                    <section className="product-form-section">
                        <Typography component="h2" variant="h3">مشخصات محصول</Typography>
                        <div className="product-basics-grid">
                            <div className="product-image-editor">
                                {form.image_url ? <img src={form.image_url} alt="پیش‌نمایش تصویر محصول" /> : <ImagePlus size={36} />}
                                <Button component="label" variant="outlined" size="small" disabled={imageLoading}>
                                    {imageLoading ? "خواندن تصویر…" : "انتخاب تصویر"}
                                    <input type="file" hidden accept="image/jpeg,image/png,image/webp" onChange={(event) => { chooseImage(event.target.files?.[0]); event.target.value = ""; }} />
                                </Button>
                                {form.image_url && <Button size="small" color="inherit" onClick={() => setForm({ ...form, image_url: null, image_file: null, remove_image: true })}>حذف تصویر</Button>}
                                <small>JPG، PNG، WebP · تا ۲ مگابایت</small>
                                {imageError && <Alert severity="error">{imageError}</Alert>}
                            </div>
                            <div className="product-fields-grid">
                                <TextField className="product-field-wide" label="نام محصول" value={form.name} required autoFocus onChange={(event) => setForm({ ...form, name: event.target.value })} slotProps={{ htmlInput: { maxLength: 150 } }} />
                                <TextField label="قیمت (تومان)" value={formatPriceInput(form.price)} onChange={(event) => setForm({ ...form, price: parsePriceInput(event.target.value) })} required slotProps={{ htmlInput: { ...priceInputRule, dir: "ltr" } }} />
                                <TextField label="موجودی (عدد)" type="number" value={form.stock} onChange={(event) => setForm({ ...form, stock: Number(event.target.value) })} required slotProps={{ htmlInput: { min: 0, max: 999999, step: 1, inputMode: "numeric" } }} />
                                <TextField className="product-field-wide" label="معرفی و توضیحات محصول" value={form.description} multiline minRows={3} onChange={(event) => setForm({ ...form, description: event.target.value })} slotProps={{ htmlInput: { maxLength: 2000 } }} />
                                <FormControlLabel className="product-field-wide" control={<Switch checked={form.is_active} onChange={(_, checked) => setForm({ ...form, is_active: checked })} />} label="محصول فعال باشد" />
                            </div>
                        </div>
                    </section>
                    <section className="product-form-section">
                        <div className="product-section-heading"><Scissors size={21} /><div><Typography component="h2" variant="h3">خدمات مرتبط و پیشنهاد مراقبتی</Typography><p>پس از ثبت انجام خدمت، محصولات فعال و موجود با این توضیحات به مشتری پیشنهاد می‌شوند.</p></div></div>
                        {servicesError && <Alert severity="warning" action={<Button color="inherit" onClick={onRetryServices}>تلاش دوباره</Button>}>{servicesError}</Alert>}
                        <Autocomplete multiple disableCloseOnSelect options={services} loading={servicesLoading} loadingText="دریافت خدمات سالن…" noOptionsText="خدمتی برای انتخاب وجود ندارد" value={form.recommendations.map((item) => ({ id: item.service_id, name: item.service_name, is_active: true }))} isOptionEqualToValue={(option, value) => option.id === value.id} getOptionLabel={(option) => option.name} onChange={(_, selected) => setForm({ ...form, recommendations: selected.map((service) => form.recommendations.find((item) => item.service_id === service.id) ?? { service_id: service.id, service_name: service.name, recommendation_text: "", recommendation_enabled: true, discount_enabled: false, discount_percent: 10 }) })} renderInput={(params) => <TextField {...params} label="انتخاب خدمات مرتبط" helperText="چند خدمت را پشت سر هم انتخاب کنید؛ فهرست با انتخاب هر خدمت باز می‌ماند." />} />
                        <div className="product-recommendations">
                            {form.recommendations.map((item) => (
                                <div key={item.service_id} className="product-recommendation-editor">
                                    <div className="product-recommendation-heading"><Chip icon={<Scissors size={15} />} label={item.service_name} /><FormControlLabel control={<Switch checked={item.recommendation_enabled} onChange={(_, checked) => updateRecommendation(item.service_id, { recommendation_enabled: checked })} />} label="پیشنهاد به مشتری" /></div>
                                    <TextField fullWidth label="چرا این محصول بعد از این خدمت پیشنهاد می‌شود؟" placeholder="مثلاً: برای حفظ ماندگاری کراتین، از شامپوی بدون سولفات استفاده کنید." multiline minRows={2} value={item.recommendation_text} required={item.recommendation_enabled} disabled={!item.recommendation_enabled} onChange={(event) => updateRecommendation(item.service_id, { recommendation_text: event.target.value })} slotProps={{ htmlInput: { maxLength: 1000 } }} />
                                    <div className="product-discount-row"><span className="product-discount-icon"><Percent size={20} /></span><FormControlLabel control={<Switch disabled={!item.recommendation_enabled} checked={item.discount_enabled} onChange={(_, checked) => updateRecommendation(item.service_id, { discount_enabled: checked, discount_percent: item.discount_percent || 10 })} />} label="تخفیف ویژه پس از خدمت" />
                                        {item.discount_enabled && <TextField label="درصد تخفیف" type="number" size="small" value={item.discount_percent} disabled={!item.recommendation_enabled} required={item.recommendation_enabled} onChange={(event) => updateRecommendation(item.service_id, { discount_percent: Number(event.target.value) })} slotProps={{ htmlInput: { min: 1, max: 100, step: 1, inputMode: "numeric" } }} />}
                                    </div>
                                    <div className="product-offer-preview"><span>پیش‌نمایش پیشنهاد</span><p>{item.recommendation_enabled ? item.recommendation_text || "توضیح مراقبتی خود را وارد کنید." : "پیشنهاد این محصول برای این خدمت غیرفعال است."}</p><strong>{formatPriceInput(item.recommendation_enabled && item.discount_enabled ? form.price - Math.floor(form.price * item.discount_percent / 100) : form.price)} تومان</strong>{item.recommendation_enabled && item.discount_enabled && <small> با {formatPriceInput(item.discount_percent)}٪ تخفیف</small>}</div>
                                </div>
                            ))}
                        </div>
                    </section>
                </DialogContent>
                <DialogActions className="product-form-actions"><Button color="inherit" onClick={onClose} disabled={saving}>انصراف</Button><Button type="submit" variant="contained" disabled={imageLoading || saving}>{saving ? "در حال ذخیره…" : "ذخیره محصول"}</Button></DialogActions>
            </form>
        </Dialog>
    );
}
