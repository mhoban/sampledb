<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Samples extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->database();
    $this->load->helper("url");
    $this->load->helper("form");
    $this->load->library("grocery_CRUD");
    $this->config->load("sampledb");
	}

  # private functions
  #
  function _render_output($template,$output=null,$content_only=false)
  {
    if (is_null($output)) {
      $output = array();
    }
    if (is_object($output)) {
      $output->method = $this->router->method;
      $output->class = $this->router->class;
      $output->task = $this->uri->segment(3);
    }
    else if (is_array($output)) {
      $output['method'] = $this->router->method;
      $output['class'] = $this->router->class;
      $output['task'] = $this->uri->segment(3);
    }
  
    if (!$content_only) {
      if (is_object($output))
        $output->db = $this->db->database;
      else
        $output['db'] = $this->db->database;
      $this->load->view("header",$output);
    }
    $this->load->view($template,$output);
    if (!$content_only)
      $this->load->view("footer",$output);
  }

  function _max_edna($prefix)
  {
    $this->db->select("substring(edna_number,char_length('$prefix')+1) as `max_num`");
    $this->db->like('edna_number',$prefix,'after');
    $this->db->order_by('edna_number','desc');
    $this->db->limit(1);
    $result = $this->db->get('edna')->result();
    if (count($result)) {
      return $result[0]->max_num;
    } else {
      return 0;
    }
  }

  function _max_mlh()
  {
    $this->db->select_max('mlh_number');
    return $this->db->get('sample')->result()[0]->mlh_number;
  }
  
  function _max_sample($prefix)
  {
    $this->db->select_max('sample_number');
    $this->db->where('sample_prefix',$prefix);
    return $this->db->get('sample')->result()[0]->sample_number;
  }

  function _insert_multiple_samples($data)
  {
    $num = $data['number_collected'];
    $c = $data['collectors'];
    $sample_num = $data['sample_number'];
    $fl = $data['fork_lengths'];
    $mlh = $data['mlh_number'];
    unset($data['number_collected']);
    unset($data['collectors']);
    unset($data['fork_lengths']);

    //get the next sample number for this species
    if (is_null($sample_num)) {
      $sample_num = $this->_max_sample($data['sample_prefix']);
      $sample_num = $sample_num ? $sample_num+1 : 1;
    }

    if ($fl == 0) {
      $fl = null;
    }

    $forks = array();
    if (!is_null($fl)) {
      $forks = explode(',',$fl);
      if (count($forks) != $num) {
        while (count($forks) < $num) {
          $forks[] = null;
        }
        if (count($forks) > $num) {
          $forks = array_slice($forks,0,$num);
        }
      }
    } else {
      $forks = array_fill(0,null,$num);
    }

    $collectors = array();

    for ($i=0; $i<$num; $i++) {
      $data['sample_number'] = $sample_num++;
      $data['mlh_number'] = $mlh++;
      $data['fork_length'] = $forks[$i];
      $res = $this->db->insert('sample',$data);
      if ($res) {
        $id = $this->db->insert_id();
        foreach ($c as $collector_id) {
          $collectors[] = array('sample_id' => $id, 'collector_id' => $collector_id);
        }
      }
    }
    if (count($collectors)) {
      $this->db->insert_batch('collector_sample',$collectors);
    }
    //return true;
  }

  function _linkify_station_id($value,$row)
  {
    $station_id = $row->station_id;
    #$link = $value . ' <a href="'.base_url('samples/station/read/'.$station_id).'">[i]</a>';
    $link = $value . ' <a class="stationid" href="'.$station_id.'">[i]</a>';
    return $link;
  }

  function _unique_field_name($field_name) {
    return 's'.substr(md5($field_name),0,8); //This s is because is better for a string to begin with a letter and not with a number
  }

  function _insert_multiple_edna($data)
  {
    $col = $data['collectors'];
    $prefix = $data['sample_prefix'];
    $sample_number = $data['number_start'];
    $to_add = $data['number_added'];
    $base_notes = $data['notes'];
    $kby = isset($data['kby']);
    unset($data['collectors']);
    unset($data['sample_prefix']);
    unset($data['number_start']);
    unset($data['number_added']);
    unset($data['kby']);

    $collectors = array();
    for ($i=0; $i < $to_add; $i++) {
      $data['edna_number'] = sprintf("%s%03d",$prefix,$sample_number+$i);
      $data['notes'] = sprintf('%d/%d',$i+1,$to_add);
      if ($kby && ($i == ($to_add-1))) {
        $data['method_id'] = 5;
        $data['notes'] .= "\ncontrol";
      }
      if (strlen($base_notes) > 0)
        $data['notes'] .= " - " . $base_notes;
      $res = $this->db->insert('edna',$data);
      if ($res) {
        $id = $this->db->insert_id();
        foreach ($col as $collector_id) {
          $collectors[] = array('edna_id' => $id, 'collector_id' => $collector_id);
        }
      }
    }
    if (count($collectors)) {
      $this->db->insert_batch('collector_edna',$collectors);
    }
  }
  
  function _substrate_sum($s)
  {
    $p = $this->input->post();
    if (($p['sub_20']+$p['sub_20_50']+$p['sub_50_100']+$p['sub_100_150']+$p['sub_150']) != 100) {
      $this->form_validation->set_message('_substrate_sum','Substrate percentages must sum to 100%');
      return false;
    } else {
      return true;
    }
  }

  function _cover_sum($s)
  {
    $p = $this->input->post();
    if (($p['cover_coral']+$p['cover_algae']+$p['cover_cca']+$p['cover_sand']+$p['cover_other']) != 100) {
      $this->form_validation->set_message('_cover_sum','Placeholder percent cover (coral, algae, etc.) must sum to 100%');   
      return false;
    } else {
      return true;
    }
  }

  function _linkify_worms_id($value,$row)
  {
    return "<a target='_blank' title='Open WORMS page in new window/tab' href='http://marinespecies.org/aphia.php?p=taxdetails&id=".$value."'>$value</a>";
  }

  function _add_fishcount($data,$fcid)
  {
    $insert_data = array();
    if ( (count($data['fc-species']) == count($data['fc-count'])) && (count($data['fc-count']) == count($data['fc-size'])) )
    {
      foreach ($data['fc-species'] as $i => $species) {
        $size = $data['fc-size'][$i];
        $count = $data['fc-count'][$i];
        $insert_data[] = array(
          "fishcount_id" => $fcid,
          "taxon_id" => $species,
          "size" => $size,
          "count" => $count
        );
      }  
    }
    if (count($insert_data)) {
      $this->db->insert_batch("fish",$insert_data);
    }
  }

  function _del_fishcount($fcid)
  {
    $this->db->where('fishcount_id',$fcid);
    $this->db->delete('fish');
  }

  function _update_fishcount($data,$fcid)
  {
    $insert_data = array();
    if ( (count($data['fc-species']) == count($data['fc-count'])) && (count($data['fc-count']) == count($data['fc-size'])) )
    {
      foreach ($data['fc-species'] as $i => $species) {
        $size = $data['fc-size'][$i];
        $count = $data['fc-count'][$i];
        $insert_data[] = array(
          "fishcount_id" => $fcid,
          "taxon_id" => $species,
          "size" => $size,
          "count" => $count
        );
      }  
    }
    if (count($insert_data)) {
      //clear out old count observations
      $this->db->where('fishcount_id',$fcid);
      $this->db->delete('fish');

      //add in the new ones
      $this->db->insert_batch("fish",$insert_data);
    }
  }

  # eDNA functions
  # ######################

  public function edna($task=null)
  {
    if (strtolower($task) == "json") {
      $this->db->select("edna_number");
      $q = $this->db->get("edna");
      $r = array();
      foreach ($q->result_array() as $row) {
        $r[] = $row['edna_number'];
      }
      $this->output->set_output(json_encode($r));
    } else {
      $station_filter = $this->input->get("station_filter");
      $crud = new grocery_CRUD();
      $crud->set_subject("eDNA Samples")
           ->set_table("edna")
           ->required_fields("edna_number","station_id","substrate_id","method_id","substrate_volume","collection_date","state_id")
           ->unset_texteditor("notes")
           ->display_as("edna_number","eDNA ID number")
           ->display_as("station_id","Station")
           ->display_as("substrate_id","Substrate")
           ->display_as("substrate_volume","Substrate volume (L)")
           ->display_as("method_id","Method")
           ->display_as("state_id","Sample State")
           ->set_relation("station_id","station","{station_name} ({station_id})")
           ->set_relation("method_id","method","{method_name}")
           ->set_relation("substrate_id","substrate","{substrate_name}")
           ->set_relation("state_id","state","{state_name}")
           ->set_relation_n_n("Collectors","collector_edna","collector","edna_id","collector_id","{first_name} {last_name}");

      $output = $crud->render();
      $output->station_filter = $station_filter;
      $this->_render_output("edna_template",$output);
    }
  }

  public function multi_edna($task=null)
  {
    if ($task == 'add' || $task == 'insert' || $task == 'insert_validation')
    {
      $crud = new grocery_CRUD();
      $crud->set_subject("eDNA Samples")
         ->set_table("edna")
         ->add_fields("station_id","substrate_id","method_id","substrate_volume","collection_date","box_number","collectors","sample_prefix","number_start","number_added","notes")
         ->callback_add_field('number_start',function() {
           return '<input id="field-number_start" class="form-control" name="number_start" type="text" value="' . ($this->_max_edna('SGP')+1) . '">';
           //return '<input type="text" class="form-control" name="mlh_number" id="field-mlh_number" value="' . ($this->_max_mlh()+1) . '">';
         })
         ->callback_add_field("sample_prefix",function() {
           return "<input type=\"text\" id=\"field-sample_prefix\" class=\"form-control\" name=\"sample_prefix\" value=\"SGP\">";
         })
         ->required_fields("station_id","substrate_id","substrate_volume","colletion_date","sample_prefix","number_start","number_added")
         ->set_relation("station_id","station","{station_name} ({station_id})")
         ->set_relation("substrate_id","substrate","substrate_name")
         ->set_relation("method_id","method","{method_name}")
         ->set_relation_n_n("collectors","collector_edna","collector","edna_id","collector_id","{first_name} {last_name}")
         ->display_as("station_id","Station")
         ->display_as("substrate_id","Substrate")
         ->display_as("method_id","Method")
         ->display_as('number_start','Starting sample number')
         ->display_as("number_added","Number of samples to add")
         ->display_as("substrate_volume","Substrate volume (L)")
         ->display_as("notes","Notes")
         ->unset_texteditor("notes")
         ->callback_insert(array($this,'_insert_multiple_edna'));
      $output = $crud->render();//$this->grocery_crud->render();

      $this->_render_output("multi_edna_template",$output);
    } else {
      $this->load->helper('url');
      redirect(base_url('samples/edna'));
    }
  }

  public function kby_edna($task=null)
  {
    if ($task == 'add' || $task == 'insert' || $task == 'insert_validation')
    {
      $crud = new grocery_CRUD();
      $crud->set_subject("Kāne‘ohe bleaching study eDNA sample set")
         ->set_table("edna")
         //->add_fields("station_id","substrate_id","method_id","substrate_volume","collection_date","box_number","collectors","sample_prefix","number_start","number_added","notes")
         ->add_fields("station_id","collection_date","collectors","number_start","notes",
                      "kby","sample_prefix","number_added","substrate_volume","substrate_id","method_id")
         ->callback_add_field('number_start',function() {
           return '<input id="field-number_start" class="form-control" name="number_start" type="text" value="' . ($this->_max_edna('KBY')+1) . '">';
           //return '<input type="text" class="form-control" name="mlh_number" id="field-mlh_number" value="' . ($this->_max_mlh()+1) . '">';
         })
         ->field_type("kby","hidden","true")
         ->field_type("sample_prefix","hidden","KBY")
         ->field_type("number_added","hidden",6)
         ->field_type("substrate_volume","hidden",1)
         ->field_type("substrate_id","hidden",1)
         ->field_type("method_id","hidden",4)
         //->callback_add_field("sample_prefix",function() {
           //return "<input type=\"text\" id=\"field-sample_prefix\" class=\"form-control\" name=\"sample_prefix\" value=\"SGP\">";
         //})
         ->required_fields("station_id","substrate_id","substrate_volume","colletion_date","sample_prefix","number_start","number_added")
         ->set_relation("station_id","station","{station_name} ({station_id})")
         //->set_relation("substrate_id","substrate","substrate_name")
         //->set_relation("method_id","method","{method_name}")
         ->set_relation_n_n("collectors","collector_edna","collector","edna_id","collector_id","{first_name} {last_name}")
         ->display_as("station_id","Station")
         //->display_as("substrate_id","Substrate")
         //->display_as("method_id","Method")
         ->display_as('number_start','Starting sample number (KBY)')
         //->display_as("number_added","Number of samples to add")
         //->display_as("substrate_volume","Substrate volume (L)")
         ->display_as("notes","Notes")
         ->unset_texteditor("notes")
         ->callback_insert(array($this,'_insert_multiple_edna'));
      $output = $crud->render();//$this->grocery_crud->render();

      $this->_render_output("multi_edna_template",$output);
    } else {
      $this->load->helper('url');
      redirect(base_url('samples/edna'));
    }
  }
  
  public function editstate($which=null,$task=null)
  {
    if (!$task) {
      $this->_render_output("states_template",array("which" => $which));
    } else {
      if ($which == "edna") {
        switch($task) {
          case "insert":
            $output = array("success" => false);
            $idlist = $this->input->post("sampleids");
            $state_id = $this->input->post("state_id");
            $idlist = explode("\n",$idlist);
            if (count($idlist) && $state_id) {
              $this->db->set("state_id",$state_id);
              $this->db->where_in("edna_number",$idlist);
              if ($this->db->update("edna")) {
                $output = array("success" => true, "num" => $this->db->affected_rows());
              } else {
                $output = array("success" => false, "num" => 0, "msg" => $this->db->_error_message());
              }
            }
            $this->output->set_output(json_encode($output));
        } 
      }
      
    }
  }

  public function substrate()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Substrate")
       ->set_table("substrate")
       ->required_fields("substrate_name");
    $output = $crud->render();
    $this->_render_output("generic_template",$output);
  }

  public function edna_method()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("eDNA Method")
      ->set_table("method")
      ->required_fields("method_name");

    $output = $crud->render();
    $this->_render_output("generic_template",$output);
  }

  # Fish sample functions
  # ######################

  public function sample($which=null)
  {
    if (strtolower($which) == "json") {
      $this->db->select("CONCAT(sample_prefix,sample_number) as `sample_number`");
      $q = $this->db->get("sample");
      $r = array();
      foreach ($q->result_array() as $row) {
        $r[] = $row['sample_number'];
      }
      $this->output->set_output(json_encode($r));
    } else {
      $station_filter = $this->input->get("station_filter");
      $crud = new grocery_CRUD();
      $crud->set_subject("Sample")
           ->set_table("sample")
           ->callback_add_field('mlh_number',function() {
             return '<input type="text" class="form-control" name="mlh_number" id="field-mlh_number" value="' . ($this->_max_mlh()+1) . '">';
           })
           ->display_as('mlh_number','MLH number')
           ->display_as("taxon_id","Taxon")
           ->display_as("state_id","Sample State")
           ->set_relation("state_id","state","{state_name}")
           ->set_relation("taxon_id","taxa","{genus} {species}")
           ->set_relation("station_id","station","{station_name} ({station_id})")
           ->callback_column($this->_unique_field_name('station_id'),array($this,'_linkify_station_id'))
           ->set_relation_n_n("Collectors","collector_sample","collector","sample_id","collector_id","{first_name} {last_name}");
      $output = $crud->render();//$this->grocery_crud->render();
      $output->station_filter = $station_filter;

      $this->_render_output("sample_template",$output);
    }
  }

  public function multi_sample($task=null)
  {
    if ($task == 'add' || $task == 'insert' || $task == 'insert_validation')
    {
      $crud = new grocery_CRUD();
      $crud->set_subject("Sample")
        ->set_table("sample")
        ->fields('station_id','taxon_id','mlh_number','fork_lengths','sample_prefix','sample_number','box_number','collection_date','collectors','number_added')
        ->callback_add_field("number_added",function() {
          return '<input type="text" name="number_collected" value="" maxlength="50" class="numeric form-control">';
        })
        ->callback_add_field('mlh_number',function() {
          return '<input type="text" class="form-control" name="mlh_number" id="field-mlh_number" value="' . ($this->_max_mlh()+1) . '">';
        })
        ->required_fields('station_id','taxon_id','sample_prefix','collection_date')
        ->set_relation("taxon_id","taxa","{genus} {species}")
        ->set_relation("station_id","station","{station_name} ({station_id})")
        ->set_relation_n_n("collectors","collector_sample","collector","sample_id","collector_id","{first_name} {last_name}")
        ->display_as("station_id","Station")
        ->display_as("taxon_id","Taxon")
        ->display_as('sample_number','Starting sample number')
        ->display_as('mlh_number','Starting MLH number')
        ->callback_insert(array($this,'_insert_multiple_samples'));
      $output = $crud->render();//$this->grocery_crud->render();

      $this->_render_output("sample_template",$output);
    } else {
      $this->load->helper('url');
      redirect(base_url('samples/sample'));
    }
  }

  public function fishcount($task=null, $fcid=null)
  {
    if ($task == 'observations') {
      $this->db->select('taxa.taxon_id,genus,species,count,size');
      $this->db->from('fish');
      $this->db->join('taxa','taxa.taxon_id = fish.taxon_id');
      $this->db->where('fishcount_id',$fcid);
      $this->db->order_by('genus,species');
      $fish = $this->db->get()->result_object();
      $this->output->set_output(json_encode($fish));
    } else {
      $crud = new grocery_CRUD();
      $crud->set_subject('Fish count')
        #->unset_jquery_ui()
        ->set_table('fishcount')
        ->columns('station_id','observation_date','observer_id')
        ->required_fields('station_id','observation_date','observer_id')
        ->set_relation('observer_id','collector','{first_name} {last_name}')
        ->set_relation("station_id","station","{station_name} ({station_id})")
        ->callback_after_insert(array($this,'_add_fishcount'))
        ->callback_before_delete(array($this,'_del_fishcount'))
        ->callback_before_update(array($this,'_update_fishcount'))
        ->display_as('station_id','Station')
        ->display_as('observer_id','Observer')
        ->display_as('observation_date','Date observed');
      $output = $crud->render();
      $output->js_files[] = 'assets/js/taxdlg.js';
      $output->js_files[] = 'assets/js/fishcount.js';
      $output->css_files[] = 'assets/css/fishcount.css';
      $output->task = $task;
      $output->fcid = $fcid;
      $this->_render_output('fishcount_template',$output);
    }
  }

  public function benthic_obs()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Benthic observation")
      ->set_table("benthic_observation")
      ->set_relation("microhabitat_id","microhabitat","habitat_name")
      ->set_relation("station_id","station","{station_name} ({station_id})")
      ->set_relation("observer_id","collector","{first_name} {last_name}")
      ->required_fields("station_id","microhabitat_id","date_observed","sub_20","sub_20_50","sub_50_100","sub_100_150","sub_150",
        "total_relief","cover_coral","cover_algae","cover_cca","cover_sand","cover_other")
      ->display_as("microhabitat_id","Microhabitat")
      ->display_as("observer_id","Observer")
      ->display_as("sub_20","Substrate <20cm (%)")
      ->display_as("sub_20_50","Substrate 20cm - 50cm (%)")
      ->display_as("sub_50_100","Substrate 50cm - 100cm (%)")
      ->display_as("sub_100_150","Substrate 1m - 1.5m (%)")
      ->display_as("sub_150","Substrate >1.5m (%)")
      ->display_as("total_relief","Max. vertical relief (m)")
      ->display_as("cover_coral","Coral cover (%)")
      ->display_as("cover_algae","Macroalgae cover (%)")
      ->display_as("cover_cca","CCA cover (%)%")
      ->display_as("cover_sand","Sand cover (%)")
      ->display_as("cover_other", "Cover (other) (%)%")
      ->set_rules('sub_150','Substrate','callback__substrate_sum')
      ->set_rules('cover_other','% cover','callback__cover_sum');
    $output = $crud->render();
    $this->_render_output("generic_template",$output);
  }

  public function microhab()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Microhabitat")
      ->set_table("microhabitat")
      ->required_fields('habitat_name');
    $output = $crud->render();
    $this->_render_output("generic_template",$output);
  }

  # General functions
  # #################
  
  public function station($task=null, $id=null, $display=null)
  {
    if (strtolower($task) == "json") {
      if (isset($id) && $id != null) {
        $this->db->select("station_name,grouping.grouping_name,status.status_name,island,country,lat,lon,depth_min,depth_max,notes");
        $this->db->from("station");
        $this->db->join("grouping","grouping.grouping_id = station.grouping");
        $this->db->join("status","status.status_id = station.protection_status");
        $this->db->where("station_id",$id);
        $station_info = $this->db->get()->row(); //get()->result_object();
        $this->output->set_output(json_encode($station_info));
      }
    } else {
      $crud = new grocery_CRUD();
      $crud->set_subject("Station")
           ->set_table("station")
           ->set_relation("grouping","grouping","grouping_name")
           ->set_relation("protection_status","status","status_name")
           ->unset_texteditor("notes");
      $output = $crud->render();//$this->grocery_crud->render();
      $output->displaymode = $display=='display' ? true : false;

      $this->_render_output("station_template",$output);
    }
  }

  function station_map($task=null)
  {
    if ($task == "filter") {
      $filter = $this->input->get('filter');
      $sample = $this->input->get('sample');
      $grouping = $this->input->get('grouping');
      $island = $this->input->get('island');
      $country = $this->input->get('country');
      if (!$sample) $sample = array("");
      if (!is_array($sample)) $sample = array($sample);

      $this->db->select("station.station_id,station.station_name,station.lat,station.lon,station.notes,count(edna.edna_id) as `ecount`, count(sample.sample_id) as `scount`");
      $this->db->from("station");
      $this->db->join("edna","edna.station_id = station.station_id","left");
      $this->db->join("sample","sample.station_id = station.station_id","left");
      $this->db->where("station.lat != 0 AND station.lon != 0");
      if ($filter && strlen($filter) > 0)
        $this->db->like("station.station_name",$filter);

      if (count($sample) && $sample[0] != "") {
        $this->db->group_start();
        $this->db->where("edna.edna_number REGEXP",implode("|",$sample));
        $this->db->or_where("concat(sample.sample_prefix,sample.sample_number) REGEXP",implode("|",$sample));
        // $this->db->like("edna.edna_number",$sample);
        // $this->db->or_like("concat(sample.sample_prefix,sample.sample_number)",$sample);
        $this->db->group_end(); 
      }

      if ($grouping && $grouping > 0)
        $this->db->where("station.grouping",$grouping);
      if ($island && strlen($island) > 0)
        $this->db->where("station.island",$island);
      if ($country && strlen($country) > 0)
        $this->db->where("station.country",$country);
      $this->db->group_by("station.station_id");
      $stations = $this->db->get()->result_object();
      $this->output->set_output(json_encode($stations));
      //$this->output->set_output($this->db->get_compiled_select());
      //$this->db->select("station_id,station_name,lat,lon,notes");
      //if ($filter && strlen($filter) > 0)
        //$this->db->like("station_name",$filter);
      //if ($grouping && $grouping > 0)
        //$this->db->where("grouping",$grouping);
      //if ($island && strlen($island) > 0)
        //$this->db->where("island",$island);
      //if ($country && strlen($country) > 0)
        //$this->db->where("country",$country);
      //$stations = $this->db->get("station")->result_object();
    } else {
      //$config = array(
        //'zoom' => "auto",
        //'apiKey' => $this->config->item('sampledb_google_api_key')
      //);
			//$this->load->library("googlemaps",$config);

      $output = array();
			$output['apiKey'] = $this->config->item('sampledb_google_api_key');
			//$output['map'] = $this->googlemaps->create_map();

      $this->_render_output("station_map_template",$output);

    }
  }

  public function grouping($task=null)
  {
    if (strtolower($task) == "json") {
      $this->db->select("grouping_id as `id`,grouping_name as `name`");
      $this->db->from("grouping");
      $groupings = $this->db->get()->result_object();
      $this->output->set_output(json_encode($groupings));

    } else {
      $crud = new grocery_CRUD();
      $crud->set_subject("Groupings")
           ->set_table("grouping");
      $output = $crud->render();//$this->grocery_crud->render();

      $this->_render_output("generic_template",$output);
    }
  }

  public function state($task=null)
  {
    if (strtolower($task) == "json") {
      $this->db->select("state_id as `id`, state_name as `name`");
      $this->db->from("state");
      $states = $this->db->get()->result_object();
      $this->output->set_output(json_encode($states));
    } else {
      $crud = new grocery_CRUD();
      $crud->set_subject("Sample State")
           ->set_table("state");
      $output = $crud->render();//$this->grocery_crud->render();

      $this->_render_output("generic_template",$output);
    }
  }

  public function protection_status()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Protection status")
      ->set_table("status");
    $output = $crud->render();//$this->grocery_crud->render();

    $this->_render_output("generic_template",$output);
  }

  public function collector()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Collector")
      ->set_table("collector");
    $output = $crud->render();//$this->grocery_crud->render();

    $this->_render_output("generic_template",$output);
  }

  public function taxa($task=null,$mode=null)
  {
    if (strtolower($task) == "json") {
      $this->db->from("taxa");
      $this->db->order_by("genus,species");
      $taxlist = $this->db->get()->result_object();
      $this->output->set_output(json_encode($taxlist));
    } else {
      $crud = new grocery_CRUD();
      $crud->set_subject("Taxa")
        ->unset_jquery_ui()
        ->set_table("taxa")
        ->display_as('worms_id','WORMS id')
        ->required_fields('genus','species','worms_id')
        ->callback_column('worms_id',array($this,'_linkify_worms_id'))
        ->callback_field("worms_id",function() {
          return '<select name="worms_id" id="field-worms_id" class="chosen-select" data-placeholder="Enter genus/species"></select>';
        });
      $output = $crud->render();
      $output->task = $task;
      $output->add_dialog = ($task == 'add' && $mode == 'dlg') ? true : false;
      $this->_render_output("taxa_template",$output,$output->add_dialog);
    }
  }

  # Export functions
  # ################

  public function export_station($task=null,$which=null)
  {
    if (strtolower($task) == "json") {
      if ($which == "edna") {
        $sids = $this->input->post("sample_ids");
        // $this->output->set_output("<pre>".print_r($sids,TRUE)."</pre>");
        if (!is_null($sids) && is_array($sids)) {
          $this->db->select(
            "e.edna_number as `edna_number`,
            st.station_name as `station_name`,
            st.island as `island`,
            st.country as `country`,
            st.depth_min as `depth_min`,
            st.depth_max as `depth_max`,
            st.lat as `lat`,
            st.lon as `lon`,
            g.grouping_name as `grouping`"#,
            #st.station_id as `hidden`"
          );
          $this->db->from("edna e");
          $this->db->join("station st","st.station_id = e.station_id","inner");
          $this->db->join("grouping g","g.grouping_id = st.grouping","inner");
          $this->db->where_in("e.edna_number",$sids);
          $this->db->order_by("e.edna_number","ASC");
          $st = $this->db->get()->result_object();
          $this->output->set_output(json_encode($st));
        }
      } else if ($which == "sample") {
        $sids = $this->input->post("sample_ids");
        // $this->output->set_output("<pre>".print_r($sids,TRUE)."</pre>");
        if (!is_null($sids) && is_array($sids)) {
          $this->db->select(
            "CONCAT(s.sample_prefix,s.sample_number) as `sample_number`,
            st.station_name as `station_name`,
            st.island as `island`,
            st.country as `country`,
            st.depth_min as `depth_min`,
            st.depth_max as `depth_max`,
            st.lat as `lat`,
            st.lon as `lon`,
            g.grouping_name as `grouping`"#,
            #st.station_id as `hidden`"
          );
          $this->db->from("sample s");
          $this->db->join("station st","st.station_id = s.station_id","inner");
          $this->db->join("grouping g","g.grouping_id = st.grouping","inner");
          $this->db->where_in("CONCAT(s.sample_prefix,s.sample_number)",$sids);
          $this->db->order_by("`sample_number`","ASC");
          // $sql = $this->db->get_compiled_select();
          // $this->output->set_output($sql);
          $st = $this->db->get()->result_object();
          $this->output->set_output(json_encode($st));
        }
      }
    } else {
      $this->_render_output("export_station_template");
    }
  }

  public function edna_calendar() 
  {
    $this->db->select(
      "st.station_name AS `Subject`, 
       e.collection_date AS `Start Date`, 
       e.collection_date AS `End Date`,  
       GROUP_CONCAT( 
         DISTINCT e.edna_number 
         ORDER BY e.edna_id ASC 
         SEPARATOR '\n' 
       ) AS `Description`" 
    );
    $this->db->from("edna e");
    $this->db->join("station st","st.station_id = e.station_id","inner");
    $this->db->join("grouping g","g.grouping_id = st.grouping","inner");
    $this->db->where("g.grouping_name LIKE '%bleach%'");
    $this->db->group_by("e.station_id, e.collection_date");
    $this->db->order_by("e.collection_date, st.station_name","ASC");
    $q = $this->db->get();

    $this->load->dbutil();
    $csv = $this->dbutil->csv_from_result($q);
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=calendar.csv");
    header("Content-Type: application/csv; ");
    echo $csv;
  }

  # Utility functions
  # #################

  function islands()
  {
    $this->db->distinct();
    $this->db->select("island as `id`, island as `name`");
    $this->db->from("station");
    $islands = $this->db->get()->result_object();
    $this->output->set_output(json_encode($islands));
    
  }

  function countries()
  {
    $this->db->distinct();
    $this->db->select("country as `id`, country as `name`");
    $this->db->from("station");
    $countries = $this->db->get()->result_object();
    $this->output->set_output(json_encode($countries));
  }

  function edna_number($prefix=null)
  {
    if ($prefix == null) 
      $prefix = $this->input->get('prefix');
    $max_edna = $this->_max_edna($prefix);
    if (is_null($max_edna)) $max_edna = 0;

    $this->output->set_output(json_encode(array('max_edna' => $max_edna)));
  }

  function mlh_number()
  {
    $mlh_num = $this->_max_mlh();
    $this->output->set_output(json_encode(array('mlh_number' => $mlh_num)));
  }

  public function sample_number($prefix=null)
  {
    if ($prefix == null)
      $prefix = $this->input->get('prefix');
    $sample_num = $this->_max_sample($prefix);
    if (is_null($sample_num)) $sample_num=0;

    $this->output->set_output(json_encode(array('max_sample' => $sample_num)));
  }

  # default function
	public function index()
	{
    $this->load->helper('url');
    redirect(base_url('samples/edna'));
	}
}
?>
