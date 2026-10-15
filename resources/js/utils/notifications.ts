import type { Notice } from "../types/notification";

const persianDay = new Intl.DateTimeFormat("fa-IR-u-ca-persian", {
    year: "numeric",
    month: "long",
    day: "numeric",
});

const persianDateTime = new Intl.DateTimeFormat("fa-IR-u-ca-persian", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
});

export function noticeMessage(notice: Notice): string {
    if (!notice.date) return notice.message;

    return notice.message.replace(notice.date, persianDay.format(new Date(`${notice.date}T00:00:00`)));
}

export function noticeCreatedAt(value: string): string {
    return persianDateTime.format(new Date(value));
}
