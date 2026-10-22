import type { Appointment, AppointmentPage, BookingItemInput } from "../../types/appointment";
import { api, initializeCsrf } from "./client";

export async function fetchAvailability(date: string, items: BookingItemInput[], panel = false): Promise<string[]> {
    await initializeCsrf();
    const { data } = await api.post<{ data: { starts: string[] } }>(`${panel ? "/panel" : ""}/appointments/availability`, { date, items });
    return data.data.starts;
}
export async function createAppointment(input: { date: string; starts_at: string; items: BookingItemInput[]; notes?: string; customer_id?: number }, panel = false): Promise<Appointment> {
    await initializeCsrf();
    const { data } = await api.post<{ data: Appointment }>(`${panel ? "/panel" : ""}/appointments`, input);
    return data.data;
}
export async function fetchMyAppointments(): Promise<AppointmentPage> { const { data } = await api.get<AppointmentPage>("/appointments"); return data; }
export async function cancelMyAppointment(id: number, reason?: string): Promise<void> { await initializeCsrf(); await api.post(`/appointments/${id}/cancel`, { reason }); }
export async function fetchPanelAppointments(params: { date?: string; status?: string; page?: number } = {}): Promise<AppointmentPage> { const { data } = await api.get<AppointmentPage>("/panel/appointments", { params }); return data; }
export async function updateAppointmentStatus(id: number, status: "completed" | "no_show" | "cancelled", reason?: string): Promise<Appointment> { await initializeCsrf(); const { data } = await api.put<{ data: Appointment }>(`/panel/appointments/${id}/status`, { status, reason }); return data.data; }
export async function rescheduleAppointment(id: number, input: { date: string; starts_at: string; items: BookingItemInput[] }): Promise<Appointment> { await initializeCsrf(); const { data } = await api.put<{ data: Appointment }>(`/panel/appointments/${id}/reschedule`, input); return data.data; }
export async function deleteAppointment(id: number): Promise<void> { await initializeCsrf(); await api.delete(`/panel/appointments/${id}`); }
export interface AppointmentOptionService { id: number; name: string; duration_minutes: number; price: number; providers: Array<{ membership_id: number; name: string }> }
export interface AppointmentOptions { can_view_all: boolean; customers: Array<{ id: number; name: string; mobile: string }>; services: AppointmentOptionService[] }
export async function fetchAppointmentOptions(): Promise<AppointmentOptions> { const { data } = await api.get<{ data: AppointmentOptions }>("/panel/appointments/options"); return data.data; }
