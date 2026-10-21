<footer>
    <div class="footer-container">
        <div class="container">
            <div class="row copyright_container">
                <div class="col-md-4 col-12">
                    <p class="copyright_link">
                        Copyright &copy; <?= date('Y') ?> <?= e(\App\Support\Site::name() !== '' ? \App\Support\Site::name() : '') ?> All Rights Reserved
                    </p>
                </div>
                <div class="col-md-8 col-12">
                    <div class="footer-nav">
                        <a href="<?= e(url('all')) ?>">All products</a>
                        <a href="<?= e(url('articles')) ?>">Guides</a>
                        <a href="<?= e(url('faq')) ?>">FAQ</a>
                        <a href="<?= e(url('contact')) ?>">Contact</a>
                        <?php if (\App\Support\Site::get('hasPhysicalLocation')): ?>
                            <a href="<?= e(url('location')) ?>">Visit us</a>
                        <?php endif; ?>
                        <?php foreach (\App\Support\Nav::pages() as $footerPage): ?>
                            <a href="<?= e(url('page', ['slug' => $footerPage['slug']])) ?>"><?= e($footerPage['title']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
