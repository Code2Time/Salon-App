import { Link } from "react-router";
import { logoUrl } from "../../constants/assets";

export default function Brand({ customer = false }: { customer?: boolean }) {
    return (
        <Link
            className="brand"
            to={customer ? "/app" : "/"}
            aria-label={customer ? "خانهٔ سالن آرا" : "انتخاب پیش‌نمایش آرا"}
        >
            <span className="brand-symbol"><img src={logoUrl} alt="" /></span>
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
