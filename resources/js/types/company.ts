export type Company = {
    id: number;
    name: string;
    tax_id: string;
    vat_number: string | null;
    is_vat_registered: boolean;
    address: string | null;
    email: string | null;
    phone: string | null;
    created_by: number;
    creator?: { id: number; name: string };
    created_at: string;
    updated_at: string;
};

export type PaginatedCompanies = {
    data: Company[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
    next_page_url: string | null;
    prev_page_url: string | null;
};
