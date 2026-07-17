export type CheckoutCountry = {
    code: string;
    label: string;
};

export type CheckoutAddressForm = {
    first_name: string;
    last_name: string;

    company: string;
    vat_number: string;

    address_line_1: string;
    address_line_2: string;

    postal_code: string;
    city: string;
    state: string;

    country_code: string;
};

export type CheckoutFormData = {
    email: string;
    phone: string;

    shipping: CheckoutAddressForm;

    billing_same_as_shipping: boolean;
    billing: CheckoutAddressForm;

    terms: boolean;
};

export type ConfigurationReviewComponent = {
    id: number;

    slot: string;
    slot_label: string;

    name: string;
    brand: string | null;
    sku: string;

    quantity: number;

    unit_price_in_cents: number;
    line_total_in_cents: number;
};

export type ConfigurationReview = {
    configuration: {
        public_id: string;
        name: string;
        version: number;
        status: string;
    };

    system: {
        name: string;
        slug: string | null;

        image: {
            url: string;
            alt: string;
        } | null;
    };

    components: ConfigurationReviewComponent[];

    pricing: {
        currency: string;

        base_price_in_cents: number;
        component_change_in_cents: number;

        configuration_total_in_cents: number;
        shipping_in_cents: number;
        order_total_in_cents: number;

        prices_include_tax: boolean;
    };

    reservation: {
        public_id: string;
        status: string;

        reserved_at: string;
        expires_at: string;

        is_expired: boolean;
        warehouse: string;
    };

    validation: {
        is_current: boolean;
        label: string;
    };

    can_submit: boolean;
};

export type ConfigurationReviewPageProps = {
    review: ConfigurationReview;

    checkout_defaults: CheckoutFormData;

    countries: CheckoutCountry[];
};
