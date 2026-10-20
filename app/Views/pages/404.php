<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>Page not found</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data">
    <div class="container">
        <div class="empty-state">
            <h2 class="h1">Page not found</h2>
            <p>The page you are looking for has moved or no longer exists.</p>
            <a class="btn btn-mix btn-primary" href="<?= e(url()) ?>">Back to the store</a>
            <a class="btn btn-mix btn-outline-primary" href="<?= e(url('all')) ?>">Browse products</a>
        </div>
    </div>
</div>
