</main>
</div>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (!empty($scriptsExtra)) : foreach ($scriptsExtra as $s): ?>
<script src="<?= BASE_URL . $s ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
