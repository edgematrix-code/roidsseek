<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Cart;
use App\Support\Database;
use App\Support\View;

class CartController
{
    public function index(): void
    {
        View::render('pages/cart', [
            'title' => 'Shopping Cart - RoidsSeek',
            'items' => Cart::items(),
            'subtotal' => Cart::subtotal(),
            'discount' => Cart::discount(),
            'shipping' => Cart::shipping(),
            'total' => Cart::total(),
            'coupon' => Cart::coupon(),
        ]);
    }

    public function add(): void
    {
        if (!is_post()) {
            redirect('cart');
        }
        verify_csrf();

        $productId = (int) input('product_id', 0);
        $qty = max(1, (int) input('qty', 1));
        $product = Database::one('SELECT slug FROM products WHERE id = ?', [$productId]);

        if (!Cart::add($productId, $qty)) {
            flash('error', 'That product is no longer available.');
            redirect('cart');
        }

        flash('success', 'Added to your cart.');
        $returnTo = (string) input('return_to', '');
        if ($returnTo === 'cart') {
            redirect('cart');
        }
        if ($product !== null) {
            redirect('product', ['slug' => $product['slug']]);
        }
        redirect('cart');
    }

    public function update(): void
    {
        if (!is_post()) {
            redirect('cart');
        }
        verify_csrf();

        foreach ((array) ($_POST['qty'] ?? []) as $id => $qty) {
            Cart::update((int) $id, (int) $qty);
        }
        flash('success', 'Cart updated.');
        redirect('cart');
    }

    public function remove(): void
    {
        if (!is_post()) {
            redirect('cart');
        }
        verify_csrf();

        Cart::remove((int) input('product_id', 0));
        flash('success', 'Item removed from your cart.');
        redirect('cart');
    }

    public function clear(): void
    {
        if (!is_post()) {
            redirect('cart');
        }
        verify_csrf();

        Cart::clear();
        flash('success', 'Your cart is empty.');
        redirect('cart');
    }

    public function coupon(): void
    {
        if (!is_post()) {
            redirect('cart');
        }
        verify_csrf();

        if (input('remove') !== null) {
            Cart::removeCoupon();
            flash('success', 'Coupon removed.');
            redirect('cart');
        }

        [$ok, $message] = Cart::applyCoupon((string) input('code', ''));
        flash($ok ? 'success' : 'error', $message);
        redirect('cart');
    }
}
