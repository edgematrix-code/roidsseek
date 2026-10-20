<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Join</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data f-zform register">
    <div class="container">
        <div class="form-fields">
            <h1 class="h1">Join</h1>
            <form method="post" action="<?= e(url('register')) ?>">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-12">
                        <p style="padding:0 10px 14px;font-size:1.15em;text-align:center;">
                            Creating an Account is fast and easy and it costs nothing.<br>
                            By creating an account at RoidsSeek you will be able to shop faster, be up to date on an order's status and keep track of the previous orders you have made.
                        </p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="name" class="required">Full Name</label>
                        <input name="name" id="name" value="<?= e(old('name')) ?>" class="form-control" required placeholder="Full Name" type="text">
                        <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="phone" class="optional">Phone (optional)</label>
                        <input name="phone" id="phone" value="<?= e(old('phone')) ?>" class="form-control" placeholder="Phone" type="text">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="email" class="required">E-mail</label>
                        <input name="email" id="email" value="<?= e(old('email')) ?>" class="form-control" required placeholder="E-mail" type="email">
                        <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="password" class="required">Password</label>
                        <input name="password" id="password" class="form-control" maxlength="50" minlength="8" required placeholder="Password" type="password">
                        <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="password_confirmation" class="required">Repeat Password</label>
                        <input name="password_confirmation" id="password_confirmation" class="form-control" maxlength="50" minlength="8" required placeholder="Repeat Password" type="password">
                        <?php if (!empty($errors['password_confirmation'])): ?><div class="field-error"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-12 form-group">
                        <label class="d-flex align-items-center" style="gap:8px;font-weight:400;">
                            <input type="checkbox" name="terms" value="1" <?= old('terms') ? 'checked' : '' ?>>
                            I agree to the <a href="<?= e(url('page', ['slug' => 'terms-of-use'])) ?>">terms of use</a> and <a href="<?= e(url('page', ['slug' => 'privacy-policy'])) ?>">privacy policy</a>
                        </label>
                        <?php if (!empty($errors['terms'])): ?><div class="field-error"><?= e($errors['terms']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-12 d-flex align-items-center justify-content-center">
                        <input name="submitBtn" id="submitBtn" value="Register" class="btn btn-mix btn-primary" type="submit">
                    </div>
                    <div class="col-md-12 d-flex align-items-center justify-content-center">
                        <div class="no-account text-center">
                            Already have an account? <a href="<?= e(url('login')) ?>">Log In</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
