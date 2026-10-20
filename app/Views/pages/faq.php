<div class="breadcrumb_container">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <nav class="breadcrumb">
                    <ol>
                        <li><a href="<?= e(url()) ?>"><span>Home</span></a></li>
                        <li><span>FAQ</span></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="box-data">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1 class="h1">FAQ</h1>
                <div class="info" style="text-align:center;font-size:12px;color:#9e9fa1;padding-bottom:24px;">
                    <div class="reading-time">Reading Time: 6 min</div>
                </div>
                <div id="l-content" class="info-content">
                    <h2>The Questions</h2>
                    <ol>
                        <?php foreach ($faqs as $index => $faq): ?>
                            <li>
                                <a href="#faq-<?= (int) $faq['id'] ?>">
                                    <h4><?= $index + 1 ?>. <?= e($faq['question']) ?></h4>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>

                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="l-content" id="faq-<?= (int) $faq['id'] ?>" style="margin-top:26px;">
                            <h4><?= $index + 1 ?>. <?= e($faq['question']) ?></h4>
                            <p><?= nl2br(e($faq['answer'])) ?></p>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($faqs === []): ?>
                        <p class="muted">No questions have been published yet.</p>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h4>Still need help?</h4>
                        <p>Our support team replies to every message within 24 hours. <a href="<?= e(url('contact')) ?>">Contact support</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
