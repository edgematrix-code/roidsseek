<?php
use App\Support\Site;

$regulatory = (array) Site::get('regulatory', []);
$rows = [
    'Pharmacy licence number' => $regulatory['pharmacyLicenceNumber'] ?? '',
    'Licensing authority' => $regulatory['licensingAuthorityName'] ?? '',
    'Superintendent pharmacist' => $regulatory['superintendentPharmacistName'] ?? '',
    'Superintendent licence number' => $regulatory['superintendentLicenceNumber'] ?? '',
    'Medicines regulator' => $regulatory['medicinesRegulatorName'] ?? '',
    'Data protection law' => $regulatory['dataProtectionLawName'] ?? '',
];
$rows = array_filter($rows, static fn ($value) => Site::filled($value));
$legalName = (string) Site::get('legalName', '');
?>
<?php if ($rows !== []): ?>
    <section class="regulatory-block">
        <h2>Licence and regulatory information</h2>
        <?php if (Site::filled($legalName)): ?>
            <p>Published by <?= e($legalName) ?>.</p>
        <?php endif; ?>
        <dl>
            <?php foreach ($rows as $label => $value): ?>
                <dt><?= e($label) ?></dt>
                <dd><?= e((string) $value) ?></dd>
            <?php endforeach; ?>
        </dl>
        <?php if (!Site::filled($legalName)): ?>
            <p class="small muted">
                Verify every licence and registration number with the named authority before relying on it.
            </p>
        <?php endif; ?>
    </section>
<?php endif; ?>
