<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Contact Us</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="contact-data">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="h1 pb-5">Help &amp; Customer Service</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="account-panel">
                    <h2 class="mt-0">Contact details</h2>
                    <p class="small muted">Questions about an order, shipping or a product? Send us a message and we will reply within 24 hours.</p>
                    <p class="small"><strong>Support hours</strong><br>Monday to Saturday, 8am to 8pm UTC</p>
                    <p class="small"><strong>Before you write</strong><br>Most order and shipping questions are covered in the <a href="<?= e(url('faq')) ?>">FAQ</a>.</p>
                    <p class="small">Looking for tracking? Sign in and open <a href="<?= e(url('account/orders')) ?>">My Orders</a>.</p>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="account-panel">
                    <h2 class="mt-0">Send a message</h2>
                    <form action="<?= e(url('contact')) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name" class="required">Your name</label>
                                <input class="form-control" id="name" type="text" name="name" required value="<?= e(old('name', user()['name'] ?? '')) ?>">
                                <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="email" class="required">Email</label>
                                <input class="form-control" id="email" type="email" name="email" required value="<?= e(old('email', user()['email'] ?? '')) ?>">
                                <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group full">
                                <label for="subject">Subject</label>
                                <input class="form-control" id="subject" type="text" name="subject" value="<?= e(old('subject')) ?>">
                            </div>
                            <div class="form-group full">
                                <label for="message" class="required">Message</label>
                                <textarea class="form-control" id="message" name="message" required><?= e(old('message')) ?></textarea>
                                <?php if (!empty($errors['message'])): ?><div class="field-error"><?= e($errors['message']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-mix btn-primary">Send message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="box-online-support support-band">
    <div class="container text-center">
        <div class="default-section-description">
            <h2>Customers Reviews</h2>
            <p>Please leave your review on products or service below.<br> Thank you beforehand.</p>
            <ul>
                <li>Very easy to order</li>
                <li>Easy to navigate. Ordering is easy to understand.</li>
            </ul>
            <a href="<?= e(url('all')) ?>" title="Write a Review" class="btn btn-mix btn-outline-light mr-sm-3 mt-3">Write a Review</a>
            <a href="<?= e(url('all')) ?>" title="View Reviews" class="btn btn-mix btn-outline-light mt-3">View Reviews</a>
        </div>
    </div>
</div>
