<?php

/* @var Concrete\Core\Application\Application $app */
/* @var Concrete\Core\Entity\Geolocator $geolocator */
/* @var Concrete\Core\Geolocator\GeolocatorController $controller */
/* @var Concrete\Core\Form\Service\Form $form */

$configuration = $geolocator->getGeolocatorConfiguration();

$productIds = [
    'GeoLite2-City' => 'GeoLite2 City',
    'GeoLite2-Country' => 'GeoLite2 Country',
    'GeoLite-Legacy-IPv6-City' => 'GeoLite Legacy IPv6 City',
    'GeoLite-Legacy-IPv6-Country' => 'GeoLite Legacy IPv6 Country',
    '533' => 'GeoLite Legacy City',
    '506' => 'GeoLite Legacy Country',
];
if ($configuration['product-id'] && !isset($productIds[$configuration['product-id']])) {
    $productIds[$configuration['product-id']] = $configuration['product-id'];
}
$postedProductId = $form->getRequestValue('maxmindgl-productid');
if (is_string($postedProductId)) {
    if (!isset($productIds[$postedProductId])) {
        $productIds[$postedProductId] = $postedProductId;
    }
    $selectedProductId = $postedProductId;
} else {
    $selectedProductId = $configuration['product-id'];
}
?>
<div class="alert alert-info">
    <?= t('This product includes GeoLite2 data created by MaxMind, available from %s', '<a target="_blank" href="https://www.maxmind.com">www.maxmind.com</a>.') ?>
</div>
<div class="alert alert-warning">
    <?= t(
        'In order to keep the database up-to-date you should run the %1$s authomated job or the %2$s CLI command on a regular basis.',
        '<code><a target="_blank" href="' . URL::to('/dashboard/system/optimization/jobs') . '">' . h('Update MaxMind database') . '</a></code>',
        '<code>geo:maxmind:update</code>'
    ) ?>
</div>

<div class="form-group">
    <?= $form->label('maxmindgl-productid', t('MaxMind database')) ?>
    <select id="maxmindgl-productid" name="maxmindgl-productid" style="display: none">
        <?php
        foreach ($productIds as $productId => $productName) {
            ?><option value="<?= h($productId) ?>"<?= $productId === $selectedProductId ? ' selected="selected"' : '' ?>><?= h($productName) ?></option><?php
        }
        ?>
    </select>
    <script>
    $(document).ready(function() {
		$('#maxmindgl-productid').selectize({
		    create: true
		});
	});
    </script>
</div>
<div class="form-group">
    <?= $form->label(
        'maxmindgl-databasepath',
        t('Local database location'),
        [
            'class' => 'launch-tooltip',
            'data-html' => 'true',
            'title' => t('Use a relative path to save under the %s directory', '<code>' . DIRNAME_APPLICATION . DIRECTORY_SEPARATOR . 'files</code>'),
        ]
    ) ?>
    <?= $form->text('maxmindgl-databasepath', str_replace('/', DIRECTORY_SEPARATOR, $configuration['database-path']), ['required' => 'required']) ?>
</div>

<div class="form-group">
    <?= $form->label('maxmindgl-licensekey', t('MaxMind license key (required for paid databases)')) ?>
    <?= $form->text('maxmindgl-licensekey', $configuration['license-key']) ?>
</div>

<div class="form-group">
    <?= $form->label('maxmindgl-userid', t('MaxMind user ID (required for paid databases)')) ?>
    <?= $form->number('maxmindgl-userid', $configuration['user-id'], ['step' => '1']) ?>
</div>

<fieldset>

    <legend><?= t('Advanced Options') ?></legend>

    <div class="form-group">
        <?= $form->label('maxmindgl-protocol', t('Protocol to be used to download the MaxMind database')) ?>
        <?= $form->select('maxmindgl-protocol', ['http' => 'http', 'https' => 'https'], $configuration['protocol'], ['required' => 'required']) ?>
    </div>
    
    <div class="form-group">
        <?= $form->label('maxmindgl-host', t('Host to be used to download the MaxMind database')) ?>
        <?= $form->text('maxmindgl-host', $configuration['host'], ['required' => 'required']) ?>
    </div>

</fieldset>
