<?php

declare(strict_types=1);

namespace App\Enums;

enum ComponentSlot: string
{
    case Cpu = 'cpu';
    case Motherboard = 'motherboard';
    case GraphicsCard = 'graphics_card';
    case Memory = 'memory';
    case PrimaryStorage = 'primary_storage';
    case SecondaryStorage = 'secondary_storage';
    case PowerSupply = 'power_supply';
    case Case = 'case';
    case CpuCooler = 'cpu_cooler';
    case CaseFan = 'case_fan';
    case OperatingSystem = 'operating_system';

    public function allowsMultiple(): bool
    {
        return match ($this) {
            self::SecondaryStorage,
            self::CaseFan => true,

            default => false,
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Cpu => 10,
            self::Motherboard => 20,
            self::GraphicsCard => 30,
            self::Memory => 40,
            self::PrimaryStorage => 50,
            self::SecondaryStorage => 60,
            self::PowerSupply => 70,
            self::Case => 80,
            self::CpuCooler => 90,
            self::CaseFan => 100,
            self::OperatingSystem => 110,
        };
    }

    public function categorySlug(): string
    {
        return match ($this) {
            self::Cpu => 'processors',
            self::Motherboard => 'motherboards',
            self::GraphicsCard => 'graphics-cards',
            self::Memory => 'memory',

            self::PrimaryStorage,
            self::SecondaryStorage => 'storage',

            self::PowerSupply => 'power-supplies',
            self::Case => 'cases',
            self::CpuCooler => 'cpu-coolers',
            self::CaseFan => 'case-fans',
            self::OperatingSystem => 'operating-systems',
        };
    }
}
