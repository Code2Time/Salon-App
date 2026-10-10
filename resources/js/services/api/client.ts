import axios from "axios";
import { fa } from "../../locales/fa";

export interface ApiErrorBody {
    message?: string;
    errors?: Record<string, string[]>;
}

export const api = axios.create({
    baseURL: "/api",
    headers: { Accept: "application/json" },
    withCredentials: true,
    withXSRFToken: true,
});

export async function initializeCsrf(): Promise<void> {
    await axios.get("/sanctum/csrf-cookie", { withCredentials: true });
}

export function readableApiError(error: unknown): string {
    if (!axios.isAxiosError<ApiErrorBody>(error)) {
        return fa.apiErrors.unexpected;
    }

    if (!error.response) return fa.apiErrors.network;

    const status = error.response.status;
    const firstValidationError = Object.values(error.response?.data.errors ?? {})[0]?.[0];
    if (firstValidationError) return translateServerMessage(firstValidationError, fa.apiErrors.validation);

    if (status === 429) {
        const retryAfter = Number(error.response.headers["retry-after"]);
        return Number.isFinite(retryAfter) && retryAfter > 0
            ? fa.apiErrors.tooManyAttemptsWithWait(retryAfter)
            : fa.apiErrors.tooManyAttempts;
    }

    const statusMessages: Partial<Record<number, string>> = {
        401: fa.apiErrors.unauthorized,
        403: fa.apiErrors.forbidden,
        419: fa.apiErrors.csrfExpired,
        422: fa.apiErrors.validation,
        500: fa.apiErrors.server,
        502: fa.apiErrors.server,
        503: fa.apiErrors.server,
    };
    const serverMessage = error.response.data.message;

    return (serverMessage && translateServerMessage(serverMessage, statusMessages[status] ?? fa.apiErrors.unexpected))
        || statusMessages[status]
        || fa.apiErrors.unexpected;
}

function translateServerMessage(message: string, fallback: string): string {
    const translated = fa.serverMessages[message as keyof typeof fa.serverMessages];
    if (translated) return translated;

    return /[\u0600-\u06ff]/.test(message) ? message : fallback;
}
