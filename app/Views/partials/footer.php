<footer>
    <div class="footer-container">
        <div class="container">
            <div class="row copyright_container">
                <div class="col-md-4 col-12">
                    <p class="copyright_link">
                        Copyright &copy; <?= date('Y') ?> ROIDSSEEK.TO All Rights Reserved
                    </p>
                </div>
                <div class="col-md-8 col-12">
                    <div class="footer-nav">
                        <a href="<?= e(url('page', ['slug' => 'terms-of-use'])) ?>" title="Terms of Use">Terms of Use</a>
                        <a href="<?= e(url('page', ['slug' => 'privacy-policy'])) ?>" title="Privacy Policy">Privacy Policy</a>
                        <a href="<?= e(url('page', ['slug' => 'shipping'])) ?>" title="Shipping Info">Shipping Info</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
