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
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        links: { url: string | null; label: string; active: boolean }[];
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
};
