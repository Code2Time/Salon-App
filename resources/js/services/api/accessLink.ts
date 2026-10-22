import { api } from "./client";

export interface AccessLink { salon_name: string; url: string | null; }

export async function fetchAccessLink(): Promise<AccessLink> {
    const { data } = await api.get<{ data: AccessLink }>("/panel/access-link");
    return data.data;
}
