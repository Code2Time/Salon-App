import { Avatar, Button, Paper, TextField, Typography } from "@mui/material";
import { ImagePlus, Save } from "lucide-react";
import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { toast } from "react-toastify";
import { useAuth } from "../../auth/AuthContext";
import { readableApiError } from "../../services/api/client";
import { updateProfile } from "../../services/api/profile";
import { defaultAvatarUrl } from "../../constants/assets";
import JalaliDatePicker from "../../components/forms/JalaliDatePicker";

export default function Profile() {
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
            const updated = await updateProfile({
                first_name: firstName,
                last_name: lastName,
                email: email || null,
                birth_date: birthDate || null,
                ...(image ? { image } : {}),
            });
            setAuthenticatedUser(updated);
            setImage(undefined);
            setImagePreview(updated.image_url);
            toast.success("پروفایل شما به‌روز شد.");
        } catch (caught) {
            toast.error(readableApiError(caught));
        } finally {
            setSaving(false);
        }
    }

    return (
        <section>
            <div className="page-heading">
                <span className="eyebrow">حساب کاربری من</span>
                <Typography variant="h2" component="h1">پروفایل</Typography>
                <Typography color="text.secondary">اطلاعات شخصی و تصویر نمایش‌داده‌شده در پنل را مدیریت کنید.</Typography>
            </div>
            <Paper variant="outlined" className="profile-card">
                <form onSubmit={submit} className="profile-form">
                    <div className="profile-avatar-section">
                        <Avatar src={imagePreview ?? defaultAvatarUrl} alt="تصویر پروفایل" />
                        <div>
                            <Typography variant="h3">تصویر پروفایل</Typography>
                            <Typography color="text.secondary" variant="body2">JPG، PNG یا WebP؛ حداکثر ۲ مگابایت</Typography>
                            <Button component="label" color="inherit">
                                <ImagePlus size={18} /> انتخاب تصویر
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
                    </div>
                    <TextField label="نام" required value={firstName} onChange={(event) => setFirstName(event.target.value)} />
                    <TextField label="نام خانوادگی" required value={lastName} onChange={(event) => setLastName(event.target.value)} />
                    <TextField label="شماره موبایل" value={user?.mobile ?? ""} disabled slotProps={{ htmlInput: { dir: "ltr" } }} />
                    <TextField label="ایمیل" type="email" value={email} onChange={(event) => setEmail(event.target.value)} slotProps={{ htmlInput: { dir: "ltr" } }} />
                    <JalaliDatePicker
                        label="تاریخ تولد"
                        value={birthDate}
                        onChange={setBirthDate}
                        disableFuture
                    />
                    <div className="profile-actions">
                        <Button type="submit" variant="contained" disabled={saving}><Save size={18} /> {saving ? "در حال ذخیره…" : "ذخیره تغییرات"}</Button>
                    </div>
                </form>
            </Paper>
        </section>
    );
}
