<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span><?= e($page['title']) ?></span></li>
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
                <h1 class="h1"><?= e($page['title']) ?></h1>
                <div class="info-meta">
                    <div class="reading-time">Reading Time: 2 min</div>
                </div>
                <div class="info-content">
                    <?= $page['content'] ?>
                </div>
            </div>
        </div>
    </div>
</div>
