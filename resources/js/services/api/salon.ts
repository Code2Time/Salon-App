import { api, initializeCsrf } from "./client";

export interface SalonDetails {
    aftercare_discount_validity_days?: number;
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
    description: string | null;
    phone: string | null;
    address: string | null;
    instagram_url: string | null;
    telegram_url: string | null;
    whatsapp_url: string | null;
    website_url: string | null;
}
export type SalonFields = { aftercare_discount_validity_days: number } & Pick<SalonDetails, "name" | "description" | "phone" | "address" | "instagram_url" | "telegram_url" | "whatsapp_url" | "website_url">;

export async function fetchSalon(panel = false): Promise<SalonDetails> {
    const { data } = await api.get<{ data: SalonDetails }>(panel ? "/panel/salon" : "/catalog/salon");
    return data.data;
}
export async function updateSalon(fields: SalonFields, logo: File | null, removeLogo: boolean): Promise<SalonDetails> {
    await initializeCsrf();
    const form = new FormData();
    Object.entries(fields).forEach(([key, value]) => form.append(key, String(value ?? "")));
    if (logo) form.append("logo", logo);
    form.append("remove_logo", removeLogo ? "1" : "0");
    const { data } = await api.post<{ data: SalonDetails }>("/panel/salon", form);
    return data.data;
}
