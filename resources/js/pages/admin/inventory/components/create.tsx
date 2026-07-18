import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Boxes,
    Check,
    ImagePlus,
    LoaderCircle,
    PackagePlus,
    Trash2,
    UploadCloud,
} from 'lucide-react';
import type { ChangeEvent, DragEvent, FormEvent, ReactNode } from 'react';
import { useEffect, useMemo, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin-layout';
import type {
    AdminComponentCategoryOption,
    AdminComponentCreatePageProps,
    AdminComponentSpecification,
} from '@/types/admin';

type SpecificationFormValue = string | boolean | string[];

type ComponentFormData = {
    name: string;
    variant_name: string;
    sku: string;
    manufacturer_part_number: string;
    barcode: string;

    brand_id: string;
    category_id: string;

    description: string;
    status: 'draft' | 'active';

    image: File | null;
    image_alt: string;

    price_list_id: string;
    amount: string;
    compare_at_amount: string;

    warehouse_id: string;
    opening_quantity: string;
    safety_stock: string;
    reorder_point: string;
    bin_location: string;

    specifications: Record<string, SpecificationFormValue>;

    creation_token: string;
};

type ComponentFormErrorKey =
    keyof ComponentFormData | `specifications.${string}`;

export default function ComponentCreatePage({
    options,
    formTokens,
    routes,
}: AdminComponentCreatePageProps) {
    const defaultPriceList = useMemo(
        () =>
            options.price_lists.find((priceList) => priceList.is_default) ??
            options.price_lists[0],
        [options.price_lists],
    );

    const defaultWarehouse = options.warehouses[0];

    const [imagePreview, setImagePreview] = useState<string | null>(null);

    const [isDraggingImage, setIsDraggingImage] = useState(false);

    const form = useForm<ComponentFormData>({
        name: '',
        variant_name: '',
        sku: '',
        manufacturer_part_number: '',
        barcode: '',

        brand_id: '',
        category_id: '',

        description: '',
        status: 'draft',

        image: null,
        image_alt: '',

        price_list_id: defaultPriceList?.value.toString() ?? '',

        amount: '',
        compare_at_amount: '',

        warehouse_id: defaultWarehouse?.value.toString() ?? '',

        opening_quantity: '0',
        safety_stock: '0',
        reorder_point: '',
        bin_location: '',

        specifications: {},

        creation_token: formTokens.creation,
    });

    const selectedCategory = useMemo(
        () =>
            options.categories.find(
                (category) =>
                    category.value.toString() === form.data.category_id,
            ) ?? null,
        [form.data.category_id, options.categories],
    );

    const selectedPriceList = useMemo(
        () =>
            options.price_lists.find(
                (priceList) =>
                    priceList.value.toString() === form.data.price_list_id,
            ) ?? null,
        [form.data.price_list_id, options.price_lists],
    );

    const selectedBrand = useMemo(
        () =>
            options.brands.find(
                (brand) => brand.value.toString() === form.data.brand_id,
            ) ?? null,
        [form.data.brand_id, options.brands],
    );

    const selectedWarehouse = useMemo(
        () =>
            options.warehouses.find(
                (warehouse) =>
                    warehouse.value.toString() === form.data.warehouse_id,
            ) ?? null,
        [form.data.warehouse_id, options.warehouses],
    );

    useEffect(() => {
        return () => {
            if (imagePreview !== null) {
                URL.revokeObjectURL(imagePreview);
            }
        };
    }, [imagePreview]);

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();

        form.post(routes.store, {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    function selectCategory(categoryId: string): void {
        const category =
            options.categories.find(
                (option) => option.value.toString() === categoryId,
            ) ?? null;

        form.setData('category_id', categoryId);

        form.setData(
            'specifications',
            createInitialSpecificationValues(category),
        );

        clearSpecificationErrors();
    }

    function clearSpecificationErrors(): void {
        const specificationErrorKeys = Object.keys(form.errors).filter(
            (key) =>
                key === 'specifications' || key.startsWith('specifications.'),
        ) as ComponentFormErrorKey[];

        if (specificationErrorKeys.length > 0) {
            form.clearErrors(...specificationErrorKeys);
        }
    }

    function selectImage(file: File | null): void {
        if (imagePreview !== null) {
            URL.revokeObjectURL(imagePreview);
        }

        form.setData('image', file);
        form.clearErrors('image');

        if (file === null) {
            setImagePreview(null);

            return;
        }

        setImagePreview(URL.createObjectURL(file));

        if (form.data.image_alt.trim() === '') {
            form.setData('image_alt', form.data.name.trim());
        }
    }

    function handleImageInput(event: ChangeEvent<HTMLInputElement>): void {
        selectImage(event.target.files?.[0] ?? null);
    }

    function handleImageDrop(event: DragEvent<HTMLDivElement>): void {
        event.preventDefault();

        setIsDraggingImage(false);

        const file = event.dataTransfer.files?.[0] ?? null;

        if (file !== null) {
            selectImage(file);
        }
    }

    function setSpecificationValue(
        key: string,
        value: SpecificationFormValue,
    ): void {
        form.setData('specifications', {
            ...form.data.specifications,
            [key]: value,
        });

        const errorKey = `specifications.${key}` as const;

        form.clearErrors(errorKey);
    }

    function specificationError(key: string): string | undefined {
        const errors = form.errors as Record<string, string | undefined>;

        return errors[`specifications.${key}`];
    }

    return (
        <AdminLayout
            title="Add component"
            description="Create a catalogue component, assign its specifications and price, and initialise inventory in a warehouse."
            actions={
                <Button variant="outline" asChild>
                    <Link href={routes.inventoryIndex}>
                        <ArrowLeft className="size-4" />
                        Back to inventory
                    </Link>
                </Button>
            }
        >
            <Head title="Add component" />

            <form
                onSubmit={submit}
                className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]"
            >
                <div className="space-y-6">
                    <GeneralInformationCard
                        form={form}
                        categories={options.categories}
                        brands={options.brands}
                        statuses={options.statuses}
                        selectCategory={selectCategory}
                    />

                    <ImageCard
                        file={form.data.image}
                        imagePreview={imagePreview}
                        imageAlt={form.data.image_alt}
                        imageError={form.errors.image}
                        imageAltError={form.errors.image_alt}
                        isDragging={isDraggingImage}
                        onImageAltChange={(value) =>
                            form.setData('image_alt', value)
                        }
                        onImageChange={handleImageInput}
                        onImageDrop={handleImageDrop}
                        onDragStateChange={setIsDraggingImage}
                        onRemoveImage={() => selectImage(null)}
                    />

                    <PricingCard
                        priceLists={options.price_lists}
                        priceListId={form.data.price_list_id}
                        amount={form.data.amount}
                        compareAtAmount={form.data.compare_at_amount}
                        amountError={form.errors.amount}
                        compareAtAmountError={form.errors.compare_at_amount}
                        priceListError={form.errors.price_list_id}
                        onPriceListChange={(value) =>
                            form.setData('price_list_id', value)
                        }
                        onAmountChange={(value) =>
                            form.setData('amount', value)
                        }
                        onCompareAtAmountChange={(value) =>
                            form.setData('compare_at_amount', value)
                        }
                    />

                    <InventoryCard
                        warehouses={options.warehouses}
                        warehouseId={form.data.warehouse_id}
                        openingQuantity={form.data.opening_quantity}
                        safetyStock={form.data.safety_stock}
                        reorderPoint={form.data.reorder_point}
                        binLocation={form.data.bin_location}
                        errors={{
                            warehouse_id: form.errors.warehouse_id,
                            opening_quantity: form.errors.opening_quantity,
                            safety_stock: form.errors.safety_stock,
                            reorder_point: form.errors.reorder_point,
                            bin_location: form.errors.bin_location,
                        }}
                        onWarehouseChange={(value) =>
                            form.setData('warehouse_id', value)
                        }
                        onOpeningQuantityChange={(value) =>
                            form.setData('opening_quantity', value)
                        }
                        onSafetyStockChange={(value) =>
                            form.setData('safety_stock', value)
                        }
                        onReorderPointChange={(value) =>
                            form.setData('reorder_point', value)
                        }
                        onBinLocationChange={(value) =>
                            form.setData('bin_location', value)
                        }
                    />

                    <SpecificationsCard
                        category={selectedCategory}
                        values={form.data.specifications}
                        generalError={form.errors.specifications}
                        errorFor={specificationError}
                        setValue={setSpecificationValue}
                    />
                </div>

                <aside className="space-y-6">
                    <ComponentSummaryCard
                        name={form.data.name}
                        sku={form.data.sku}
                        status={form.data.status}
                        brand={selectedBrand?.label ?? null}
                        category={selectedCategory?.label ?? null}
                        warehouse={selectedWarehouse?.label ?? null}
                        currency={selectedPriceList?.currency ?? null}
                        amount={form.data.amount}
                        imagePreview={imagePreview}
                    />

                    <Card className="xl:sticky xl:top-24">
                        <CardContent className="space-y-4 pt-6">
                            {form.progress && (
                                <div>
                                    <div className="mb-2 flex items-center justify-between text-xs text-muted-foreground">
                                        <span>Uploading</span>

                                        <span>{form.progress.percentage}%</span>
                                    </div>

                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full bg-foreground transition-[width]"
                                            style={{
                                                width: `${form.progress.percentage}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            )}

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={form.processing}
                            >
                                {form.processing ? (
                                    <>
                                        <LoaderCircle className="size-4 animate-spin" />
                                        Creating component…
                                    </>
                                ) : (
                                    <>
                                        <PackagePlus className="size-4" />
                                        Create component
                                    </>
                                )}
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                className="w-full"
                                asChild
                            >
                                <Link href={routes.inventoryIndex}>Cancel</Link>
                            </Button>

                            <p className="text-xs leading-5 text-muted-foreground">
                                Opening stock will be recorded as an inventory
                                receipt so that the initial balance remains
                                auditable.
                            </p>
                        </CardContent>
                    </Card>
                </aside>
            </form>
        </AdminLayout>
    );
}

type GeneralInformationCardProps = {
    form: ReturnType<typeof useForm<ComponentFormData>>;

    brands: {
        value: number;
        label: string;
    }[];

    categories: AdminComponentCategoryOption[];

    statuses: {
        value: 'draft' | 'active';
        label: string;
    }[];

    selectCategory: (categoryId: string) => void;
};

function GeneralInformationCard({
    form,
    brands,
    categories,
    statuses,
    selectCategory,
}: GeneralInformationCardProps) {
    return (
        <SectionCard
            title="General information"
            description="The shared catalogue and SKU information for this component."
            icon={<Boxes className="size-5" />}
        >
            <div className="grid gap-5 md:grid-cols-2">
                <Field
                    label="Product name"
                    htmlFor="name"
                    required
                    error={form.errors.name}
                >
                    <Input
                        id="name"
                        value={form.data.name}
                        maxLength={180}
                        placeholder="AMD Ryzen 7 7800X3D"
                        onChange={(event) => {
                            const value = event.target.value;

                            form.setData('name', value);

                            if (form.data.image_alt.trim() === '') {
                                form.setData('image_alt', value);
                            }
                        }}
                    />
                </Field>

                <Field
                    label="Variant name"
                    htmlFor="variant_name"
                    description="Optional when the SKU uses the same name as the product."
                    error={form.errors.variant_name}
                >
                    <Input
                        id="variant_name"
                        value={form.data.variant_name}
                        maxLength={180}
                        placeholder="Boxed retail"
                        onChange={(event) =>
                            form.setData('variant_name', event.target.value)
                        }
                    />
                </Field>

                <Field
                    label="SKU"
                    htmlFor="sku"
                    required
                    error={form.errors.sku}
                >
                    <Input
                        id="sku"
                        value={form.data.sku}
                        maxLength={80}
                        placeholder="CPU-AMD-7800X3D"
                        autoCapitalize="characters"
                        onChange={(event) =>
                            form.setData(
                                'sku',
                                event.target.value
                                    .toUpperCase()
                                    .replace(/\s+/g, '-'),
                            )
                        }
                    />
                </Field>

                <Field
                    label="Manufacturer part number"
                    htmlFor="manufacturer_part_number"
                    error={form.errors.manufacturer_part_number}
                >
                    <Input
                        id="manufacturer_part_number"
                        value={form.data.manufacturer_part_number}
                        maxLength={120}
                        placeholder="100-100000910WOF"
                        onChange={(event) =>
                            form.setData(
                                'manufacturer_part_number',
                                event.target.value,
                            )
                        }
                    />
                </Field>

                <Field
                    label="Barcode / EAN"
                    htmlFor="barcode"
                    error={form.errors.barcode}
                >
                    <Input
                        id="barcode"
                        value={form.data.barcode}
                        maxLength={32}
                        inputMode="numeric"
                        placeholder="0730143314930"
                        onChange={(event) =>
                            form.setData('barcode', event.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Brand"
                    htmlFor="brand_id"
                    required
                    error={form.errors.brand_id}
                >
                    <Select
                        value={form.data.brand_id}
                        onValueChange={(value) =>
                            form.setData('brand_id', value)
                        }
                    >
                        <SelectTrigger id="brand_id">
                            <SelectValue placeholder="Select a brand" />
                        </SelectTrigger>

                        <SelectContent>
                            {brands.map((brand) => (
                                <SelectItem
                                    key={brand.value}
                                    value={brand.value.toString()}
                                >
                                    {brand.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>

                <Field
                    label="Component type"
                    htmlFor="category_id"
                    required
                    description="The component type determines which technical specifications appear below."
                    error={form.errors.category_id}
                >
                    <Select
                        value={form.data.category_id}
                        onValueChange={selectCategory}
                    >
                        <SelectTrigger id="category_id">
                            <SelectValue placeholder="Select a component type" />
                        </SelectTrigger>

                        <SelectContent>
                            {categories.map((category) => (
                                <SelectItem
                                    key={category.value}
                                    value={category.value.toString()}
                                >
                                    {category.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>

                <Field
                    label="Status"
                    htmlFor="status"
                    required
                    description="Draft components stay hidden from the storefront and configurator."
                    error={form.errors.status}
                >
                    <Select
                        value={form.data.status}
                        onValueChange={(value: 'draft' | 'active') =>
                            form.setData('status', value)
                        }
                    >
                        <SelectTrigger id="status">
                            <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                            {statuses.map((status) => (
                                <SelectItem
                                    key={status.value}
                                    value={status.value}
                                >
                                    {status.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>

                <div className="md:col-span-2">
                    <Field
                        label="Description"
                        htmlFor="description"
                        error={form.errors.description}
                    >
                        <Textarea
                            id="description"
                            value={form.data.description}
                            maxLength={5000}
                            rows={5}
                            placeholder="Describe the component, its intended use and its important characteristics."
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                    </Field>
                </div>
            </div>
        </SectionCard>
    );
}

type ImageCardProps = {
    file: File | null;
    imagePreview: string | null;
    imageAlt: string;
    imageError?: string;
    imageAltError?: string;
    isDragging: boolean;

    onImageAltChange: (value: string) => void;

    onImageChange: (event: ChangeEvent<HTMLInputElement>) => void;

    onImageDrop: (event: DragEvent<HTMLDivElement>) => void;

    onDragStateChange: (dragging: boolean) => void;

    onRemoveImage: () => void;
};

function ImageCard({
    file,
    imagePreview,
    imageAlt,
    imageError,
    imageAltError,
    isDragging,
    onImageAltChange,
    onImageChange,
    onImageDrop,
    onDragStateChange,
    onRemoveImage,
}: ImageCardProps) {
    return (
        <SectionCard
            title="Product image"
            description="Upload the primary image used throughout the admin area, storefront and configurator."
            icon={<ImagePlus className="size-5" />}
        >
            <div className="grid gap-6 md:grid-cols-[16rem_minmax(0,1fr)]">
                <div>
                    {imagePreview ? (
                        <div className="relative overflow-hidden rounded-xl border bg-muted/20">
                            <img
                                src={imagePreview}
                                alt={imageAlt || 'Component preview'}
                                className="aspect-square w-full object-contain p-4"
                            />

                            <Button
                                type="button"
                                size="icon"
                                variant="destructive"
                                className="absolute top-3 right-3"
                                aria-label="Remove image"
                                onClick={onRemoveImage}
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ) : (
                        <div className="grid aspect-square place-items-center rounded-xl border border-dashed bg-muted/10">
                            <ImagePlus className="size-10 text-muted-foreground" />
                        </div>
                    )}
                </div>

                <div className="space-y-5">
                    <div>
                        <div
                            className={[
                                'rounded-xl border-2 border-dashed p-8 text-center transition',
                                isDragging
                                    ? 'border-foreground bg-muted'
                                    : 'border-border bg-muted/10',
                            ].join(' ')}
                            onDragEnter={(event) => {
                                event.preventDefault();
                                onDragStateChange(true);
                            }}
                            onDragOver={(event) => {
                                event.preventDefault();
                                onDragStateChange(true);
                            }}
                            onDragLeave={() => onDragStateChange(false)}
                            onDrop={onImageDrop}
                        >
                            <UploadCloud className="mx-auto size-8 text-muted-foreground" />

                            <p className="mt-3 text-sm font-medium">
                                Drop an image here
                            </p>

                            <p className="mt-1 text-xs text-muted-foreground">
                                JPEG, PNG or WebP, maximum 5 MB
                            </p>

                            <Button
                                type="button"
                                variant="outline"
                                className="mt-5"
                                asChild
                            >
                                <Label
                                    htmlFor="image"
                                    className="cursor-pointer"
                                >
                                    Choose image
                                </Label>
                            </Button>

                            <Input
                                id="image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                className="sr-only"
                                onChange={onImageChange}
                            />
                        </div>

                        <FieldError message={imageError} />

                        {file && (
                            <p className="mt-2 truncate text-xs text-muted-foreground">
                                {file.name} · {formatFileSize(file.size)}
                            </p>
                        )}
                    </div>

                    <Field
                        label="Image alt text"
                        htmlFor="image_alt"
                        description="Describe the image for customers using assistive technology."
                        error={imageAltError}
                    >
                        <Input
                            id="image_alt"
                            value={imageAlt}
                            maxLength={255}
                            placeholder="AMD Ryzen 7 7800X3D processor"
                            onChange={(event) =>
                                onImageAltChange(event.target.value)
                            }
                        />
                    </Field>
                </div>
            </div>
        </SectionCard>
    );
}

type PricingCardProps = {
    priceLists: {
        value: number;
        label: string;
        currency: string;
        is_default: boolean;
    }[];

    priceListId: string;
    amount: string;
    compareAtAmount: string;

    priceListError?: string;
    amountError?: string;
    compareAtAmountError?: string;

    onPriceListChange: (value: string) => void;

    onAmountChange: (value: string) => void;

    onCompareAtAmountChange: (value: string) => void;
};

function PricingCard({
    priceLists,
    priceListId,
    amount,
    compareAtAmount,
    priceListError,
    amountError,
    compareAtAmountError,
    onPriceListChange,
    onAmountChange,
    onCompareAtAmountChange,
}: PricingCardProps) {
    const priceList =
        priceLists.find((option) => option.value.toString() === priceListId) ??
        null;

    return (
        <SectionCard
            title="Pricing"
            description="Set the initial selling price for the selected price list."
            icon={<PackagePlus className="size-5" />}
        >
            <div className="grid gap-5 md:grid-cols-3">
                <Field
                    label="Price list"
                    htmlFor="price_list_id"
                    required
                    error={priceListError}
                >
                    <Select
                        value={priceListId}
                        onValueChange={onPriceListChange}
                    >
                        <SelectTrigger id="price_list_id">
                            <SelectValue placeholder="Select a price list" />
                        </SelectTrigger>

                        <SelectContent>
                            {priceLists.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value.toString()}
                                >
                                    {option.label} ({option.currency})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>

                <Field
                    label={`Selling price${
                        priceList ? ` (${priceList.currency})` : ''
                    }`}
                    htmlFor="amount"
                    required
                    error={amountError}
                >
                    <Input
                        id="amount"
                        value={amount}
                        inputMode="decimal"
                        placeholder="349.99"
                        onChange={(event) => onAmountChange(event.target.value)}
                    />
                </Field>

                <Field
                    label={`Compare-at price${
                        priceList ? ` (${priceList.currency})` : ''
                    }`}
                    htmlFor="compare_at_amount"
                    description="Optional. Must be higher than the selling price."
                    error={compareAtAmountError}
                >
                    <Input
                        id="compare_at_amount"
                        value={compareAtAmount}
                        inputMode="decimal"
                        placeholder="399.99"
                        onChange={(event) =>
                            onCompareAtAmountChange(event.target.value)
                        }
                    />
                </Field>
            </div>
        </SectionCard>
    );
}

type InventoryCardProps = {
    warehouses: {
        value: number;
        label: string;
    }[];

    warehouseId: string;
    openingQuantity: string;
    safetyStock: string;
    reorderPoint: string;
    binLocation: string;

    errors: {
        warehouse_id?: string;
        opening_quantity?: string;
        safety_stock?: string;
        reorder_point?: string;
        bin_location?: string;
    };

    onWarehouseChange: (value: string) => void;

    onOpeningQuantityChange: (value: string) => void;

    onSafetyStockChange: (value: string) => void;

    onReorderPointChange: (value: string) => void;

    onBinLocationChange: (value: string) => void;
};

function InventoryCard({
    warehouses,
    warehouseId,
    openingQuantity,
    safetyStock,
    reorderPoint,
    binLocation,
    errors,
    onWarehouseChange,
    onOpeningQuantityChange,
    onSafetyStockChange,
    onReorderPointChange,
    onBinLocationChange,
}: InventoryCardProps) {
    return (
        <SectionCard
            title="Initial inventory"
            description="Create the first inventory item and optionally receive its opening stock."
            icon={<Boxes className="size-5" />}
        >
            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-5">
                <Field
                    label="Warehouse"
                    htmlFor="warehouse_id"
                    required
                    error={errors.warehouse_id}
                >
                    <Select
                        value={warehouseId}
                        onValueChange={onWarehouseChange}
                    >
                        <SelectTrigger id="warehouse_id">
                            <SelectValue placeholder="Select a warehouse" />
                        </SelectTrigger>

                        <SelectContent>
                            {warehouses.map((warehouse) => (
                                <SelectItem
                                    key={warehouse.value}
                                    value={warehouse.value.toString()}
                                >
                                    {warehouse.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </Field>

                <Field
                    label="Opening quantity"
                    htmlFor="opening_quantity"
                    required
                    description="Recorded as a receipt movement."
                    error={errors.opening_quantity}
                >
                    <Input
                        id="opening_quantity"
                        type="number"
                        min={0}
                        step={1}
                        value={openingQuantity}
                        onChange={(event) =>
                            onOpeningQuantityChange(event.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Safety stock"
                    htmlFor="safety_stock"
                    required
                    error={errors.safety_stock}
                >
                    <Input
                        id="safety_stock"
                        type="number"
                        min={0}
                        step={1}
                        value={safetyStock}
                        onChange={(event) =>
                            onSafetyStockChange(event.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Reorder point"
                    htmlFor="reorder_point"
                    description="Optional."
                    error={errors.reorder_point}
                >
                    <Input
                        id="reorder_point"
                        type="number"
                        min={0}
                        step={1}
                        value={reorderPoint}
                        onChange={(event) =>
                            onReorderPointChange(event.target.value)
                        }
                    />
                </Field>

                <Field
                    label="Bin location"
                    htmlFor="bin_location"
                    error={errors.bin_location}
                >
                    <Input
                        id="bin_location"
                        value={binLocation}
                        maxLength={64}
                        placeholder="A-01-03"
                        onChange={(event) =>
                            onBinLocationChange(event.target.value)
                        }
                    />
                </Field>
            </div>
        </SectionCard>
    );
}

type SpecificationsCardProps = {
    category: AdminComponentCategoryOption | null;

    values: Record<string, SpecificationFormValue>;

    generalError?: string;

    errorFor: (key: string) => string | undefined;

    setValue: (key: string, value: SpecificationFormValue) => void;
};

function SpecificationsCard({
    category,
    values,
    generalError,
    errorFor,
    setValue,
}: SpecificationsCardProps) {
    return (
        <SectionCard
            title="Technical specifications"
            description="These values drive storefront information and compatibility checks."
            icon={<Check className="size-5" />}
        >
            {category === null ? (
                <div className="rounded-xl border border-dashed bg-muted/10 p-8 text-center">
                    <p className="font-medium">Select a component type</p>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Category-specific fields will appear here.
                    </p>
                </div>
            ) : category.specifications.length === 0 ? (
                <div className="rounded-xl border border-dashed bg-muted/10 p-8 text-center">
                    <p className="font-medium">No specifications configured</p>

                    <p className="mt-2 text-sm text-muted-foreground">
                        This category has no specification definitions.
                    </p>
                </div>
            ) : (
                <div className="grid gap-5 md:grid-cols-2">
                    {category.specifications.map((specification) => (
                        <SpecificationField
                            key={specification.id}
                            specification={specification}
                            value={values[specification.key]}
                            error={errorFor(specification.key)}
                            setValue={(value) =>
                                setValue(specification.key, value)
                            }
                        />
                    ))}
                </div>
            )}

            <FieldError message={generalError} />
        </SectionCard>
    );
}

type SpecificationFieldProps = {
    specification: AdminComponentSpecification;
    value: SpecificationFormValue | undefined;
    error?: string;

    setValue: (value: SpecificationFormValue) => void;
};

function SpecificationField({
    specification,
    value,
    error,
    setValue,
}: SpecificationFieldProps) {
    if (specification.data_type === 'boolean') {
        return (
            <div className="rounded-xl border p-4">
                <div className="flex items-start gap-3">
                    <Checkbox
                        id={`specification-${specification.id}`}
                        checked={value === true}
                        onCheckedChange={(checked) =>
                            setValue(checked === true)
                        }
                    />

                    <div>
                        <Label
                            htmlFor={`specification-${specification.id}`}
                            className="cursor-pointer"
                        >
                            {specification.label}

                            {specification.required && ' *'}
                        </Label>

                        {specification.description && (
                            <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                {specification.description}
                            </p>
                        )}
                    </div>
                </div>

                <FieldError message={error} />
            </div>
        );
    }

    if (
        specification.data_type === 'multi_option' ||
        specification.data_type === 'multi-option'
    ) {
        const selectedValues = Array.isArray(value) ? value : [];

        return (
            <div className="md:col-span-2">
                <div className="rounded-xl border p-4">
                    <Label>
                        {specification.label}

                        {specification.required && ' *'}
                    </Label>

                    {specification.description && (
                        <p className="mt-1 text-xs leading-5 text-muted-foreground">
                            {specification.description}
                        </p>
                    )}

                    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {specification.options.map((option) => {
                            const optionValue = option.value.toString();

                            const checked =
                                selectedValues.includes(optionValue);

                            return (
                                <label
                                    key={option.value}
                                    className="flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm"
                                >
                                    <Checkbox
                                        checked={checked}
                                        onCheckedChange={(nextChecked) => {
                                            setValue(
                                                nextChecked === true
                                                    ? [
                                                          ...selectedValues,
                                                          optionValue,
                                                      ]
                                                    : selectedValues.filter(
                                                          (current) =>
                                                              current !==
                                                              optionValue,
                                                      ),
                                            );
                                        }}
                                    />

                                    {option.label}
                                </label>
                            );
                        })}
                    </div>

                    <FieldError message={error} />
                </div>
            </div>
        );
    }

    if (specification.data_type === 'option') {
        return (
            <Field
                label={specification.label}
                htmlFor={`specification-${specification.id}`}
                required={specification.required}
                description={specification.description ?? undefined}
                error={error}
            >
                <Select
                    value={typeof value === 'string' ? value : ''}
                    onValueChange={setValue}
                >
                    <SelectTrigger id={`specification-${specification.id}`}>
                        <SelectValue
                            placeholder={`Select ${specification.label.toLowerCase()}`}
                        />
                    </SelectTrigger>

                    <SelectContent>
                        {specification.options.map((option) => (
                            <SelectItem
                                key={option.value}
                                value={option.value.toString()}
                            >
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </Field>
        );
    }

    const inputType =
        specification.data_type === 'integer' ||
        specification.data_type === 'decimal'
            ? 'number'
            : 'text';

    return (
        <Field
            label={
                specification.unit
                    ? `${specification.label} (${specification.unit})`
                    : specification.label
            }
            htmlFor={`specification-${specification.id}`}
            required={specification.required}
            description={specification.description ?? undefined}
            error={error}
        >
            <Input
                id={`specification-${specification.id}`}
                type={inputType}
                step={specification.data_type === 'decimal' ? 'any' : undefined}
                value={typeof value === 'string' ? value : ''}
                onChange={(event) => setValue(event.target.value)}
            />
        </Field>
    );
}

type ComponentSummaryCardProps = {
    name: string;
    sku: string;
    status: 'draft' | 'active';
    brand: string | null;
    category: string | null;
    warehouse: string | null;
    currency: string | null;
    amount: string;
    imagePreview: string | null;
};

function ComponentSummaryCard({
    name,
    sku,
    status,
    brand,
    category,
    warehouse,
    currency,
    amount,
    imagePreview,
}: ComponentSummaryCardProps) {
    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between gap-3">
                    <CardTitle>Preview</CardTitle>

                    <Badge
                        variant={status === 'active' ? 'default' : 'secondary'}
                    >
                        {status === 'active' ? 'Active' : 'Draft'}
                    </Badge>
                </div>
            </CardHeader>

            <CardContent>
                <div className="overflow-hidden rounded-xl border bg-muted/20">
                    {imagePreview ? (
                        <img
                            src={imagePreview}
                            alt={name || 'Component preview'}
                            className="aspect-square w-full object-contain p-5"
                        />
                    ) : (
                        <div className="grid aspect-square place-items-center">
                            <ImagePlus className="size-10 text-muted-foreground" />
                        </div>
                    )}
                </div>

                <div className="mt-5">
                    <p className="text-lg font-semibold">
                        {name || 'Unnamed component'}
                    </p>

                    <p className="mt-1 font-mono text-xs text-muted-foreground">
                        {sku || 'No SKU'}
                    </p>
                </div>

                <dl className="mt-5 space-y-3 border-t pt-5 text-sm">
                    <SummaryRow label="Brand" value={brand ?? '—'} />

                    <SummaryRow label="Type" value={category ?? '—'} />

                    <SummaryRow label="Warehouse" value={warehouse ?? '—'} />

                    <SummaryRow
                        label="Price"
                        value={
                            amount ? `${currency ?? ''} ${amount}`.trim() : '—'
                        }
                    />
                </dl>
            </CardContent>
        </Card>
    );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start justify-between gap-4">
            <dt className="text-muted-foreground">{label}</dt>

            <dd className="text-right font-medium">{value}</dd>
        </div>
    );
}

function SectionCard({
    title,
    description,
    icon,
    children,
}: {
    title: string;
    description: string;
    icon: ReactNode;
    children: ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <div className="flex items-start gap-3">
                    <span className="grid size-10 shrink-0 place-items-center rounded-xl border bg-muted/20">
                        {icon}
                    </span>

                    <div>
                        <CardTitle>{title}</CardTitle>

                        <CardDescription className="mt-1">
                            {description}
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>

            <CardContent>{children}</CardContent>
        </Card>
    );
}

function Field({
    label,
    htmlFor,
    description,
    required = false,
    error,
    children,
}: {
    label: string;
    htmlFor: string;
    description?: string;
    required?: boolean;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div>
            <Label htmlFor={htmlFor}>
                {label}
                {required && ' *'}
            </Label>

            {description && (
                <p className="mt-1 mb-2 text-xs leading-5 text-muted-foreground">
                    {description}
                </p>
            )}

            {!description && <div className="h-2" />}

            {children}

            <FieldError message={error} />
        </div>
    );
}

function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-2 text-sm text-destructive">{message}</p>;
}

function createInitialSpecificationValues(
    category: AdminComponentCategoryOption | null,
): Record<string, SpecificationFormValue> {
    if (category === null) {
        return {};
    }

    return Object.fromEntries(
        category.specifications.map((specification) => {
            if (specification.data_type === 'boolean') {
                return [specification.key, false];
            }

            if (
                specification.data_type === 'multi_option' ||
                specification.data_type === 'multi-option'
            ) {
                return [specification.key, []];
            }

            return [specification.key, ''];
        }),
    );
}

function formatFileSize(sizeInBytes: number): string {
    if (sizeInBytes < 1024) {
        return `${sizeInBytes} B`;
    }

    if (sizeInBytes < 1024 * 1024) {
        return `${(sizeInBytes / 1024).toFixed(1)} KB`;
    }

    return `${(sizeInBytes / (1024 * 1024)).toFixed(1)} MB`;
}
