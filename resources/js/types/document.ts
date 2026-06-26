export type DocumentType = 'invoice_in' | 'invoice_out' | 'bank_statement' | 'contract' | 'receipt' | 'other';
export type DocumentStatus = 'pending' | 'ai_processing' | 'ai_processed' | 'verified' | 'booked' | 'rejected';

export const DOCUMENT_TYPE_LABELS: Record<DocumentType, string> = {
    invoice_in:     'Влезна фактура',
    invoice_out:    'Излезна фактура',
    bank_statement: 'Банкарски извод',
    contract:       'Договор',
    receipt:        'Сметка / Уплатница',
    other:          'Друго',
};

export const DOCUMENT_STATUS_LABELS: Record<DocumentStatus, string> = {
    pending:       'Чека обработка',
    ai_processing: 'AI обработка во тек',
    ai_processed:  'AI обработен',
    verified:      'Верификуван',
    booked:        'Прокнижен',
    rejected:      'Одбиен',
};

export const DOCUMENT_STATUS_VARIANT: Record<DocumentStatus, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pending:       'outline',
    ai_processing: 'secondary',
    ai_processed:  'default',
    verified:      'default',
    booked:        'default',
    rejected:      'destructive',
};

export type DocumentExtraction = {
    id: number;
    document_id: number;
    vendor_name: string | null;
    vendor_tax_id: string | null;
    vendor_vat_number: string | null;
    customer_name: string | null;
    customer_tax_id: string | null;
    document_number: string | null;
    document_date: string | null;
    due_date: string | null;
    currency: string;
    subtotal: string;
    vat_amount: string;
    total_amount: string;
    is_confirmed: boolean;
};

export type DocumentLineItem = {
    id: number;
    sort_order: number;
    description: string;
    quantity: string | null;
    unit: string | null;
    unit_price: string | null;
    vat_rate: string;
    vat_amount: string;
    total_amount: string;
    suggested_account_code: string | null;
    confirmed_account_code: string | null;
    ai_confidence: number | null;
    suggested_account?: { code: string; name: string } | null;
};

export type DocumentFile = {
    id: number;
    company_id: number;
    company?: { id: number; name: string };
    uploaded_by: number;
    uploader?: { id: number; name: string };
    type: DocumentType;
    status: DocumentStatus;
    intake_channel: string;
    original_filename: string;
    mime_type: string;
    file_size: number;
    ai_confidence: number | null;
    verified_by: number | null;
    created_at: string;
    updated_at: string;
    extraction?: DocumentExtraction | null;
    line_items?: DocumentLineItem[];
};

export type PaginatedDocuments = {
    data: DocumentFile[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};
