import type { Notice } from "../../types/notification";
import { api, initializeCsrf } from "./client";

export async function fetchNotifications(): Promise<Notice[]> {
    const { data } = await api.get<{ data: Notice[] }>("/notifications");
    return data.data;
}

export async function markNotificationRead(id: string): Promise<void> {
    await initializeCsrf();
    await api.post(`/notifications/${id}/read`);
}

export async function markAllNotificationsRead(): Promise<void> {
    await initializeCsrf();
    await api.post("/notifications/read-all");
}
