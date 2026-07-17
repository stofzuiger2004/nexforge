<?php
declare(strict_types=1);

namespace App\Enums;

enum AdminInventoryAdjustmentType: string{
    case Receipt = 'receipt';
    case CorrectionIncrease = 'correction_increase';
    case CorrectionDecrease = 'correction_decrease';
    case Damaged = 'damaged';
    case StockCount = 'stock_count';
}