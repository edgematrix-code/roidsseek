<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Forgot Password</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data f-zform">
    <div class="container">
        <div class="form-fields">
            <h1 class="h1">Forgot Password</h1>

            <?php if ($resetLink !== null): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="site-flash site-flash--success">
                            Reset link generated:<br>
                            <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
                            <div class="form-hint">No mail service is configured locally, so the link is shown here.</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form enctype="application/x-www-form-urlencoded" method="post" action="<?= e(url('forgot')) ?>">
                <?= csrf_field() ?>
                <div class="row d-flex justify-content-center">
                    <div class="col-md-10">
                        <span id="lblUseEmail">Please enter your e-mail address as listed in your account and then click SUBMIT. You will receive your username and password in your mailbox within a few minutes. If you have any questions or concerns, please email customer support and they will be more then glad to assist you.</span>
                        <br><br>
                    </div>
                    <div class="col-md-6 col-12 form-group">
                        <label for="email" class="required">Please, enter your email:</label>
                        <input type="email" name="email" id="email" value="<?= e(old('email')) ?>" class="form-control" required placeholder="Please, enter your email">
                        <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4 col-12 align-self-end">
                        <div class="form-group d-flex justify-content-center">
                            <input type="submit" name="submitBtn" id="submitBtn" value="Submit" class="btn btn-mix btn-primary">
                        </div>
                    </div>
                    <div class="col-md-12 d-flex align-items-center justify-content-center">
                        <div class="no-account">
                            <a href="<?= e(url('login')) ?>">Back to Auth</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
