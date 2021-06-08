<?php
$model = new waModel();
try {
    $model->query("SELECT `yml_up` FROM `shop_product_skus` WHERE 0");
} catch(waDbException $e) {
    $model->exec("ALTER TABLE `shop_product_skus` ADD `yml_up` INT NULL DEFAULT 0");
}
