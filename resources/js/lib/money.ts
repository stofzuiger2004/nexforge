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

export function formatSignedMoney(
    amountInCents: number,
    currency: string,
    locale = 'nl-BE',
): string {
    if (amountInCents === 0) {
        return formatMoney(0, currency, locale);
    }

    const sign = amountInCents > 0 ? '+' : '−';

    return `${sign}${formatMoney(Math.abs(amountInCents), currency, locale)}`;
}
