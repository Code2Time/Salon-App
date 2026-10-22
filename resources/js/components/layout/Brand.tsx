import { Link } from "react-router";
import { Sparkles } from "lucide-react";
import { useSalon } from "../../salon/SalonContext";

export default function Brand({ customer = false }: { customer?: boolean }) {
    const { salon } = useSalon();
    const name = salon?.name ?? "سالن زیبایی";
    return <Link className="brand" to={customer ? "/app" : "/panel"} aria-label={`خانه ${name}`}>
        <span className="brand-symbol">{salon?.logo_url ? <img src={salon.logo_url} alt="" /> : <Sparkles size={28} />}</span>
        <span><strong>{name}</strong><small>{customer ? "خدمات و رزرو نوبت" : "مدیریت سالن زیبایی"}</small></span>
    </Link>;
}
