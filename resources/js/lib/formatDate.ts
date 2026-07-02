export function formatDate(d: string | null | undefined): string {
    if (!d) return '—';
    const part = d.includes('T') ? d.split('T')[0] : d;
    const [y, m, day] = part.split('-');
    return `${day}.${m}.${y}`;
}