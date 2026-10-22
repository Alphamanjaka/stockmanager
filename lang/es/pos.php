<?php

// Pantalla de caja y recibo (módulo Sale)

return [
    'title' => 'Caja',

    // Búsqueda y filtros
    'search_label' => 'Buscar un producto',
    'search_placeholder' => 'Escanee un código o escriba el nombre del producto…',
    'clear_search' => 'Borrar la búsqueda',
    'categories' => 'Categorías',
    'all' => 'Todo',
    'top_sellers' => 'Los más vendidos',
    'brands' => 'Marcas',
    'all_brands' => 'Todas',
    'quantity_multiplier' => 'Cantidad del próximo artículo',

    // Carrito
    'cart' => 'Carrito',
    'cart_empty_title' => 'El carrito está vacío',
    'cart_empty_hint' => 'Escanee un artículo, escriba su nombre o toque una ficha.',
    'total' => 'Total',
    'pay' => 'Cobrar',
    'cancel_sale' => 'Anular la venta',
    'settings' => 'Ajustes de la caja',
    'sound' => 'Pitido en cada artículo',
    'auto_print' => 'Imprimir el recibo automáticamente',

    // Ayuda de teclado
    'shortcuts_title' => 'Atajos de teclado',
    'keys' => ['enter' => 'Intro', 'esc' => 'Esc', 'del' => 'Supr'],
    'shortcuts' => [
        'search' => 'Buscar',
        'multiply' => 'Cantidad y artículo',
        'qty' => 'Cantidad de la línea',
        'lines' => 'Elegir una línea',
        'remove' => 'Quitar la línea',
        'cancel' => 'Anular la venta',
        'pay' => 'Cobrar',
        'clear' => 'Borrar',
    ],

    // Cobro
    'payment_title' => 'Cobro',
    'amount_due' => 'A pagar',
    'subtotal' => 'Subtotal',
    'discount' => 'Descuento',
    'method' => 'Forma de pago',
    'methods' => [
        'cash' => 'Efectivo',
        'mobile_money' => 'Mobile Money',
        'card' => 'Tarjeta bancaria',
    ],
    'amount_received' => 'Importe recibido',
    'amount_received_hint' => 'Déjelo vacío si el cliente paga el importe exacto.',
    'exact_amount' => 'Importe exacto',
    'change_due' => 'Cambio a devolver',
    'customer_optional' => 'Cliente (opcional)',
    'back' => 'Volver',
    'validate' => 'Validar la venta',

    // Venta terminada
    'success_title' => 'Venta registrada',
    'print_receipt' => 'Imprimir el recibo',
    'new_sale' => 'Nueva venta',
    'view_sale' => 'Ver la ficha de la venta',

    // Cliente
    'walk_in' => '— Cliente de paso —',
    'new_customer' => '+ Nuevo cliente',
    'customer_name' => 'Nombre',
    'customer_phone' => 'Teléfono',
    'new_customer_hint' => 'Solo se usa si no se elige un cliente existente.',

    // Recibo
    'receipt' => 'Recibo',
    'receipt_number' => 'Recibo n.º',
    'total_excl_vat' => 'Total sin IVA',
    'vat' => 'IVA :rate %',
    'total_incl_vat' => 'Total IVA incl.',
    'date' => 'Fecha',
    'cashier' => 'Cajero',
    'customer' => 'Cliente',
    'paid_by' => 'Pagado con',
    'received' => 'Recibido',
    'change' => 'Cambio',
    'thanks' => '¡Gracias por su visita!',
    'print' => 'Imprimir',
    'close' => 'Cerrar',

    // Mensajes de la caja (JavaScript)
    'js' => [
        'item' => 'artículo',
        'items' => 'artículos',
        'in_stock' => ':count en stock',
        'out_of_stock' => 'Agotado',
        'in_cart' => 'en el carrito',
        'sold_out' => '«:name» está agotado.',
        'not_enough_stock' => 'Stock insuficiente para «:name»: :count disponible(s).',
        'not_found' => 'Ningún producto corresponde a «:query».',
        'no_results' => 'Ningún producto en esta selección.',
        'more_results' => 'Y :count más: precise la búsqueda o elija una categoría.',
        'removed' => '«:name» quitado del carrito.',
        'cart_restored' => 'Se ha restaurado el carrito en curso.',
        'cart_empty_pay' => 'Añada al menos un artículo antes de cobrar.',
        'confirm_cancel' => '¿Anular la venta en curso?',
        'confirm_cancel_text' => 'Se quitarán todos los artículos del carrito.',
        'confirm_cancel_yes' => 'Sí, vaciar el carrito',
        'confirm_cancel_no' => 'No, continuar',
        'decrease' => 'Disminuir la cantidad',
        'increase' => 'Aumentar la cantidad',
        'remove' => 'Quitar del carrito',
        'quantity' => 'Cantidad',
        'change_due' => 'Cambio a devolver',
        'missing' => 'Faltan',
        'paid_exact' => 'Importe exacto: sin cambio',
        'sale_failed' => 'La venta no se ha registrado',
        'network_error' => 'El servidor no responde. Compruebe la conexión e inténtelo de nuevo: no se ha registrado nada.',
        'session_expired' => 'Su sesión ha caducado. Recargue la página: el carrito se conservará.',
    ],
];
