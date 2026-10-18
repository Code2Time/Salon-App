import type { DashboardData } from "../../types/dashboard";
import { api } from "./client";

export async function fetchDashboard(): Promise<DashboardData> {
    const { data } = await api.get<{ data: DashboardData }>("/panel/dashboard");
    return data.data;
}
