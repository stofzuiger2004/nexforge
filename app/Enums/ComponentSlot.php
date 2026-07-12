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
}
