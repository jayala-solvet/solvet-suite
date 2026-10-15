<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'numeric' => 'El campo :attribute debe ser numérico.',
    'string' => 'El campo :attribute debe ser texto.',
    'array' => 'El campo :attribute debe ser una lista.',
    'date' => 'El campo :attribute debe ser una fecha válida.',
    'after_or_equal' => 'La fecha :attribute debe ser igual o posterior a :date.',
    'regex' => 'El campo :attribute tiene un formato inválido.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'exists' => 'El registro seleccionado para :attribute no existe.',
    'decimal' => 'El campo :attribute tiene una precisión decimal inválida.',
    'gt' => ['numeric' => 'El campo :attribute debe ser mayor que :value.'],
    'gte' => ['numeric' => 'El campo :attribute debe ser mayor o igual que :value.'],
    'between' => ['numeric' => 'El campo :attribute debe estar entre :min y :max.'],
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.', 'array' => 'El campo :attribute debe tener al menos :min elementos.', 'numeric' => 'El campo :attribute debe ser al menos :min.'],
    'max' => ['string' => 'El campo :attribute no debe superar :max caracteres.', 'array' => 'El campo :attribute no debe superar :max elementos.', 'numeric' => 'El campo :attribute no debe superar :max.'],
    'unique' => 'El valor del campo :attribute ya está registrado.',
    'attributes' => ['customer_id' => 'cliente', 'subject' => 'referencia', 'currency' => 'moneda', 'valid_until' => 'vigencia', 'terms' => 'condiciones', 'evidence' => 'evidencia', 'lines' => 'detalle', 'lines.*.quantity' => 'cantidad', 'lines.*.unit_price' => 'precio unitario', 'lines.*.description' => 'descripción', 'lines.*.discount' => 'descuento', 'lines.*.tax_rate' => 'IVA'],
];
