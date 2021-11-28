<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
?>
<div class="form-group">
    <?php echo $form->label('squareCurrency', t('Currency'))?>
    <?php echo $form->select('squareCurrency', $squareCurrencies, $squareCurrency)?>
</div>

<div class="form-group">
    <?php echo $form->label('squareMode', t('Mode'))?>
    <?php echo $form->select('squareMode', array('sandbox'=>t('Sandbox'), 'live'=>t('Live')), $squareMode)?>
</div>

<div class="form-group">
    <?php echo $form->label('squareSandboxApplicationId', t('Sandbox Application ID'))?>
    <input type="text" name="squareSandboxApplicationId" value="<?php echo $squareSandboxApplicationId?>" class="form-control">
</div>

<div class="form-group">
    <?php echo $form->label('squareSandboxAccessToken', t('Sandbox Access Token'))?>
    <input type="text" name="squareSandboxAccessToken" value="<?php echo $squareSandboxAccessToken?>" class="form-control">
</div>

<div class="form-group">
    <?php echo $form->label('squareSandboxLocation', t('Sandbox Location'))?>
    <input type="text" name="squareSandboxLocation" value="<?php echo $squareSandboxLocation?>" class="form-control">
</div>

<div class="form-group">
    <?php echo $form->label('squareLiveApplicationId', t('Live Application ID'))?>
    <input type="text" name="squareLiveApplicationId" value="<?php echo $squareLiveApplicationId?>" class="form-control">
</div>

<div class="form-group">
    <?php echo $form->label('squareLiveAccessToken', t('Live Access Token'))?>
    <input type="text" name="squareLiveAccessToken" value="<?php echo $squareLiveAccessToken?>" class="form-control">
</div>

<div class="form-group">
    <?php echo $form->label('squareLiveLocation', t('Live Location'))?>
    <input type="text" name="squareLiveLocation" value="<?php echo $squareLiveLocation?>" class="form-control">
</div>
