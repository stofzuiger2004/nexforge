export type SystemAvailabilityStatus =
    | 'available'
    | 'limited'
    | 'unavailable'
    | 'made_to_order';

export type SystemComponentSummary = {
    name: string;
    brand: string | null;
    quantity: number;
};

export type SystemPrice = {
    amount_in_cents: number;
    compare_at_amount_in_cents: number | null;
    currency: string;
};

export type SystemImage = {
    url: string;
    alt: string;
};

export type FeaturedSystem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_configurable: boolean;

    image: SystemImage | null;
    price: SystemPrice | null;

    availability: {
        status: SystemAvailabilityStatus;
        label: string;
        available_builds: number | null;
    };

    components: {
        processor: SystemComponentSummary | null;
        graphics_card: SystemComponentSummary | null;
        memory: SystemComponentSummary | null;
        storage: SystemComponentSummary | null;
    };
};

export type HomePageProps = {
    featuredSystems: FeaturedSystem[];
};

export type StorefrontAuthUser = {
    id: number;
    name: string;
    email: string;
};

export type StorefrontSharedProps = {
    auth?: {
        user: StorefrontAuthUser | null;
    };

    [key: string]: unknown;
};
export type SystemDetailImage = {
    id: number;
    url: string;
    alt: string;
    is_primary: boolean;
};

export type SystemSpecification = {
    key: string;
    label: string;
    value: string;
};

export type SystemDetailComponent = {
    id: number;

    slot: string;
    slot_label: string;

    quantity: number;

    is_required: boolean;
    is_replaceable: boolean;

    name: string;
    brand: string | null;
    sku: string;

    product_slug: string;
    category: string;

    specifications: SystemSpecification[];
};

export type SystemDetail = {
    id: number;
    sku: string;
    name: string;
    slug: string;

    short_description: string | null;
    description: string | null;

    is_configurable: boolean;

    images: SystemDetailImage[];
    price: SystemPrice | null;

    availability: {
        status: SystemAvailabilityStatus;
        label: string;
        available_builds: number | null;
    };

    components: SystemDetailComponent[];
};

export type SystemDetailPageProps = {
    system: SystemDetail;
};