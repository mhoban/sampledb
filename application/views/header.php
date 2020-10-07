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
.hdr {
  display: block;
}

.parent {
  display: grid;
  grid-template-columns: 65px auto;
  grid-template-rows: repeat(4, 20px);
  grid-column-gap: 0px;
  grid-row-gap: 0px;
}

.div1 { grid-area: 1 / 1 / 2 / 2; }
.div2 { grid-area: 1 / 2 / 2 / 3; }
.div3 { grid-area: 2 / 1 / 3 / 2; }
.div4 { grid-area: 2 / 2 / 3 / 3; }
.div5 { grid-area: 3 / 1 / 4 / 2; }
.div6 { grid-area: 3 / 2 / 4 / 3; }
.div7 { grid-area: 4 / 1 / 5 / 2; }
.div8 { grid-area: 4 / 2 / 5 / 3; }
</style>
</head>
<body>
<!-- Beginning header -->
<?php if (preg_match('/test/',$db)): ?>
  <p><h3 style="color: red">TEST DATABASE</h3></p>
<?php endif; ?>
  <div id="hdrlist" class="parent">
  <div class="div1">
    eDNA:
  </div>
  <div class="div2">
    <?php print_header(site_url('samples/edna'),'eDNA samples','edna'); ?> |
    <?php print_header(site_url('samples/multi_edna/add'),'Add multiple eDNA samples','multi_edna'); ?> |
    <?php print_header(site_url('samples/kby_edna/add'),'Add Kāne‘ohe samples','kby_edna'); ?> |
    <?php print_header(site_url('samples/editstate/edna'),'Status update','editstate_edna',$task); ?> |
    <?php print_header(site_url('samples/substrate'),'eDNA Substrate','substrate'); ?> |
    <?php print_header(site_url('samples/edna_method'),'eDNA Method','edna_method'); ?>
  </div>
  <div class="div3">
    Fish:
  </div>
  <div class="div4">
    <?php print_header(site_url('samples/sample'),'Samples','sample'); ?> |
    <?php print_header(site_url('samples/multi_sample/add'),'Add multiple samples','multi_sample'); ?> |
    <?php print_header(site_url('samples/editstate/fish'),'Status update','editstate_fish',$task); ?> |
    <?php print_header(site_url('samples/fishcount'),'Fish count','fishcount'); ?> |
    <?php print_header(site_url('samples/benthic_obs'),'Benthic observations','benthic_obs'); ?> |
    <?php print_header(site_url('samples/microhab'),'Microhabitat','microhab'); ?> 
  </div>
  <div class="div5">
    General:
  </div>
  <div class="div6">
    <?php print_header(site_url('samples/station'),'Stations','station'); ?> |
    <?php print_header(site_url('samples/station_map'),'Station map','station_map'); ?> |
    <?php print_header(site_url('samples/grouping'),'Station groupings','grouping'); ?> |
    <?php print_header(site_url('samples/state'),'Sample states','state'); ?> |
    <?php print_header(site_url('samples/protection_status'),'Protection status','protection_status'); ?> |
    <?php print_header(site_url('samples/collector'),'Collectors','collector'); ?> |
    <?php print_header(site_url('samples/taxa'),'Taxa','taxa'); ?> 
  </div>
  <div class="div7">
    Exports:
  </div>
  <div class="div8">
    <?php print_header(site_url('samples/export_station'),'Stations from Sample IDs','export_station'); ?> |
    <?php print_header(site_url('samples/edna_calendar'),'eDNA Event Calendar CSV','edna_calendar'); ?> 
  </div>
  </div>
<div class="headsep" style='height:20px;'></div>  
<!-- End of header-->
