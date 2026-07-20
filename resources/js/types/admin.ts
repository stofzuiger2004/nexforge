export type AdminSharedProps = {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        } | null;

        roles: string[];

        can: {
            adminAccess: boolean;
            viewOrders: boolean;
            viewCustomerData: boolean;
            viewPayments: boolean;
            viewInventory: boolean;
            adjustInventory: boolean;
            manageInventory: boolean;
            viewPrices: boolean;
            managePrices: boolean;
            manageCatalog: boolean;
        };
    };
    flash: {
        success: string | null;
    };

    [key: string]: unknown;
};

export type AdminOrderListItem = {
    public_id: string;
    order_number: string;
    href: string;
    customer: {
        name: string;
        email: string;
        has_account: boolean;
    };
    status: string;
    payment_status: string;
    fulfillment_status: string;
    currency: string;
    total_in_cents: number;
    item_count: number;
    placed_at: string | null;
    requires_attention: boolean;
};

export type AdminOrderPaginator = {
    data: AdminOrderListItem[];

    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };

    links: {
        previous: string | null;
        next: string | null;
    };
};

export type AdminFilterOption = {
    value: string;
    label: string;
};

export type AdminOrderFilters = {
    search: string;
    status: string;
    payment_status: string;
    fulfillment_status: string;
    date_from: string;
    date_to: string;
    sort: string;
    per_page: string;
};

export type AdminOrderDetail = {
    public_id: string;
    order_number: string;

    status: string;
    payment_status: string;
    fulfillment_status: string;

    customer: {
        account_id: number | null;
        name: string;
        email: string;
        locale: string;
        has_account: boolean;
    };

    pricing: {
        currency: string;
        subtotal_in_cents: number;
        adjustment_total_in_cents: number;
        shipping_in_cents: number;
        tax_in_cents: number;
        total_in_cents: number;
        paid_in_cents: number;
        refunded_in_cents: number;
        charged_back_in_cents: number;
        outstanding_in_cents: number;
    };

    dates: {
        created_at: string;
        placed_at: string | null;
        paid_at: string | null;
        cancelled_at: string | null;
        completed_at: string | null;
    };

    addresses: AdminOrderAddress[] | null;
    items: AdminOrderItem[];
    adjustments: AdminOrderAdjustment[];

    payments: AdminPayment[] | null;

    inventory_reservations: AdminInventoryReservation[] | null;

    timeline: AdminOrderTimelineItem[];

    references: {
        configuration_public_id: string | null;
        configuration_version: number | null;
        reservation_public_id: string | null;
        terms_version: string | null;
        terms_accepted_at: string | null;
    };

    can: {
        view_customer_data: boolean;
        view_payments: boolean;
        view_inventory: boolean;
    };
};

export type AdminOrderAddress = {
    id: number;
    type: string;

    first_name: string;
    last_name: string;

    company: string | null;
    vat_number: string | null;

    address_line_1: string;
    address_line_2: string | null;

    postal_code: string;
    city: string;
    state: string | null;
    country_code: string;

    email: string;
    phone: string | null;
};

export type AdminOrderItem = {
    id: number;
    line_number: number;
    type: string;

    sku: string | null;
    name: string;
    description: string | null;

    quantity: number;

    unit_price_in_cents: number;
    subtotal_in_cents: number;
    adjustment_total_in_cents: number;
    tax_in_cents: number;
    line_total_in_cents: number;

    configuration: {
        public_id: string | null;
        version: number | null;
    };

    components: {
        id: number;
        slot: string;
        slot_label: string;
        sku: string;
        name: string;
        quantity: number;
        unit_price_in_cents: number | null;
        line_total_in_cents: number | null;
    }[];
};

export type AdminOrderAdjustment = {
    id: number;
    type: string;
    code: string | null;
    label: string;
    amount_in_cents: number;
    tax_in_cents: number;
};

export type AdminPayment = {
    public_id: string;
    attempt_number: number;

    provider: string;
    provider_payment_id: string | null;
    provider_profile_id: string | null;

    status: string;
    provider_status: string | null;
    mode: string | null;
    method: string | null;

    amount_in_cents: number;
    currency: string;

    failure_code: string | null;
    failure_message: string | null;

    webhook_event_count: number;

    dates: Record<string, string | null>;

    refunds: {
        public_id: string;
        provider_refund_id: string | null;
        status: string;
        amount_in_cents: number;
        currency: string;
        reason: string | null;
        created_at: string;
    }[];

    chargebacks: {
        public_id: string;
        provider_chargeback_id: string;
        status: string;
        amount_in_cents: number;
        currency: string;
        reason_code: string | null;
        reason_description: string | null;
        provider_created_at: string;
    }[];
};

export type AdminInventoryReservation = {
    public_id: string;
    status: string;
    configuration_version: number;

    warehouse: {
        code: string;
        name: string;
    };

    reserved_at: string;
    expires_at: string;
    committed_at: string | null;
    released_at: string | null;
    consumed_at: string | null;

    items: {
        id: number;
        sku: string;
        name: string;
        quantity: number;
        released_quantity: number;
        consumed_quantity: number;
        outstanding_quantity: number;
        inventory_item_id: number;
    }[];
};

export type AdminOrderTimelineItem = {
    id: number;
    category: string;
    from_status: string | null;
    to_status: string;
    reason: string | null;
    actor: string | null;
    created_at: string;
};

export type AdminInventoryPrice = {
    amount_in_cents: number;
    compare_at_amount_in_cents: number | null;
    currency: string;
    price_list_name: string;
};

export type AdminInventoryListItem = {
    id: number;
    href: string;

    image: {
        url: string;
        alt: string;
    } | null;

    product: {
        name: string;
        brand: string | null;
    };

    variant: {
        id: number;
        name: string;
        sku: string;
    };

    warehouse: {
        id: number;
        code: string;
        name: string;
    };

    bin_location: string | null;

    stock: {
        quantity_on_hand: number;
        quantity_reserved: number;
        available: number;
        reservable: number;
        safety_stock: number;
        reorder_point: number | null;
        is_active: boolean;
        state: 'in_stock' | 'low_stock' | 'out_of_stock' | 'inactive';
        lock_version: number;
    };

    price?: AdminInventoryPrice | null;
};

export type AdminPaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type AdminPaginationData = {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
    links: AdminPaginationLink[];
};

export type AdminInventoryDetailPrice = {
    id: number;
    price_list: {
        id: number;
        code: string;
        name: string;
        currency: string;
        is_default: boolean;
        is_active: boolean;
    };
    amount_in_cents: number;
    compare_at_amount_in_cents: number | null;
    lock_version: number;
    update_url: string;
};

export type AdminInventoryMovement = {
    public_id: string;
    type: string;
    on_hand_delta: number;
    reserved_delta: number;
    on_hand_after: number;
    reserved_after: number;
    reason: string | null;
    reference: string | null;
    actor: {
        id: number;
        name: string;
        email: string;
    } | null;
    occurred_at: string;
};

export type AdminInventoryDetail = AdminInventoryListItem & {
    last_counted_at: string | null;

    update_url: string;
    adjustment_url: string;

    can: {
        adjust_inventory: boolean;
        manage_inventory: boolean;
        view_prices: boolean;
        manage_prices: boolean;
    };

    prices: AdminInventoryDetailPrice[] | null;

    movements: AdminInventoryMovement[];

    reservations: AdminInventoryItemReservation[];

    form_tokens: {
        adjustment: string;
    };
};

export type AdminInventoryItemReservation = {
    public_id: string;
    status: string;
    quantity: number;
    outstanding_quantity: number;
    expires_at: string | null;

    order: {
        public_id: string;
        order_number: string;
    } | null;
};

export type AdminPriceHistoryItem = {
    id: number;
    price_list: {
        name: string;
        currency: string;
    };
    old_amount_in_cents: number;
    new_amount_in_cents: number;
    old_compare_at_amount_in_cents: number | null;
    new_compare_at_amount_in_cents: number | null;
    reason: string;
    actor: {
        id: number;
        name: string;
        email: string;
    } | null;
    changed_at: string;
};

export type AdminComponentSpecification = {
    id: number;
    key: string;
    label: string;
    description: string | null;
    data_type: AdminComponentSpecificationDataType;
    unit: string | null;
    required: boolean;
    options: AdminComponentSpecificationOption[];
};

export type AdminComponentCategoryOption = {
    value: number;
    label: string;
    slug: string;
    specifications: AdminComponentSpecification[];
};

export type AdminComponentCreatePageProps = {
    options: {
        brands: AdminIdOption[];
        categories: AdminComponentCategoryOption[];
        warehouses: AdminIdOption[];
        price_lists: {
            value: number;
            label: string;
            currency: string;
            is_default: boolean;
        }[];
        statuses: {
            value: 'draft' | 'active';
            label: string;
        }[];
    };
    formTokens: {
        creation: string;
    };

    routes: {
        store: string;
        inventoryIndex: string;
    };
};

export type AdminInventoryFilters = {
    search: string;
    warehouse_id: string;
    state: string;
    sort: string;
    per_page: string;
};

export type AdminInventoryPaginator = {
    data: AdminInventoryListItem[];
    meta: AdminPaginationData;
};

export type AdminInventoryIndexPageProps = {
    inventoryItems: AdminInventoryPaginator;
    filters: AdminInventoryFilters;
    options: {
        warehouses: AdminFilterOption[];
        states: AdminFilterOption[];
        sorts: AdminFilterOption[];
        perPage: AdminFilterOption[];
    };

    createComponentUrl: string | null;
};

export type AdminIdOption = {
    value: number;
    label: string;
};

export type AdminComponentSpecificationDataType =
    | 'text'
    | 'integer'
    | 'decimal'
    | 'boolean'
    | 'option'
    | 'multi_option'
    | 'multi-option';

export type AdminComponentSpecificationOption = {
    value: string;
    label: string;
};
