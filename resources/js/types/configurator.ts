export type ConfiguratorImage = {
    url: string;
    alt: string;
};

export type ConfiguratorSpecification = {
    key: string;
    label: string;
    value: string;
};

export type ConfiguratorAvailability = {
    status:
        | 'available'
        | 'limited'
        | 'unavailable'
        | 'made_to_order';

    label: string;
    available_builds: number | null;
};

export type ConfiguratorCompatibilityMessage = {
    rule_key: string;
    severity: 'error' | 'warning' | 'info';
    message: string;
};

export type ConfiguratorOption = {
    id: number;
    sku: string;

    name: string;
    brand: string | null;
    description: string | null;

    image: ConfiguratorImage | null;

    specifications: ConfiguratorSpecification[];

    is_selected: boolean;
    is_base: boolean;
    selectable: boolean;

    price: {
        amount_in_cents: number;
        delta_from_current_in_cents: number;
        delta_from_base_in_cents: number;
        currency: string;
    };

    availability: ConfiguratorAvailability;

    compatibility: {
        status:
            | 'compatible'
            | 'warning'
            | 'requires_changes';

        has_errors: boolean;

        messages: ConfiguratorCompatibilityMessage[];
    };
};

export type ConfiguratorGroupIssue = {
    rule_key: string;
    severity: string;
    message: string;
};

export type ConfiguratorGroup = {
    slot: string;
    label: string;
    description: string;

    sort_order: number;

    is_required: boolean;
    is_replaceable: boolean;
    can_edit: boolean;

    selection_mode: 'single' | 'multiple';

    selected: ConfiguratorOption | null;

    selected_price_change_from_base_in_cents: number;

    issues: ConfiguratorGroupIssue[];

    options: ConfiguratorOption[];
};

export type ConfiguratorValidationIssue = {
    rule_key: string;
    message: string;
    context: Record<string, unknown>;
};

export type ConfiguratorConfiguration = {
    public_id: string;
    name: string;

    status:
        | 'draft'
        | 'valid'
        | 'invalid'
        | 'converted'
        | 'expired';

    version: number;
    read_only: boolean;
    updated_at: string | null;

    source_system: {
        id: number;
        name: string;
        slug: string;
        details_url: string;
        image: ConfiguratorImage | null;
    };

    pricing: {
        currency: string;
        base_price_in_cents: number;
        component_change_in_cents: number;
        subtotal_in_cents: number;
        adjustment_total_in_cents: number;
        tax_in_cents: number;
        total_in_cents: number;
        prices_include_tax: boolean;
    };

    availability: ConfiguratorAvailability;

    validation: {
        status: string;
        label: string;
        is_current: boolean;
        completed_at: string | null;

        errors: ConfiguratorValidationIssue[];
        warnings: ConfiguratorValidationIssue[];
    };

    can_review: boolean;
};

export type ConfiguratorPageProps = {
    configuration: ConfiguratorConfiguration;
    groups: ConfiguratorGroup[];
};