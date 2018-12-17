<script>
$(function() {
<?php if ($task == 'add'): ?>
  addSetup();
<?php elseif ($task == 'edit'): ?>
  editSetup(<?php echo $fcid; ?>);
<?php else: ?>
  listSetup();
<?php endif; ?>
});
</script>
<?php if ($task != 'add' && $task != 'edit'): ?>
<style type="text/css">
#newtax {
  display: none;
}
#observations {
  display: none;
}
</style>
<?php endif; ?>
<div id="wrapper">
<div id="top">
<?php echo $output; ?>
</div>
<div id="bottom">
<?php if ($task == 'add' || $task == 'edit'): ?>
<form id="obsform" method="post" action="">
<?php endif; ?>
<table width="80%" id="observations" class="obs_table">
<tr align="left" class='obs-header'><th></th><th>Taxon <a id="newtax" title='Add new taxon...' href="">[+]</a></th><th>Size</th><th>Count</th></tr>
<tr>
</table>
<?php if ($task == 'add' || $task == 'edit'): ?>
</form>
<?php endif; ?>
</div>
</div>