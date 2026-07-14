export function formatMoney(
    amountInCents: number,
    currency: string,
    locale = 'nl-BE',
): string {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
    }).format(amountInCents / 100);
}