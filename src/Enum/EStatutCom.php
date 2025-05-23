<?php

namespace App\Enum;

enum EStatutCom: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case PAYMENT_PENDING ='payment_pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::PAID => 'Payée',
            self::SHIPPED => 'Expédiée',
            self::DELIVERED => 'Livrée',
            self::CANCELLED => 'Annulée',
            self::PAYMENT_PENDING => 'Paiement en Attente'
        };
    }
}
