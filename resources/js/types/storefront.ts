export type FeaturedSystem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;

    price_in_cents: number | null;
    currency: string | null;

    image_url: string | null;

    processor: string | null;
    graphics_card: string | null;
    memory: string | null;
    storage: string | null;
};

export type HomePageProps = {
    featuredSystems: FeaturedSystem[];
};