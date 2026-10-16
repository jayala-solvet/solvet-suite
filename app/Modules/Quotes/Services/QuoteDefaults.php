<?php

namespace App\Modules\Quotes\Services;

use App\Modules\Core\Models\Company;

class QuoteDefaults
{
    public static function terms(): string
    {
        $company = Company::first();
        if ($company?->quote_terms) {
            return $company->quote_terms;
        }
        $name = $company?->name ?: 'SOLVET-IT';
        if (preg_match('/^solvet[\s-]*it$/i', trim($name))) {
            $name = 'SOLVET-IT (de José María Ayala Benítez)';
        }

        return "El cliente deberá revisar el alcance, los productos y servicios, los precios y las condiciones de esta propuesta antes de aprobarla.\n\n$name se compromete a suministrar únicamente los productos y servicios expresamente detallados, bajo las condiciones indicadas en este documento.\n\nLa aprobación confirma la conformidad del cliente con la propuesta. Cualquier aclaración, requisito adicional o modificación deberá comunicarse al responsable de cuenta y acordarse por escrito. Los cambios de alcance podrán requerir una nueva cotización y ajustes en los plazos de entrega.\n\nLas condiciones particulares expresamente acordadas en esta propuesta complementan estos términos generales.";
    }
}
