<?php
function print_header($url,$name,$page,$task="")
{
  global $method;

  $s = implode("_",array_filter(array($method,$task)));

  if ($s == $page) echo "<strong>[";
  echo "<a href=\"$url\">$name</a>";
  if ($s == $page) echo "]</strong>";
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
<?php if (preg_match('/test/',$db)): ?>
  <p><h3 style="color: red">TEST DATABASE</h3></p>
<?php endif; ?>
  <div id="hdrlist">
  eDNA:
  <?php print_header(site_url('samples/edna'),'eDNA samples','edna'); ?> |
  <?php print_header(site_url('samples/multi_edna/add'),'Add multiple eDNA samples','multi_edna'); ?> |
  <?php print_header(site_url('samples/kby_edna/add'),'Add Kāne‘ohe samples','kby_edna'); ?> |
  <?php print_header(site_url('samples/editstate/edna'),'Status update','editstate_edna',$task); ?> |
  <?php print_header(site_url('samples/substrate'),'eDNA Substrate','substrate'); ?> |
  <?php print_header(site_url('samples/edna_method'),'eDNA Method','edna_method'); ?> |
  <?php print_header(site_url('samples/edna_calendar'),'Export Calendar CSV','edna_calendar'); ?> <br>
  Fish:
  <?php print_header(site_url('samples/sample'),'Samples','sample'); ?> |
  <?php print_header(site_url('samples/multi_sample/add'),'Add multiple samples','multi_sample'); ?> |
  <?php print_header(site_url('samples/editstate/fish'),'Status update','editstate_fish',$task); ?> |
  <?php print_header(site_url('samples/fishcount'),'Fish count','fishcount'); ?> |
  <?php print_header(site_url('samples/benthic_obs'),'Benthic observations','benthic_obs'); ?> |
  <?php print_header(site_url('samples/microhab'),'Microhabitat','microhab'); ?> <br>
  General:
  <?php print_header(site_url('samples/station'),'Stations','station'); ?> |
  <?php print_header(site_url('samples/station_map'),'Station map','station_map'); ?> |
  <?php print_header(site_url('samples/grouping'),'Station groupings','grouping'); ?> |
  <?php print_header(site_url('samples/state'),'Sample states','state'); ?> |
  <?php print_header(site_url('samples/protection_status'),'Protection status','protection_status'); ?> |
  <?php print_header(site_url('samples/collector'),'Collectors','collector'); ?> |
  <?php print_header(site_url('samples/taxa'),'Taxa','taxa'); ?> 
  </div>
<div class="headsep" style='height:20px;'></div>  
<!-- End of header-->
