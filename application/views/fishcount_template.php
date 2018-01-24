<link type="text/css" rel="stylesheet" href="<?php echo base_url('assets/grocery_crud/css/jquery_plugins/chosen/chosen.css'); ?>">
<link type="text/css" rel="stylesheet" href="<?php echo base_url('assets/grocery_crud/css/ui/simple/jquery-ui-1.10.1.custom.min.css'); ?>">
<script src="<?php echo base_url('assets/grocery_crud/js/jquery_plugins/jquery.chosen.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/grocery_crud/js/jquery_plugins/config/jquery.chosen.config.js'); ?>"></script>
<script src="<?php echo base_url('assets/grocery_crud/js/jquery_plugins/ui/jquery-ui-1.10.3.custom.min.js'); ?>"></script>
<div id="wrapper">
<div id="top">
<?php echo $output; ?>
</div>
<?php if ($task == 'add'): ?>
<div id="bottom">
<form id="obsform" method="post" action="assets/f.php">
<table width="80%" id="observations" class="obs_table">
<tr align="left"><th></th><th>Taxon <a id="newtax" title='Add new taxon...' href="">[+]</a></th><th>Size</th><th>Count</th></tr>
<tr>
</table>
</form>
</div>
<?php endif; ?>
</div>