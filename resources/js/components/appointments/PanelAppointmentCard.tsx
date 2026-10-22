import { Button, IconButton, Paper, Tooltip } from "@mui/material";
import { ArrowLeft, CalendarDays, Check, ChevronDown, Clock3, MoreHorizontal, Pencil, Smartphone, Store } from "lucide-react";
import { useState } from "react";
import type { Appointment } from "../../types/appointment";
import "../../../css/panel-appointments.css";

const number = new Intl.NumberFormat("fa-IR");
const timeLabel = (value: string) => value.slice(11, 16).replace(/\d/g, (digit) => "۰۱۲۳۴۵۶۷۸۹".charAt(Number(digit)));
const labels: Record<Appointment["status"], string> = { confirmed: "تأییدشده", completed: "انجام‌شده", cancelled: "لغوشده", no_show: "عدم حضور", pending: "در انتظار", rejected: "ردشده", expired: "باطل‌شده" };
const dateFormat = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { weekday: "long", day: "numeric", month: "long" });

interface Props {
    appointment: Appointment;
    canDelete: boolean;
    busy: boolean;
    onEdit: (appointment: Appointment) => void;
    onComplete: (id: number) => void;
    onMore: (anchor: HTMLElement, appointment: Appointment) => void;
}

export default function PanelAppointmentCard({ appointment: item, canDelete, busy, onEdit, onComplete, onMore }: Props) {
    const [expanded, setExpanded] = useState(false);
    const date = dateFormat.format(new Date(`${item.starts_at.slice(0, 10)}T12:00:00`));
    const duration = item.items.reduce((sum, part) => sum + part.duration_minutes, 0);
    const canComplete = item.status === "confirmed" || (item.status === "expired" && canDelete);
    const hasActions = item.status === "confirmed" || canDelete;

    return <Paper component="article" variant="outlined" className={`booking-record status-${item.status}`} aria-label={`نوبت ${item.customer?.name ?? "مشتری"}، ${date}`}>
        <header className="booking-record-top">
            <div className="booking-record-reference"><span className="booking-record-date"><CalendarDays size={14} />{date}</span><span className="booking-record-id">#{number.format(item.id)}</span><span className="booking-record-source">{item.source === "panel" ? <Store size={13} /> : <Smartphone size={13} />}{item.source === "panel" ? "حضوری" : "رزرو آنلاین"}</span></div>
            <div className="booking-record-controls"><span className="booking-record-status"><i aria-hidden="true" />{labels[item.status]}</span>{hasActions && <IconButton size="small" disabled={busy} aria-label={`عملیات نوبت شماره ${number.format(item.id)}`} onClick={(event) => onMore(event.currentTarget, item)}><MoreHorizontal size={20} /></IconButton>}</div>
        </header>

        <div className="booking-record-summary">
            <div className="booking-record-time"><strong><bdi>{timeLabel(item.starts_at)}</bdi></strong><span>تا <bdi>{timeLabel(item.ends_at)}</bdi></span></div>
            <div className="booking-record-customer"><span className="booking-record-avatar" aria-hidden="true">{item.customer?.name.trim().slice(0, 1) || "م"}</span><div><h3>{item.customer?.name ?? "مشتری"}</h3>{item.customer?.mobile ? <a href={`tel:${item.customer.mobile}`} aria-label={`تماس با ${item.customer.name}`}><bdi>{item.customer.mobile}</bdi></a> : <small>شماره ثبت نشده</small>}</div></div>
            <div className="booking-record-services">{item.items.slice(0, 2).map((part) => <div key={part.id}><strong>{part.service_name}</strong><span><ArrowLeft size={12} aria-hidden="true" />{part.employee_name || "متخصص مشخص نشده"}</span></div>)}{item.items.length > 2 && <small>و {number.format(item.items.length - 2)} خدمت دیگر</small>}{!item.items.length && <small>خدمتی ثبت نشده است</small>}</div>
            <div className="booking-record-price"><small>مبلغ نوبت</small><span><strong>{number.format(item.total_price)}</strong><small>تومان</small></span></div>
        </div>

        <footer className="booking-record-bottom">
            <div className="booking-record-info"><span><Clock3 size={14} />{number.format(duration)} دقیقه</span><span className="booking-record-service-count">{number.format(item.items.length)} خدمت</span><Button size="small" aria-expanded={expanded} aria-controls={expanded ? `booking-detail-${item.id}` : undefined} onClick={() => setExpanded(!expanded)} endIcon={<ChevronDown size={15} className={expanded ? "is-open" : ""} />}>{expanded ? "بستن جزئیات" : "جزئیات نوبت"}</Button></div>
            {canComplete && <div className="booking-record-actions">{item.status === "confirmed" && <Tooltip title="تغییر زمان یا متخصص"><Button size="small" disabled={busy} onClick={() => onEdit(item)} startIcon={<Pencil size={14} />}>جابه‌جایی</Button></Tooltip>}<Button size="small" variant="outlined" className="booking-record-complete" disabled={busy} onClick={() => onComplete(item.id)} startIcon={<Check size={15} />}>{item.status === "expired" ? "ثبت انجام واقعی خدمت" : "ثبت انجام خدمت"}</Button></div>}
        </footer>

        {expanded && <div className="booking-record-expanded" id={`booking-detail-${item.id}`}>
            <div className="booking-record-detail-heading"><strong>جزئیات خدمات</strong><span>{number.format(item.items.length)} خدمت در این نوبت</span></div>
            <ol className="booking-record-timeline">{item.items.map((part, index) => <li key={part.id}><span className="booking-record-step">{number.format(index + 1)}</span><div className="booking-record-service-name"><strong>{part.service_name}</strong><small>{part.employee_name} · {number.format(part.duration_minutes)} دقیقه</small></div><div className="booking-record-service-time"><bdi>{timeLabel(part.starts_at)} – {timeLabel(part.ends_at)}</bdi><small>{number.format(part.price)} تومان</small></div></li>)}</ol>
            {(item.notes || item.cancellation_reason) && <div className="booking-record-notes">{item.notes && <p><strong>یادداشت نوبت</strong>{item.notes}</p>}{item.cancellation_reason && <p><strong>دلیل لغو</strong>{item.cancellation_reason}</p>}</div>}
        </div>}
    </Paper>;
}
