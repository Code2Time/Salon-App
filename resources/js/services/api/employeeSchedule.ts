import type { EmployeeOption, EmployeeSchedule } from "../../types/employeeSchedule";
import type { WorkingPeriod } from "../../types/appointmentSchedule";
import { api, initializeCsrf } from "./client";

export async function fetchEmployees(): Promise<EmployeeOption[]> {
    const { data } = await api.get<{ data: EmployeeOption[] }>("/panel/employee-schedule/employees");
    return data.data;
}
export async function fetchEmployeeSchedule(from: string, to: string, membershipId?: number): Promise<EmployeeSchedule> {
    const { data } = await api.get<{ data: EmployeeSchedule }>("/panel/employee-schedule", { params: { from, to, ...(membershipId ? { membership_id: membershipId } : {}) } });
    return data.data;
}
export async function saveEmployeeDay(date: string, input: { is_present: boolean; periods: WorkingPeriod[]; membership_id?: number }): Promise<void> {
    await initializeCsrf();
    await api.put(`/panel/employee-schedule/days/${date}`, input);
}
