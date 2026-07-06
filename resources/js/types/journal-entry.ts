export type JournalGroup = {
    code: number;
    name: string;
    description?: string | null;
};

export type JournalEntryStatus = 'draft' | 'posted';

export const JOURNAL_STATUS_LABELS: Record<JournalEntryStatus, string> = {
    draft:  'Нацрт',
    posted: 'Прокнижено',
};

export const JOURNAL_STATUS_VARIANT: Record<JournalEntryStatus, 'outline' | 'default'> = {
    draft:  'outline',
    posted: 'default',
};

export type Kooperant = {
    id: number;
    name: string;
    edb: string;
    embs?: string | null;
    address?: string | null;
    phone?: string | null;
    email?: string | null;
};

export type JournalEntryLine = {
    id: number;
    sort_order: number;
    account_code: string;
    account?: { code: string; name: string; class?: number; account_type?: string } | null;
    kooperant_id?: number | null;
    kooperant?: Kooperant | null;
    line_date?: string | null;
    closing_reference?: string | null;
    debit: string;
    credit: string;
    description: string | null;
};

export type JournalEntry = {
    id: number;
    document_id: number | null;
    company_id: number;
    group_code: number | null;
    year: number | null;
    sequence_number: number | null;
    entry_date: string;
    description: string;
    reference: string | null;
    status: JournalEntryStatus;
    created_by: number;
    posted_by: number | null;
    posted_at: string | null;
    created_at: string;
    company?: { id: number; name: string } | null;
    document?: { id: number; original_filename: string } | null;
    creator?: { id: number; name: string } | null;
    poster?: { id: number; name: string } | null;
    journal_group?: JournalGroup | null;
    lines?: JournalEntryLine[];
};
