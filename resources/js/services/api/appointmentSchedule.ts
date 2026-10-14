import type { AppointmentSchedule, AppointmentSettings, PlanningPeriod, SalonWorkingDay, WorkingDayInput } from "../../types/appointmentSchedule";
import { api, initializeCsrf } from "./client";

export async function fetchAppointmentSchedule(from: string, to: string): Promise<AppointmentSchedule> {
    const { data } = await api.get<{ data: AppointmentSchedule }>("/panel/appointment-schedule", { params: { from, to } });
    return data.data;
}

export async function updateAppointmentSettings(input: { planning_period: PlanningPeriod; is_recurring: boolean }): Promise<AppointmentSettings> {
    await initializeCsrf();
    const { data } = await api.put<{ data: AppointmentSettings }>("/panel/appointment-schedule/settings", input);
    return data.data;
}

export async function updateSalonWorkingDay(date: string, input: WorkingDayInput): Promise<SalonWorkingDay> {
    await initializeCsrf();
    const { data } = await api.put<{ data: SalonWorkingDay }>(`/panel/appointment-schedule/days/${date}`, input);
    return data.data;
}
