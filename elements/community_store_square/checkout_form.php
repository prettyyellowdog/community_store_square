<?php defined('C5_EXECUTE') or die(_("Access Denied."));
    extract($vars);
?>
<script>
    var CSSQP_KEY = "<?php echo $publicAPIKey; ?>",
        CSSQP_LOCATION = "<?php echo $locationKey; ?>",
        CSSQP_PMID = <?php echo $pmID; ?>;
</script>
<script src="<?php echo $squareJsUrl; ?>"></script>
<script src="<?php echo $jsPath; ?>"></script>
<div class="store-credit-card-boxpanel store-square-payment-card panel panel-default">
    <div class="panel-body">
        <div id="square-payment-card-container"></div>
    </div>
</div>
<input type="hidden" id="card-nonce" name="nonce">
