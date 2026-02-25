<?php
require_once __DIR__ . '../../../core/helpers.php';
?>

<style>
    .flash-msg {
        position: fixed;
        top: -100px;
        right: 20px;
        padding: 14px 20px;
        border-radius: 6px;
        color: #fff;
        font-size: 15px;
        z-index: 9999;
        min-width: 250px;
        max-width: 350px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        opacity: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.6s ease;
    }

    .flash-success {
        background-color: #28a745;
    }

    .flash-error {
        background-color: #dc3545;
    }

    .flash-msg img {
        width: 20px;
        height: 20px;
    }

    .flash-show {
        top: 65px;
        opacity: 1;
    }
</style>

<!-- All success-type keys here -->
<?php
$flash_keys = ['success'];
foreach ($flash_keys as $key):
    if ($m = flash_get($key)):
?>

        <div id="flash-<?= $key ?>" class="flash-msg flash-success">
            <?= h($m) ?>
            <img src="https://cdn-icons-png.flaticon.com/128/14090/14090371.png" alt="Success">
        </div>

        <script>
            const box_<?= $key ?> = document.getElementById('flash-<?= $key ?>');
            setTimeout(() => box_<?= $key ?>.classList.add('flash-show'), 100);
            setTimeout(() => box_<?= $key ?>.classList.remove('flash-show'), 5000);
            setTimeout(() => box_<?= $key ?>.style.display = 'none', 5600);
        </script>

<?php endif;
endforeach; ?>

<!-- ERROR FLASH OUTSIDE LOOP -->
<?php if ($m = flash_get('err')): ?>
    <div id="flash-err" class="flash-msg flash-error">
        <?= h($m) ?>
        <img src="https://cdn-icons-png.flaticon.com/128/1828/1828665.png" alt="Error">
    </div>

    <script>
        const errBox = document.getElementById('flash-err');
        setTimeout(() => errBox.classList.add('flash-show'), 100);
        setTimeout(() => errBox.classList.remove('flash-show'), 5000);
        setTimeout(() => errBox.style.display = 'none', 5600);
    </script>
<?php endif; ?>