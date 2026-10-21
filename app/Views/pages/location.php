<?php
use App\Support\Site;

$address = (array) Site::get('address', []);
$phone = (string) Site::get('phone', '');
$email = (string) Site::get('email', '');
$hours = (string) Site::get('openingHours', '');
?>
<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Location</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data info-page-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1 class="h1">Visit <?= e(Site::name() !== '' ? Site::name() : 'us') ?></h1>
                <div class="info-content">
                    <p>Our staffed location and opening hours are listed below. Please contact us before travelling for prescription or collection queries.</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h2>Address</h2>
                        <address>
                            <?= e((string) ($address['street'] ?? '')) ?><br>
                            <?= e(trim(implode(', ', array_filter([
                                (string) ($address['city'] ?? ''),
                                (string) ($address['region'] ?? ''),
                                (string) ($address['postalCode'] ?? ''),
                            ])))) ?><br>
                            <?= e((string) ($address['country'] ?? '')) ?>
                        </address>
                        <?php if (Site::filled($phone)): ?>
                            <p><strong>Phone</strong><br><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone) ?? '') ?>"><?= e($phone) ?></a></p>
                        <?php endif; ?>
                        <?php if (Site::filled($email)): ?>
                            <p><strong>Email</strong><br><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if (Site::filled($hours)): ?>
                            <h2>Opening hours</h2>
                            <p><?= nl2br(e(str_replace('|', "\n", $hours))) ?></p>
                        <?php endif; ?>
                        <?php $whatsapp = trim((string) Site::get('whatsapp', '')); ?>
                        <?php if (Site::filled($whatsapp)): ?>
                            <p><strong>WhatsApp</strong><br>
                                <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsapp) ?? '') ?>" rel="noopener nofollow" target="_blank"><?= e($whatsapp) ?></a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <?= \App\Support\View::partial('partials/regulatory') ?>
            </div>
        </div>
    </div>
</div>
