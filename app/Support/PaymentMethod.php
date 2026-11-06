<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The crypto payment methods offered at checkout.
 *
 * One place describes each coin: its label, receive address, how many network
 * confirmations are required, the amount the customer has to send and the wallet
 * link the buttons open. The checkout, the payment page and the order view all
 * read from here so they can never drift apart.
 */
final class PaymentMethod
{
    public const BITCOIN = 'bitcoin';
    public const ETHEREUM = 'ethereum';
    public const USDT = 'usdt';
    public const USDC = 'usdc';

    /** @return array<int,string> */
    public static function keys(): array
    {
        return [self::BITCOIN, self::ETHEREUM, self::USDT, self::USDC];
    }

    /** True when a submitted value names one of the supported methods. */
    public static function isValid(string $value): bool
    {
        return in_array(
            strtolower(trim($value)),
            ['bitcoin', 'btc', 'ethereum', 'eth', 'ether', 'usdt', 'tether', 'usdc', 'usd-coin'],
            true
        );
    }

    /** Canonical key for an alias such as "btc" or "ETH". */
    public static function normalise(string $value): string
    {
        $v = strtolower(trim($value));
        if (in_array($v, ['ethereum', 'eth', 'ether'], true)) {
            return self::ETHEREUM;
        }
        if (in_array($v, ['usdt', 'tether'], true)) {
            return self::USDT;
        }
        if (in_array($v, ['usdc', 'usd-coin'], true)) {
            return self::USDC;
        }
        return self::BITCOIN;
    }

    /**
     * Official coin mark as inline SVG: no icon font, no external requests.
     *
     * Both marks use the real 32x32 brand geometry (Bitcoin: orange roundel with
     * the white B; Ethereum: the blue diamond), so they read as the actual logos
     * at any size and stay crisp on retina screens.
     */
    public static function icon(string $key): string
    {
        switch (self::normalise($key)) {
            case self::ETHEREUM:
                return '<svg class="coin-icon coin-icon--eth" viewBox="0 0 32 32" aria-hidden="true" focusable="false">'
                    . '<circle cx="16" cy="16" r="16" fill="#627EEA"/>'
                    . '<path fill="#fff" fill-opacity=".602" d="M16.498 4v8.87l7.497 3.35z"/>'
                    . '<path fill="#fff" d="M16.498 4 9 16.22l7.498-3.35z"/>'
                    . '<path fill="#fff" fill-opacity=".602" d="M16.498 21.968v6.027L24 17.616z"/>'
                    . '<path fill="#fff" d="M16.498 27.995v-6.028L9 17.616z"/>'
                    . '<path fill="#fff" fill-opacity=".2" d="m16.498 20.573 7.497-4.353-7.497-3.348z"/>'
                    . '<path fill="#fff" fill-opacity=".602" d="m9 16.22 7.498 4.353v-7.701z"/>'
                    . '</svg>';
            case self::USDT:
                return '<svg class="coin-icon coin-icon--usdt" viewBox="0 0 32 32" aria-hidden="true" focusable="false">'
                    . '<g fill="none" fill-rule="evenodd">'
                    . '<circle cx="16" cy="16" r="16" fill="#26A17B"/>'
                    . '<path fill="#FFF" d="M17.922 17.383v-.002c-.11.008-.677.042-1.942.042-1.01 0-1.721-.03-1.971-.042v.003c-3.888-.171-6.79-.848-6.79-1.658 0-.809 2.902-1.486 6.79-1.66v2.644c.254.018.982.061 1.988.061 1.207 0 1.812-.05 1.925-.06v-2.643c3.88.173 6.775.85 6.775 1.658 0 .81-2.895 1.485-6.775 1.657m0-3.59v-2.366h5.414V7.819H8.595v3.608h5.414v2.365c-4.4.202-7.709 1.074-7.709 2.118 0 1.044 3.309 1.915 7.709 2.118v7.582h3.913v-7.584c4.393-.202 7.694-1.073 7.694-2.116 0-1.043-3.301-1.914-7.694-2.117"/>'
                    . '</g>'
                    . '</svg>';
            case self::USDC:
                return '<svg class="coin-icon coin-icon--usdc" viewBox="0 0 32 32" aria-hidden="true" focusable="false">'
                    . '<g fill="none">'
                    . '<circle fill="#3E73C4" cx="16" cy="16" r="16"/>'
                    . '<g fill="#FFF">'
                    . '<path d="M20.022 18.124c0-2.124-1.28-2.852-3.84-3.156-1.828-.243-2.193-.728-2.193-1.578 0-.85.61-1.396 1.828-1.396 1.097 0 1.707.364 2.011 1.275a.458.458 0 00.427.303h.975a.416.416 0 00.427-.425v-.06a3.04 3.04 0 00-2.743-2.489V9.142c0-.243-.183-.425-.487-.486h-.915c-.243 0-.426.182-.487.486v1.396c-1.829.242-2.986 1.456-2.986 2.974 0 2.002 1.218 2.791 3.778 3.095 1.707.303 2.255.668 2.255 1.639 0 .97-.853 1.638-2.011 1.638-1.585 0-2.133-.667-2.316-1.578-.06-.242-.244-.364-.427-.364h-1.036a.416.416 0 00-.426.425v.06c.243 1.518 1.219 2.61 3.23 2.914v1.457c0 .242.183.425.487.485h.915c.243 0 .426-.182.487-.485V21.34c1.829-.303 3.047-1.578 3.047-3.217z"/>'
                    . '<path d="M12.892 24.497c-4.754-1.7-7.192-6.98-5.424-11.653.914-2.55 2.925-4.491 5.424-5.402.244-.121.365-.303.365-.607v-.85c0-.242-.121-.424-.365-.485-.061 0-.183 0-.244.06a10.895 10.895 0 00-7.13 13.717c1.096 3.4 3.717 6.01 7.13 7.102.244.121.488 0 .548-.243.061-.06.061-.122.061-.243v-.85c0-.182-.182-.424-.365-.546zm6.46-18.936c-.244-.122-.488 0-.548.242-.061.061-.061.122-.061.243v.85c0 .243.182.485.365.607 4.754 1.7 7.192 6.98 5.424 11.653-.914 2.55-2.925 4.491-5.424 5.402-.244.121-.365.303-.365.607v.85c0 .242.121.424.365.485.061 0 .183 0 .244-.06a10.895 10.895 0 007.13-13.717c-1.096-3.46-3.778-6.07-7.13-7.162z"/>'
                    . '</g>'
                    . '</g>'
                    . '</svg>';
            default:
                return '<svg class="coin-icon coin-icon--btc" viewBox="0 0 32 32" aria-hidden="true" focusable="false">'
                    . '<circle cx="16" cy="16" r="16" fill="#F7931A"/>'
                    . '<path fill="#fff" fill-rule="nonzero" d="M23.189 14.02c.314-2.096-1.283-3.223-3.465-3.975l.708-2.84-1.728-.43-.69 2.765c-.454-.114-.92-.22-1.385-.326l.695-2.783L15.596 6l-.708 2.839c-.376-.086-.746-.17-1.104-.26l.002-.009-2.384-.595-.46 1.846s1.283.294 1.256.312c.7.175.826.638.805 1.006l-.806 3.235c.048.012.11.03.18.057l-.183-.045-1.13 4.532c-.086.212-.303.531-.793.41.018.025-1.256-.313-1.256-.313l-.858 1.978 2.25.561c.418.105.828.215 1.231.318l-.715 2.872 1.727.43.708-2.84c.472.127.93.245 1.378.357l-.706 2.828 1.728.43.715-2.866c2.948.558 5.164.333 6.097-2.333.752-2.146-.037-3.385-1.588-4.192 1.13-.26 1.979-1.003 2.206-2.537zm-3.949 5.537c-.535 2.145-4.144.985-5.316.694l.948-3.8c1.172.293 4.93.872 4.368 3.106zm.534-5.568c-.488 1.953-3.489.96-4.465.717l.86-3.447c.975.243 4.113.696 3.605 2.73z"/>'
                    . '</svg>';
        }
    }

    /** True when an address looks like it belongs to this coin's network. */
    public static function addressFits(string $key, string $address): bool
    {
        $address = trim($address);
        if ($address === '') {
            return false;
        }

        if (self::normalise($key) === self::ETHEREUM) {
            return preg_match('/^0x[0-9a-fA-F]{40}$/', $address) === 1;
        }

        // Base58 (1…/3…) or bech32 (bc1…).
        return preg_match('/^(?:[13][1-9A-HJ-NP-Za-km-z]{25,34}|bc1[02-9ac-hj-np-z]{11,71})$/i', $address) === 1;
    }

    /**
     * Every supported method, keyed by its canonical name.
     *
     * @return array<string,array{key:string,label:string,symbol:string,network:string,token:string,address:string,confirmations:int,usd_rate:float,decimals:int,wallet_scheme:string,explorer:string,blurb:string,qr_image:string}>
     */
    public static function all(): array
    {
        $btc = (array) config('payments.btc', []);
        $eth = (array) config('payments.eth', []);
        $usdt = (array) config('payments.usdt', []);
        $usdc = (array) config('payments.usdc', []);

        return [
            self::BITCOIN => [
                'key' => self::BITCOIN,
                'label' => 'Bitcoin',
                'symbol' => 'BTC',
                'network' => 'Bitcoin',
                'token' => 'Bitcoin (BTC)',
                'address' => trim((string) ($btc['address'] ?? '')),
                'confirmations' => max(1, (int) ($btc['confirmations'] ?? 2)),
                'usd_rate' => (float) ($btc['usd_rate'] ?? 0),
                'decimals' => 8,
                'wallet_scheme' => 'bitcoin',
                'explorer' => 'https://mempool.space/tx/',
                'blurb' => 'Send Bitcoin (BTC) on the Bitcoin network.',
                'qr_image' => 'tokens-qrcode/btc-qrcode.jpg',
            ],
            self::ETHEREUM => [
                'key' => self::ETHEREUM,
                'label' => 'Ethereum',
                'symbol' => 'ETH',
                'network' => 'Ethereum',
                'token' => 'Ethereum (ETH)',
                'address' => trim((string) ($eth['address'] ?? '')),
                'confirmations' => max(1, (int) ($eth['confirmations'] ?? 12)),
                'usd_rate' => (float) ($eth['usd_rate'] ?? 0),
                'decimals' => 6,
                'wallet_scheme' => 'ethereum',
                'explorer' => 'https://etherscan.io/tx/',
                'blurb' => 'Send Ether (ETH) on the Ethereum mainnet (ERC-20 network).',
                'qr_image' => 'tokens-qrcode/ethereum-qrcode.jpg',
            ],
            self::USDT => [
                'key' => self::USDT,
                'label' => 'Tether',
                'symbol' => 'USDT',
                'network' => 'Tron (TRC20)',
                'token' => 'Tether (USDT)',
                'address' => trim((string) ($usdt['address'] ?? '')),
                'confirmations' => max(1, (int) ($usdt['confirmations'] ?? 12)),
                'usd_rate' => 1.0,
                'decimals' => 6,
                'wallet_scheme' => 'tron',
                'explorer' => 'https://tronscan.org/tx/',
                'blurb' => 'Send Tether (USDT) on the Tron TRC20 network.',
                'qr_image' => 'tokens-qrcode/usdt-qrcode.jpg',
            ],
            self::USDC => [
                'key' => self::USDC,
                'label' => 'USD Coin',
                'symbol' => 'USDC',
                'network' => 'Ethereum (ERC20)',
                'token' => 'USD Coin (USDC)',
                'address' => trim((string) ($usdc['address'] ?? '')),
                'confirmations' => max(1, (int) ($usdc['confirmations'] ?? 12)),
                'usd_rate' => 1.0,
                'decimals' => 6,
                'wallet_scheme' => 'ethereum',
                'explorer' => 'https://etherscan.io/tx/',
                'blurb' => 'Send USD Coin (USDC) on the Ethereum ERC20 network.',
                'qr_image' => 'tokens-qrcode/Usdc-Qrcode.jpg',
            ],
        ];
    }

    /**
     * @return array{key:string,label:string,symbol:string,network:string,token:string,address:string,confirmations:int,usd_rate:float,decimals:int,wallet_scheme:string,explorer:string,blurb:string}
     */
    public static function get(?string $key): array
    {
        $all = self::all();

        return $all[self::normalise((string) $key)];
    }

    /**
     * Amount the customer must send, as a decimal string.
     *
     * Bitcoin uses the amount locked on the order at checkout. Ethereum has no
     * per-order unit stored (its wei value exceeds the BIGINT column), so the
     * amount is converted from the order total at the configured ETH rate.
     * Null means no rate is configured and support confirms the amount.
     * Stablecoins (USDT/USDC) use 1:1 rate by default.
     */
    public static function amount(array $order, array $method): ?string
    {
        if ($method['key'] === self::BITCOIN) {
            $sat = (int) ($order['payment_amount_sat'] ?? 0);
            if ($sat > 0) {
                return number_format($sat / 100000000.0, 8, '.', '');
            }
        }

        $rate = (float) $method['usd_rate'];
        if ($rate <= 0) {
            return null;
        }

        return number_format((float) $order['total'] / $rate, (int) $method['decimals'], '.', '');
    }

    /** Wallet deep link (BIP-21 for Bitcoin, EIP-681 for Ethereum). */
    public static function uri(array $method, string $address, ?string $amount, string $label): string
    {
        if ($address === '') {
            return '';
        }

        switch (self::normalise($method['key'])) {
            case self::ETHEREUM:
            case self::USDC:
                $wei = self::etherToWei($amount);
                return 'ethereum:' . $address . ($wei !== null ? '?value=' . $wei : '');
            case self::USDT:
                // Tron uses tron: scheme
                return 'tron:' . $address . ($amount !== null && $amount !== '' ? '?value=' . $amount : '');
            default:
                $uri = 'bitcoin:' . $address . '?label=' . rawurlencode($label);
                if ($amount !== null && $amount !== '') {
                    $uri .= '&amount=' . $amount;
                }
                return $uri;
        }
    }

    /** Transaction explorer link, empty when the hash is not known/fitting. */
    public static function explorerUrl(array $method, string $txHash): string
    {
        $txHash = trim($txHash);
        if ($txHash === '') {
            return '';
        }

        return (string) $method['explorer'] . rawurlencode($txHash);
    }

    /** Convert a decimal ETH amount to wei without bcmath. */
    private static function etherToWei(?string $amount): ?string
    {
        if ($amount === null || $amount === '' || !preg_match('/^\d+(?:\.\d+)?$/', $amount)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $wei = ltrim($whole . substr(str_pad($fraction, 18, '0'), 0, 18), '0');

        return $wei === '' ? '0' : $wei;
    }
}
