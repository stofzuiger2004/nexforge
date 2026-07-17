const dateTimeFormatter = new Intl.DateTimeFormat('en-BE', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

const dateFormatter = new Intl.DateTimeFormat('en-BE', {
    dateStyle: 'medium',
});

export function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return dateTimeFormatter.format(new Date(value));
}

export function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return dateFormatter.format(new Date(value));
}
