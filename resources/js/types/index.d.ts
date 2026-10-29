export interface User {
    id: number;
    name: string;
    nip?: string | null;
    email: string;
    phone?: string | null;
    is_active: boolean;
    position_id?: number | null;
    position?: Position | null;
    roles?: Role[];
    permissions?: string[];
}

export interface OrgUnit {
    id: number;
    parent_id?: number | null;
    name: string;
    code: string;
    type: string;
    order_index: number;
    children?: OrgUnit[];
    parent?: OrgUnit | null;
}

export interface Position {
    id: number;
    org_unit_id: number;
    name: string;
    level: number;
    is_penandatangan: boolean;
    org_unit?: OrgUnit | null;
}

export interface Role {
    id: number;
    name: string;
    permissions?: { id: number; name: string }[];
}

export interface InstitutionProfile {
    id?: number;
    name: string;
    alias: string;
    address?: string | null;
    email?: string | null;
    phone?: string | null;
    website?: string | null;
    logo_path?: string | null;
    header_image_path?: string | null;
    timezone: string;
    default_theme: string;
    terminology_dict: Record<string, string>;
    is_configured?: boolean;
}

export interface LicenseState {
    id?: number;
    license_key: string;
    institution_slug: string;
    plan: 'dasar' | 'standar' | 'lanjutan';
    status: 'active' | 'grace_period' | 'expired';
    expires_at: string;
    grace_period_days: number;
    max_users: number;
    max_storage_bytes: number;
    active_features: Record<string, boolean>;
    is_read_only: boolean;
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null;
    };
    institution: InstitutionProfile | null;
    license: LicenseState | null;
    flash: FlashMessages;
    is_configured: boolean;
};
