const persianDigits = "۰۱۲۳۴۵۶۷۸۹";
const arabicDigits = "٠١٢٣٤٥٦٧٨٩";
const priceFormatter = new Intl.NumberFormat("fa-IR", { maximumFractionDigits: 0 });

export const priceInputRule = {
    inputMode: "numeric" as const,
    maxLength: 16,
    pattern: "[0-9۰-۹٠-٩,٬]*",
};

export function parsePriceInput(value: string): number {
    const normalized = [...value].map((character) => {
        const persianIndex = persianDigits.indexOf(character);
        if (persianIndex >= 0) return String(persianIndex);
        const arabicIndex = arabicDigits.indexOf(character);
        return arabicIndex >= 0 ? String(arabicIndex) : character;
    }).join("");
    const digits = normalized.replace(/\D/g, "").slice(0, 12);

    return digits ? Number(digits) : 0;
}

export function formatPriceInput(value: number): string {
    return priceFormatter.format(value);
}
