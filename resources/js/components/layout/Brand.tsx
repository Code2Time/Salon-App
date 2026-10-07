import { Flower2 } from "lucide-react";
import { Link } from "react-router";

export default function Brand({ customer = false }: { customer?: boolean }) {
    return (
        <Link
            className="brand"
            to={customer ? "/app" : "/"}
            aria-label={customer ? "خانهٔ سالن آرا" : "انتخاب پیش‌نمایش آرا"}
        >
            <span className="brand-symbol">
                <Flower2 size={29} strokeWidth={1.4} />
            </span>
            <span>
                <strong>{customer ? "سالن زیبایی آرا" : "آرا"}</strong>
                <small>
                    {customer
                        ? "سالن نمونه · پیش‌نمایش"
                        : "همراه زیبایی کسب‌وکار شما"}
                </small>
            </span>
        </Link>
    );
}
