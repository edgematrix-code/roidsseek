<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Login</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data f-zform login">
    <div class="container">
        <div class="form-fields">
            <h1 class="h1">Login</h1>
            <form method="post" action="<?= e(url('login')) ?>">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-12">
                        <p align="right">You are always Welcome on <span style="font-weight: bold;">RoidsSeek</span></p>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="email" class="required">E-mail</label>
                        <input type="email" name="email" id="email" value="<?= e(old('email')) ?>" class="form-control" placeholder="E-mail" required autocomplete="off">
                        <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="password" class="required">Password</label>
                        <input type="password" name="password" id="password" value="" class="form-control" maxlength="200" required placeholder="Password">
                        <?php if (!empty($errors['password'])): ?><div class="field-error"><?= e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-12 d-flex align-items-center justify-content-center">
                        <button type="submit" name="submitBtn" id="submitBtn" class="btn btn-mix btn-primary">Log In</button>
                    </div>
                    <div class="col-md-12 d-flex align-items-center justify-content-center">
                        <div class="no-account text-center">
                            <a href="<?= e(url('register')) ?>">Create New Account</a> <span>or</span>
                            <a href="<?= e(url('forgot')) ?>">Forgot password</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
