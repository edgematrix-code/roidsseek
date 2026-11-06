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
        // No address yet, so the summary is priced for the delivery service the
        // customer picked in the cart modal (international unless they chose US
        // domestic). Checkout lets the shipping country override it.
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

        // The floating cart modal opens on the page the customer lands on.
        $_SESSION['cart_modal_open'] = true;

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

        if (!$this->isAjax()) {
            flash('success', 'Cart updated.');
        }
        $this->respond('cart');
    }

    public function remove(): void
    {
        if (!is_post()) {
            redirect('cart');
        }
        verify_csrf();

        Cart::remove((int) input('product_id', 0));
        if (!$this->isAjax()) {
            flash('success', 'Item removed from your cart.');
        }
        $this->respond('cart');
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

    /**
     * Switch the delivery service in the cart modal.
     *
     * International is the cheaper rate; US domestic costs 20% more and free
     * delivery starts at the configured order threshold. Everything shown in
     * the modal is re-priced by the server and sent back as markup.
     */
    public function shipping(): void
    {
        if (!is_post()) {
            redirect('cart');
        }
        verify_csrf();

        Cart::setShippingService((string) input('service', Cart::SERVICE_INTERNATIONAL));
        $this->respond('cart');
    }

    /** Close the floating cart modal (clears the one-shot open flag). */
    public function closeModal(): void
    {
        unset($_SESSION['cart_modal_open']);

        if ($this->isAjax()) {
            http_response_code(204);
            return;
        }

        $this->redirectBack('');
    }

    private function isAjax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
    }

    private function modalHtml(): string
    {
        return View::partial('partials/cart-modal', ['opensModal' => true]);
    }

    /** Answer with refreshed modal markup, or send a form-post back to its page. */
    private function respond(string $fallbackRoute): void
    {
        $_SESSION['cart_modal_open'] = true;

        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'count' => Cart::count(),
                'html' => $this->modalHtml(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return;
        }

        $this->redirectBack($fallbackRoute);
    }

    /** Return to the page the request came from, or to a cart route. */
    private function redirectBack(string $fallbackRoute): void
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');

        if ($referer !== '') {
            $parts = parse_url($referer);
            if ($parts !== false && !empty($parts['path'])
                && (!isset($parts['host']) || strcasecmp((string) $parts['host'], $host) === 0)) {
                redirect_to(
                    $parts['path'] . (isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '')
                );
            }
        }

        redirect($fallbackRoute);
    }
}
