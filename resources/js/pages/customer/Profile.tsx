import { Avatar, Button, Chip, Paper, TextField, Typography } from "@mui/material";
import { BadgeCheck, Camera, Save, ShieldCheck, Smartphone } from "lucide-react";
import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { toast } from "react-toastify";
import { useAuth } from "../../auth/AuthContext";
import JalaliDatePicker from "../../components/forms/JalaliDatePicker";
import { defaultAvatarUrl } from "../../constants/assets";
import { readableApiError } from "../../services/api/client";
import { updateCustomerProfile } from "../../services/api/profile";

export default function CustomerProfile() {
    const { user, setAuthenticatedUser } = useAuth();
    const [firstName, setFirstName] = useState(user?.first_name ?? "");
    const [lastName, setLastName] = useState(user?.last_name ?? "");
    const [email, setEmail] = useState(user?.email ?? "");
    const [birthDate, setBirthDate] = useState(user?.birth_date ?? "");
    const [image, setImage] = useState<File>();
    const [imagePreview, setImagePreview] = useState(user?.image_url ?? null);
    const [saving, setSaving] = useState(false);

    useEffect(() => () => {
        if (imagePreview?.startsWith("blob:")) URL.revokeObjectURL(imagePreview);
    }, [imagePreview]);

    async function submit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);

        try {
            const updated = await updateCustomerProfile({
                first_name: firstName,
                last_name: lastName,
                email: email || null,
                birth_date: birthDate || null,
                ...(image ? { image } : {}),
            });
            setAuthenticatedUser(updated);
            setImage(undefined);
            setImagePreview(updated.image_url);
            toast.success("اطلاعات پروفایل شما ذخیره شد.");
        } catch (error) {
            toast.error(readableApiError(error));
        } finally {
            setSaving(false);
        }
    }

    return (
        <section className="customer-profile-page">
            <div className="customer-profile-heading">
                <span className="eyebrow">حساب شخصی شما</span>
                <Typography component="h1" variant="h2">پروفایل من</Typography>
                <Typography color="text.secondary">
                    اطلاعات تماس و مشخصات فردی‌تان را همیشه به‌روز نگه دارید.
                </Typography>
            </div>

            <div className="customer-profile-layout">
                <Paper className="customer-profile-identity">
                    <span className="customer-profile-glow" aria-hidden="true" />
                    <Avatar src={imagePreview ?? defaultAvatarUrl} alt={user?.name ?? "تصویر پروفایل"} />
                    <Typography component="h2" variant="h3">{user?.name}</Typography>
                    <Chip icon={<BadgeCheck size={15} />} label="شماره موبایل تأیید شده" size="small" />
                    <div className="customer-profile-mobile">
                        <Smartphone size={17} />
                        <span dir="ltr">{user?.mobile}</span>
                    </div>
                    <div className="customer-profile-privacy">
                        <ShieldCheck size={20} />
                        <p>اطلاعات شما فقط برای ارائه بهتر خدمات سالن استفاده می‌شود.</p>
                    </div>
                </Paper>

                <Paper variant="outlined" className="customer-profile-form-card">
                    <form onSubmit={submit} className="customer-profile-form">
                        <div className="customer-profile-photo">
                            <div>
                                <Typography component="h2" variant="h3">تصویر پروفایل</Typography>
                                <Typography variant="body2" color="text.secondary">
                                    تصویر JPG، PNG یا WebP تا حداکثر ۲ مگابایت
                                </Typography>
                            </div>
                            <Button component="label" color="inherit" variant="outlined">
                                <Camera size={17} /> تغییر تصویر
                                <input
                                    hidden
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(event) => {
                                        const file = event.target.files?.[0];
                                        if (!file) return;
                                        setImage(file);
                                        setImagePreview(URL.createObjectURL(file));
                                    }}
                                />
                            </Button>
                        </div>
                        <TextField label="نام" required value={firstName} onChange={(event) => setFirstName(event.target.value)} />
                        <TextField label="نام خانوادگی" required value={lastName} onChange={(event) => setLastName(event.target.value)} />
                        <TextField label="شماره موبایل" value={user?.mobile ?? ""} disabled slotProps={{ htmlInput: { dir: "ltr" } }} />
                        <TextField label="ایمیل" type="email" value={email} onChange={(event) => setEmail(event.target.value)} slotProps={{ htmlInput: { dir: "ltr" } }} />
                        <JalaliDatePicker label="تاریخ تولد" value={birthDate} onChange={setBirthDate} disableFuture />
                        <div className="customer-profile-actions">
                            <Button type="submit" variant="contained" disabled={saving}>
                                <Save size={18} /> {saving ? "در حال ذخیره…" : "ذخیره تغییرات"}
                            </Button>
                        </div>
                    </form>
                </Paper>
            </div>
        </section>
    );
}
