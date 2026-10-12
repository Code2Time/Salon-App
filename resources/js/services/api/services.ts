import { api, initializeCsrf } from "./client";
import type { PaginatedServices, SalonService, ServiceFilters, ServiceInput, ServiceOptions } from "../../types/service";

export async function fetchServices(filters: ServiceFilters): Promise<PaginatedServices> {
    const { data } = await api.get<PaginatedServices>("/panel/services", { params: filters });
    return data;
}

export async function fetchServiceOptions(): Promise<ServiceOptions> {
    const { data } = await api.get<{ data: ServiceOptions }>("/panel/services/options");
    return data.data;
}

export async function createService(input: ServiceInput): Promise<SalonService> {
    await initializeCsrf();
    const { data } = await api.post<{ data: SalonService }>("/panel/services", input);
    return data.data;
}

export async function updateService(id: number, input: ServiceInput): Promise<SalonService> {
    await initializeCsrf();
    const { data } = await api.put<{ data: SalonService }>(`/panel/services/${id}`, input);
    return data.data;
}

export async function deleteService(id: number): Promise<void> {
    await initializeCsrf();
    await api.delete(`/panel/services/${id}`);
}
