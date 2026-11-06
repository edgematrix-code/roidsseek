<?php
use App\Support\Site;

$phone = trim((string) Site::get('phone', ''));
$whatsapp = trim((string) Site::get('whatsapp', ''));
$hours = trim((string) Site::get('openingHours', ''));
$address = (array) Site::get('address', []);
$hasLocation = (bool) Site::get('hasPhysicalLocation', false);
?>
<?php if (Site::filled($phone)): ?>
    <p class="small"><strong>Phone</strong><br>
        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone) ?? '') ?>"><?= e($phone) ?></a>
    </p>
<?php endif; ?>
<?php if (Site::filled($whatsapp)): ?>
    <p class="small"><strong>WhatsApp</strong><br>
        <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsapp) ?? '') ?>" rel="noopener nofollow" target="_blank"><?= e($whatsapp) ?></a>
    </p>
<?php endif; ?>
<?php if (Site::filled($hours)): ?>
    <p class="small"><strong>Opening hours</strong><br>
        <?= nl2br(e(str_replace('|', "\n", $hours))) ?>
    </p>
<?php endif; ?>
<?php if ($hasLocation && Site::filled($address['street'] ?? '') && Site::filled($address['city'] ?? '')): ?>
    <p class="small"><strong>Address</strong><br>
        <?= e((string) $address['street']) ?><br>
        <?= e(trim(implode(', ', array_filter([
            (string) ($address['city'] ?? ''),
            (string) ($address['region'] ?? ''),
            (string) ($address['postalCode'] ?? ''),
        ])))) ?><br>
        <?= e((string) ($address['country'] ?? '')) ?>
    </p>
<?php endif; ?>
<?php if (!Site::filled($phone) && !Site::filled($whatsapp)): ?>
    <p class="small muted">
        Use the form to reach our support team. Your message is delivered straight to the
        support inbox and we reply within 24 hours.
    </p>
<?php endif; ?>
