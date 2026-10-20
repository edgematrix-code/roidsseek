<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Reset Password</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data f-zform">
    <div class="container">
        <div class="form-fields">
            <h1 class="h1">Choose a new password</h1>

            <?php if (!$valid): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="site-flash site-flash--error">This reset link is invalid or has expired.</div>
                        <p class="text-center mt-2"><a href="<?= e(url('forgot')) ?>">Request a new link</a></p>
                    </div>
                </div>
            <?php else: ?>
                <form method="post" action="<?= e(url('reset')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="password" class="required">New password</label>
                            <input class="form-control" id="password" type="password" name="password" required minlength="8">
                            <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="password_confirmation" class="required">Confirm password</label>
                            <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required minlength="8">
                            <?php if (!empty($errors['password_confirmation'])): ?><div class="field-error"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-12 d-flex align-items-center justify-content-center">
                            <button type="submit" class="btn btn-mix btn-primary">Update password</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
