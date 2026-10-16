<?php

// Checkout screen and receipt (Sale module)

return [
    'title' => 'Checkout',

    // Search and filters
    'search_label' => 'Search for a product',
    'search_placeholder' => 'Scan a barcode or type a product name…',
    'clear_search' => 'Clear search',
    'categories' => 'Categories',
    'all' => 'All',
    'top_sellers' => 'Best sellers',
    'brands' => 'Brands',
    'all_brands' => 'All',
    'quantity_multiplier' => 'Quantity of the next item',

    // Cart
    'cart' => 'Cart',
    'cart_empty_title' => 'The cart is empty',
    'cart_empty_hint' => 'Scan an item, type its name or tap a tile.',
    'total' => 'Total',
    'pay' => 'Take payment',
    'cancel_sale' => 'Cancel sale',
    'settings' => 'Checkout settings',
    'sound' => 'Beep on each item',
    'auto_print' => 'Print the receipt automatically',

    // Keyboard help
    'shortcuts_title' => 'Keyboard shortcuts',
    'keys' => ['enter' => 'Enter', 'esc' => 'Esc', 'del' => 'Del'],
    'shortcuts' => [
        'search' => 'Search',
        'multiply' => 'Quantity then item',
        'qty' => 'Line quantity',
        'lines' => 'Select a line',
        'remove' => 'Remove line',
        'cancel' => 'Cancel sale',
        'pay' => 'Take payment',
        'clear' => 'Clear',
    ],

    // Payment
    'payment_title' => 'Payment',
    'amount_due' => 'Amount due',
    'subtotal' => 'Subtotal',
    'discount' => 'Discount',
    'method' => 'Payment method',
    'methods' => [
        'cash' => 'Cash',
        'mobile_money' => 'Mobile Money',
        'card' => 'Bank card',
    ],
    'amount_received' => 'Amount received',
    'amount_received_hint' => 'Leave empty if the customer pays the exact amount.',
    'exact_amount' => 'Exact amount',
    'change_due' => 'Change due',
    'customer_optional' => 'Customer (optional)',
    'back' => 'Back',
    'validate' => 'Complete sale',

    // Sale completed
    'success_title' => 'Sale recorded',
    'print_receipt' => 'Print receipt',
    'new_sale' => 'New sale',
    'view_sale' => 'View sale details',

    // Customer
    'walk_in' => '— Walk-in customer —',
    'new_customer' => '+ New customer',
    'customer_name' => 'Name',
    'customer_phone' => 'Phone',
    'new_customer_hint' => 'Used only if no existing customer is selected.',

    // Receipt
    'receipt' => 'Receipt',
    'receipt_number' => 'Receipt no.',
    'date' => 'Date',
    'cashier' => 'Cashier',
    'customer' => 'Customer',
    'paid_by' => 'Paid by',
    'received' => 'Received',
    'change' => 'Change',
    'thanks' => 'Thank you for your visit!',
    'print' => 'Print',
    'close' => 'Close',

    // Messages shown by the checkout (JavaScript)
    'js' => [
        'item' => 'item',
        'items' => 'items',
        'in_stock' => ':count in stock',
        'out_of_stock' => 'Out of stock',
        'in_cart' => 'in cart',
        'sold_out' => '“:name” is out of stock.',
        'not_enough_stock' => 'Not enough stock for “:name”: :count available.',
        'not_found' => 'No product matches “:query”.',
        'no_results' => 'No product in this selection.',
        'more_results' => 'And :count more: refine the search or pick a category.',
        'removed' => '“:name” removed from the cart.',
        'cart_restored' => 'The current cart has been restored.',
        'cart_empty_pay' => 'Add at least one item before taking payment.',
        'confirm_cancel' => 'Cancel the current sale?',
        'confirm_cancel_text' => 'Every item will be removed from the cart.',
        'confirm_cancel_yes' => 'Yes, empty the cart',
        'confirm_cancel_no' => 'No, keep going',
        'decrease' => 'Decrease quantity',
        'increase' => 'Increase quantity',
        'remove' => 'Remove from cart',
        'quantity' => 'Quantity',
        'change_due' => 'Change due',
        'missing' => 'Still missing',
        'paid_exact' => 'Exact amount: no change',
        'sale_failed' => 'The sale was not recorded',
        'network_error' => 'The server is not responding. Check the connection and try again: nothing was recorded.',
        'session_expired' => 'Your session has expired. Reload the page: the cart will be kept.',
    ],
];
