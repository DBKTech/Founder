<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case UnprintAwb = 'unprint_awb';

    case Pending = 'pending';        // COD only (picked up, in transit)
    case OnTheMove = 'on_the_move';  // Online payment (in transit)

    case Completed = 'completed';
    case Returned = 'returned';

    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Approved => 'Approved',
            self::UnprintAwb => 'Unprint AWB',
            self::Pending => 'Pending (COD)',
            self::OnTheMove => 'On The Move',
            self::Completed => 'Completed',
            self::Returned => 'Returned',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }
}
