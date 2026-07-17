import type { ReactNode } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CheckoutAddressForm, CheckoutCountry } from '@/types/checkout';

type AddressFieldsProps = {
    idPrefix: string;

    value: CheckoutAddressForm;

    countries: CheckoutCountry[];

    errors: Record<string, string>;

    fieldPrefix: 'shipping' | 'billing';

    disabled?: boolean;

    onChange: (value: CheckoutAddressForm) => void;
};

export function AddressFields({
    idPrefix,
    value,
    countries,
    errors,
    fieldPrefix,
    disabled = false,
    onChange,
}: AddressFieldsProps) {
    function update(field: keyof CheckoutAddressForm, fieldValue: string) {
        onChange({
            ...value,
            [field]: fieldValue,
        });
    }

    return (
        <div className="grid gap-5 sm:grid-cols-2">
            <Field
                id={`${idPrefix}-first-name`}
                label="First name"
                error={errors[`${fieldPrefix}.first_name`]}
            >
                <Input
                    id={`${idPrefix}-first-name`}
                    value={value.first_name}
                    disabled={disabled}
                    autoComplete="given-name"
                    onChange={(event) =>
                        update('first_name', event.target.value)
                    }
                />
            </Field>

            <Field
                id={`${idPrefix}-last-name`}
                label="Last name"
                error={errors[`${fieldPrefix}.last_name`]}
            >
                <Input
                    id={`${idPrefix}-last-name`}
                    value={value.last_name}
                    disabled={disabled}
                    autoComplete="family-name"
                    onChange={(event) =>
                        update('last_name', event.target.value)
                    }
                />
            </Field>

            <Field
                id={`${idPrefix}-company`}
                label="Company"
                optional
                error={errors[`${fieldPrefix}.company`]}
            >
                <Input
                    id={`${idPrefix}-company`}
                    value={value.company}
                    disabled={disabled}
                    autoComplete="organization"
                    onChange={(event) => update('company', event.target.value)}
                />
            </Field>

            <Field
                id={`${idPrefix}-vat`}
                label="VAT number"
                optional
                error={errors[`${fieldPrefix}.vat_number`]}
            >
                <Input
                    id={`${idPrefix}-vat`}
                    value={value.vat_number}
                    disabled={disabled}
                    onChange={(event) =>
                        update('vat_number', event.target.value)
                    }
                />
            </Field>

            <div className="sm:col-span-2">
                <Field
                    id={`${idPrefix}-address-1`}
                    label="Street and house number"
                    error={errors[`${fieldPrefix}.address_line_1`]}
                >
                    <Input
                        id={`${idPrefix}-address-1`}
                        value={value.address_line_1}
                        disabled={disabled}
                        autoComplete="address-line1"
                        onChange={(event) =>
                            update('address_line_1', event.target.value)
                        }
                    />
                </Field>
            </div>

            <div className="sm:col-span-2">
                <Field
                    id={`${idPrefix}-address-2`}
                    label="Additional address information"
                    optional
                    error={errors[`${fieldPrefix}.address_line_2`]}
                >
                    <Input
                        id={`${idPrefix}-address-2`}
                        value={value.address_line_2}
                        disabled={disabled}
                        autoComplete="address-line2"
                        onChange={(event) =>
                            update('address_line_2', event.target.value)
                        }
                    />
                </Field>
            </div>

            <Field
                id={`${idPrefix}-postal-code`}
                label="Postal code"
                error={errors[`${fieldPrefix}.postal_code`]}
            >
                <Input
                    id={`${idPrefix}-postal-code`}
                    value={value.postal_code}
                    disabled={disabled}
                    autoComplete="postal-code"
                    onChange={(event) =>
                        update('postal_code', event.target.value)
                    }
                />
            </Field>

            <Field
                id={`${idPrefix}-city`}
                label="City"
                error={errors[`${fieldPrefix}.city`]}
            >
                <Input
                    id={`${idPrefix}-city`}
                    value={value.city}
                    disabled={disabled}
                    autoComplete="address-level2"
                    onChange={(event) => update('city', event.target.value)}
                />
            </Field>

            <Field
                id={`${idPrefix}-state`}
                label="State or province"
                optional
                error={errors[`${fieldPrefix}.state`]}
            >
                <Input
                    id={`${idPrefix}-state`}
                    value={value.state}
                    disabled={disabled}
                    autoComplete="address-level1"
                    onChange={(event) => update('state', event.target.value)}
                />
            </Field>

            <Field
                id={`${idPrefix}-country`}
                label="Country"
                error={errors[`${fieldPrefix}.country_code`]}
            >
                <select
                    id={`${idPrefix}-country`}
                    value={value.country_code}
                    disabled={disabled}
                    autoComplete="country"
                    onChange={(event) =>
                        update('country_code', event.target.value)
                    }
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {countries.map((country) => (
                        <option key={country.code} value={country.code}>
                            {country.label}
                        </option>
                    ))}
                </select>
            </Field>
        </div>
    );
}

type FieldProps = {
    id: string;
    label: string;
    optional?: boolean;
    error?: string;
    children: ReactNode;
};

function Field({ id, label, optional = false, error, children }: FieldProps) {
    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between gap-3">
                <Label htmlFor={id}>{label}</Label>

                {optional && (
                    <span className="text-xs text-muted-foreground">
                        Optional
                    </span>
                )}
            </div>

            {children}

            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
