export type UserRole = 'admin' | 'accountant' | 'company_admin';

export const USER_ROLE_LABELS: Record<UserRole, string> = {
    admin:         'Администратор',
    accountant:    'Сметководител',
    company_admin: 'Клиент',
};

export const USER_ROLE_VARIANT: Record<UserRole, 'default' | 'secondary' | 'outline'> = {
    admin:         'default',
    accountant:    'secondary',
    company_admin: 'outline',
};

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole | null;
    companies: { id: number; name: string }[];
    created_at: string;
};

export type PaginatedUsers = {
    data: ManagedUser[];
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
