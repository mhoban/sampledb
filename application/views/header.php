<?php
function print_header($url,$name,$page)
{
  global $method,$class;
  if ($method == $page) echo "[";
  echo "<a href=\"$url\">$name</a>";
  if ($method == $page) echo "]";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<base href="<?php echo base_url(); ?>">
		<meta charset="utf-8" />
<?php 
if (isset($css_files)):
foreach($css_files as $file): ?>
		<link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
<?php endforeach; endif;?>

<?php if(isset($js_files)): 
foreach($js_files as $file): ?>

		<script src="<?php echo $file; ?>"></script>
<?php endforeach; endif;?>

<style type='text/css'>
body
{
		font-family: Arial;
		font-size: 14px;
}
a {
		color: blue;
		text-decoration: none;
		font-size: 14px;
}
a:hover
{
		text-decoration: underline;
}
</style>
</head>
<body>
<!-- Beginning header -->
  <div id="hdrlist">
  <?php print_header(site_url('samples/collector'),'Collectors','collector'); ?> |
  <?php print_header(site_url('samples/station'),'Stations','station'); ?> |
  <?php print_header(site_url('samples/fishcount'),'Fish count','fishcount'); ?> |
  <?php print_header(site_url('samples/microhab'),'Microhabitat','microhab'); ?> |
  <?php print_header(site_url('samples/taxa'),'Taxa','taxa'); ?> |
  <?php print_header(site_url('samples/sample'),'Samples','sample'); ?> |
  <?php print_header(site_url('samples/multi_sample/add'),'Add multiple samples','multi_sample'); ?> |
  <?php print_header(site_url('samples/benthic_obs'),'Benthic observations','benthic_obs'); ?>
  </div>
<div class="headsep" style='height:20px;'></div>  
<!-- End of header-->
