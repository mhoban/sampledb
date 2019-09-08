<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Samples extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->database();
    $this->load->helper("url");
    $this->load->library("grocery_CRUD");

	}

  function _render_output($template,$output=null,$content_only=false)
  {
    if (is_null($output)) {
      $output = array();
    }
    if (is_object($output)) {
      $output->method = $this->router->method;
      $output->class = $this->router->class;
    }
    else if (is_array($output)) {
      $output['method'] = $this->router->method;
      $output['class'] = $this->router->class;
    }
  
    if (!$content_only)
      $this->load->view("header",$output);
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
    unset($data['collectors']);
    unset($data['sample_prefix']);
    unset($data['number_start']);
    unset($data['number_added']);

    $collectors = array();
    for ($i=0; $i < $to_add; $i++) {
      $data['edna_number'] = sprintf("%s%03d",$prefix,$sample_number+$i);
      $data['notes'] = sprintf('%d/%d',$i+1,$to_add);
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

  function station_map($task=null)
  {
    if ($task == "filter") {
      $filter = $this->input->get('filter');
      $grouping = $this->input->get('grouping');
      $island = $this->input->get('island');
      $country = $this->input->get('country');
      $this->db->select("station_name,lat,lon,notes");
      if ($filter && strlen($filter) > 0)
        $this->db->like("station_name",$filter);
      if ($grouping && $grouping > 0)
        $this->db->where("grouping",$grouping);
      if ($island && strlen($island) > 0)
        $this->db->where("island",$island);
      if ($country && strlen($country) > 0)
        $this->db->where("country",$country);
      $stations = $this->db->get("station")->result_object();
      $this->output->set_output(json_encode($stations));
    } else {
      $config = array(
        'zoom' => 'auto',
        'apiKey' => "AIzaSyDWJ23Tdap-vRO1PcJnlN59X80CaO49YHA"
      );
      $this->load->library("googlemaps",$config);
      //$config['zoom'] = 'auto';
      //$this->googlemaps->initialize($config);

      $this->db->select("station_name,lat,lon,notes");
      $qry = $this->db->get("station");
      foreach ($qry->result() as $row) {
        $marker = array();
        $marker["position"] = $row->lat . ',' . $row->lon;
        //$marker["infowindow_content"] = html_escape($row->notes);
        $marker["title"] = $row->station_name;
        $this->googlemaps->add_marker($marker);
      }

      $output = array();
      $output['map'] = $this->googlemaps->create_map();

      $this->_render_output("station_map",$output);

    }
  }

  function edna_number($prefix=null)
  {
    if ($prefix == null) 
      $prefix = $this->input->get('prefix');
    $max_edna = $this->_max_edna($prefix);
    if (is_null($max_edna)) $max_edna = 0;

    $this->output->set_output(json_encode(array('max_edna' => $max_edna)));
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


  function mlh_number()
  {
    $mlh_num = $this->_max_mlh();
    $this->output->set_output(json_encode(array('mlh_number' => $mlh_num)));
  }


	public function index()
	{
    $this->load->helper('url');
    redirect(base_url('samples/sample'));
	}

  public function collector()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Collector")
      ->set_table("collector");
    $output = $crud->render();//$this->grocery_crud->render();

    $this->_render_output("generic_template",$output);
  }

  public function station($task=null, $id=null, $display=null)
  {
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

  public function grouping($task=null)
  {
    if ($task == "json") {
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

  public function protection_status()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Protection status")
      ->set_table("status");
    $output = $crud->render();//$this->grocery_crud->render();

    $this->_render_output("generic_template",$output);
  }


  public function sample_number($prefix=null)
  {
    if ($prefix == null)
      $prefix = $this->input->get('prefix');
    $sample_num = $this->_max_sample($prefix);
    if (is_null($sample_num)) $sample_num=0;

    $this->output->set_output(json_encode(array('max_sample' => $sample_num)));
  }

  public function multi_sample($task=null)
  {
    if ($task != "add") {
      $this->sample();
      return;
    }
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
  }

  public function sample()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Sample")
      ->set_table("sample")
      ->callback_add_field('mlh_number',function() {
        return '<input type="text" class="form-control" name="mlh_number" id="field-mlh_number" value="' . ($this->_max_mlh()+1) . '">';
      })
      ->display_as('mlh_number','MLH number')
      ->display_as("taxon_id","Taxon")
      ->set_relation("taxon_id","taxa","{genus} {species}")
      ->set_relation("station_id","station","{station_name} ({station_id})")
      ->callback_column($this->_unique_field_name('station_id'),array($this,'_linkify_station_id'))
      ->set_relation_n_n("Collectors","collector_sample","collector","sample_id","collector_id","{first_name} {last_name}");
    $output = $crud->render();//$this->grocery_crud->render();

    $this->_render_output("sample_template",$output);
  }

  public function edna($task=null)
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("eDNA Samples")
      ->set_table("edna")
      ->required_fields("edna_number","station_id","substrate_id","method_id","substrate_volume","collection_date")
      ->unset_texteditor("notes")
      ->display_as("edna_number","eDNA ID number")
      ->display_as("station_id","Station")
      ->display_as("substrate_id","Substrate")
      ->display_as("substrate_volume","Substrate volume (L)")
      ->display_as("method_id","Method")
      ->set_relation("station_id","station","{station_name} ({station_id})")
      ->set_relation("method_id","method","{method_name}")
      ->set_relation("substrate_id","substrate","{substrate_name}")
      ->set_relation_n_n("Collectors","collector_edna","collector","edna_id","collector_id","{first_name} {last_name}");

    $output = $crud->render();
    $this->_render_output("generic_template",$output);
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

      $this->_render_output("edna_template",$output);
    } else {
      $this->load->helper('url');
      redirect(base_url('samples/edna'));
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

  public function microhab()
  {
    $crud = new grocery_CRUD();
    $crud->set_subject("Microhabitat")
      ->set_table("microhabitat")
      ->required_fields('habitat_name');
    $output = $crud->render();
    $this->_render_output("generic_template",$output);
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

  public function taxa($task=null,$mode=null)
  {
    if ($task == 'json') {
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
}
?>