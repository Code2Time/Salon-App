import { useEffect, useRef, useState } from "react";
import { Alert, Button, CircularProgress, Paper, TextField, Typography } from "@mui/material";
import { Building2, Check, ImagePlus, Save, Trash2 } from "lucide-react";
import { toast } from "react-toastify";
import { useSalon } from "../../salon/SalonContext";
import { updateSalon } from "../../services/api/salon";
import type { SalonFields } from "../../services/api/salon";
import { readableApiError } from "../../services/api/client";
import "../../../css/salon-settings.css";

const emptyFields: SalonFields = { aftercare_discount_validity_days: 7, name: "", description: "", phone: "", address: "", instagram_url: "", telegram_url: "", whatsapp_url: "", website_url: "" };
const socials = [{ key: "instagram_url", label: "اینستاگرام", placeholder: "https://instagram.com/your-salon" }, { key: "telegram_url", label: "تلگرام", placeholder: "https://t.me/your-salon" }, { key: "whatsapp_url", label: "واتساپ", placeholder: "https://wa.me/989120000000" }, { key: "website_url", label: "وب‌سایت", placeholder: "https://example.com" }] as const;

export default function SalonSettings() {
    const { salon, loading, error, reload, setSalon } = useSalon();
    const [fields, setFields] = useState<SalonFields>(emptyFields);
    const [logo, setLogo] = useState<File | null>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState(false);
    const upload = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!salon) return;
        setFields({ aftercare_discount_validity_days: salon.aftercare_discount_validity_days ?? 7, name: salon.name, description: salon.description ?? "", phone: salon.phone ?? "", address: salon.address ?? "", instagram_url: salon.instagram_url ?? "", telegram_url: salon.telegram_url ?? "", whatsapp_url: salon.whatsapp_url ?? "", website_url: salon.website_url ?? "" });
        setLogo(null); setRemoveLogo(false); setDirty(false);
    }, [salon]);
    useEffect(() => {
        const url = logo ? URL.createObjectURL(logo) : null;
        setPreview(url);
        return () => { if (url) URL.revokeObjectURL(url); };
    }, [logo]);

    function change(key: keyof SalonFields, value: string | number) {
        setFields(current => ({ ...current, [key]: value }));
        setDirty(true);
    }
    async function save(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (saving) return;
        setSaving(true);
        try {
            const updated = await updateSalon(fields, logo, removeLogo);
            setSalon(updated);
            toast.success("تنظیمات سالن ذخیره شد.");
        } catch (caught) { toast.error(readableApiError(caught)); }
        finally { setSaving(false); }
    }
    const image = removeLogo ? null : preview ?? salon?.logo_url;
    if (loading) return <div className="salon-settings-loading"><CircularProgress /><span>در حال دریافت تنظیمات سالن…</span></div>;
    if (error || !salon) return <Alert severity="error" action={<Button onClick={() => void reload()} color="inherit">تلاش دوباره</Button>}>{error ?? "اطلاعات سالن در دسترس نیست."}</Alert>;

    return <section className="salon-settings-page"><header><span className="eyebrow">هویت و اطلاعات سالن</span><Typography variant="h2" component="h1">تنظیمات عمومی سالن</Typography><Typography color="text.secondary">اطلاعاتی که مشتریان در برنامه سالن می‌بینند، از اینجا مدیریت کنید.</Typography></header>
        <form onSubmit={(event) => void save(event)}>
            <fieldset disabled={saving} className="salon-settings-fieldset">
                <div className="salon-settings-layout">
                    <Paper variant="outlined" className="salon-settings-logo"><div className="salon-settings-logo-preview">{image ? <img src={image} alt="پیش‌نمایش لوگوی سالن" /> : <Building2 size={42} />}</div><Typography component="h2" variant="h3">لوگوی سالن</Typography><p>در هدر برنامه، پنل و صفحه QR نمایش داده می‌شود.</p><input ref={upload} type="file" hidden accept="image/jpeg,image/png,image/webp" aria-label="انتخاب لوگوی سالن" onChange={(event) => { const file = event.target.files?.[0]; event.target.value = ""; if (!file) return; if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) { toast.error("تصویر JPG، PNG یا WebP تا حجم ۲ مگابایت انتخاب کنید."); return; } setLogo(file); setRemoveLogo(false); setDirty(true); }} /><Button variant="outlined" onClick={() => upload.current?.click()} startIcon={<ImagePlus size={17} />}>{image ? "تغییر لوگو" : "انتخاب لوگو"}</Button>{image && <Button color="error" onClick={() => { setLogo(null); setRemoveLogo(true); setDirty(true); }} startIcon={<Trash2 size={16} />}>حذف لوگو</Button>}<small>JPG، PNG یا WebP · حداکثر ۲ مگابایت</small></Paper>
                    <div className="salon-settings-sections">
                        <Paper variant="outlined" className="salon-settings-section"><Typography variant="h3" component="h2">معرفی سالن</Typography><p>نام و معرفی شما در صفحات برنامه مشتری نمایش داده می‌شود.</p><TextField required fullWidth label="نام سالن" value={fields.name} onChange={event => change("name", event.target.value)} slotProps={{ htmlInput: { maxLength: 100 } }} /><TextField fullWidth multiline minRows={4} label="معرفی سالن" value={fields.description} onChange={event => change("description", event.target.value)} slotProps={{ htmlInput: { maxLength: 3000 } }} helperText="درباره خدمات، تجربه و ویژگی‌های سالن بنویسید." /></Paper>
                        <Paper variant="outlined" className="salon-settings-section"><Typography variant="h3" component="h2">اطلاعات تماس</Typography><p>شماره و نشانی برای ارتباط و مراجعه مشتریان.</p><TextField fullWidth label="شماره تماس" value={fields.phone} onChange={event => change("phone", event.target.value)} slotProps={{ htmlInput: { maxLength: 30, dir: "ltr", inputMode: "tel" } }} /><TextField fullWidth multiline minRows={2} label="آدرس سالن" value={fields.address} onChange={event => change("address", event.target.value)} slotProps={{ htmlInput: { maxLength: 500 } }} /></Paper>
                        <Paper variant="outlined" className="salon-settings-section"><Typography variant="h3" component="h2">تخفیف محصولات مراقبتی</Typography><p>پس از ثبت انجام خدمت، محصولات مرتبط به مشتری پیشنهاد می‌شوند.</p><TextField required type="number" label="مدت اعتبار تخفیف (روز)" value={fields.aftercare_discount_validity_days} onChange={event => change("aftercare_discount_validity_days", Number(event.target.value))} slotProps={{ htmlInput: { min: 1, max: 365, dir: "ltr" } }} helperText="از زمان صدور کد، بین ۱ تا ۳۶۵ روز. هر کد فقط یک‌بار در یک سفارش قابل استفاده است و با کد دیگری ترکیب نمی‌شود. تغییر این مقدار فقط برای کدهای جدید اعمال می‌شود." /></Paper>
                        <Paper variant="outlined" className="salon-settings-section"><Typography variant="h3" component="h2">شبکه‌های اجتماعی</Typography><p>لینک کامل وارد کنید؛ فیلدهای بدون مقدار نمایش داده نمی‌شوند.</p><div className="salon-social-fields">{socials.map(social => <TextField key={social.key} fullWidth label={social.label} type="url" placeholder={social.placeholder} value={fields[social.key]} onChange={event => change(social.key, event.target.value)} slotProps={{ htmlInput: { maxLength: 500, dir: "ltr" } }} />)}</div></Paper>
                    </div>
                </div>
            </fieldset>
            <Paper variant="outlined" className="salon-settings-save"><span>{dirty ? "تغییرات ذخیره‌نشده دارید" : <><Check size={16} />اطلاعات سالن به‌روز است</>}</span><Button type="submit" variant="contained" disabled={saving || !dirty || !fields.name.trim()} startIcon={saving ? <CircularProgress color="inherit" size={16} /> : <Save size={17} />}>{saving ? "در حال ذخیره…" : "ذخیره تنظیمات"}</Button></Paper>
        </form>
    </section>;
}
