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

    public function label(): string
    {
        return match ($this) {
            self::Cpu => 'Processor',
            self::Motherboard => 'Motherboard',
            self::GraphicsCard => 'Graphics Card',
            self::Memory => 'Memory',
            self::PrimaryStorage => 'Primary Storage',
            self::SecondaryStorage => 'Secondary Storage',
            self::PowerSupply => 'Power supply',
            self::Case => 'Case',
            self::CpuCooler => 'CPU cooler',
            self::CaseFan => 'Case fan',
            self::OperatingSystem => 'Operating system'
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Cpu => 'Choose the processor that best matches your gaming and productivity needs.',

            self::Motherboard => 'The motherboard determines platform compatibility, connectivity, and expansion options.',

            self::GraphicsCard => 'The graphics card has the largest impact on gaming resolution, detail settings, and frame rate.',

            self::Memory => 'More memory improves multitasking and performance in demanding games and applications.',

            self::PrimaryStorage => 'Choose the primary drive for Windows, applications, and your most frequently played games.',

            self::SecondaryStorage => 'Add extra storage for a larger game library, recordings, and project files.',

            self::PowerSupply => 'The power supply must provide enough capacity for the selected processor and graphics card.',

            self::Case => 'The case determines the system appearance, airflow, and available component clearance.',

            self::CpuCooler => 'A suitable cooler keeps processor temperatures and noise under control.',

            self::CaseFan => 'Additional case fans can improve airflow and reduce component temperatures.',

            self::OperatingSystem => 'Choose whether the system should be supplied with an operating system.',
        };
    }
}
