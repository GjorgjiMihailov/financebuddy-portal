export function formatNumber(value: number | string | null | undefined): string {
    const n = Number(value ?? 0);
    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
