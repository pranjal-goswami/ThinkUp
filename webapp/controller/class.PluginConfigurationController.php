<?php
/**
 * Plugin Configuration Controller - Extends ThinkUpAuthController to add configuration option functionality
 *  <code>
 *
 *      $this->addPluginOption(FORM_TEXT_ELEMENT, array('name' => 'email') );
 *      // you can add a header for an option
 *      $this->addPluginOptionHeader('email', 'Please add an email address for this plugin so we can spam you');
 *      // you can also set a special message for required options, the default is:
 *      //  "Please enter a value for the field '{name}'
 *      $this->addPluginOptionRequiredMessage('email', 'You must enter an email so we can spam you');
 *
 *      // you can set a default value for a text element
 *      $this->addPluginOption(FORM_TEXT_ELEMENT, array('name' => 'Location', default => 'New York') );
 *
 *      // by default an option is required, but can be set as optional
 *      $this->setPluginOptionRequired('Bio', false);
 *      $this->addPluginOption(FORM_TEXTAREA_ELEMENT, array('name' => 'Bio') );
 *
 *      // can set a validation regex, in this case service_id must be an integer
 *      $this->addPluginOption(FORM_TEXT_ELEMENT, array('name' => 'service_id', validation_regex => '^\d+$) );
 *
 *      // can set optional label for element
 *      $this->addPluginOption(FORM_TEXT_ELEMENT, array('name' => 'phone', 'label' => "Phone Number") );
 *
 *      $this->addPluginOption(FORM_RADIO_ELEMENT, 
 *          array('name' => 'Gender', value => 'F', 'display_value' => 'Female') );
 *      $this->addPluginOption(FORM_RADIO_ELEMENT, 
 *          array('name' => 'Gender', value => 'M', 'display_value' => 'Male') );
 *      $this->addPluginOption(FORM_RADIO_ELEMENT, 
 *          array('name' => 'Gender', value => 'O', 'display_value' => 'Other', 'default_selection' => true) );
 * *
 *      // select elements hove a few other special attributes
 *      $this->setPluginSelectMultiple('City', true);
 *      $this->setPluginSelectMultiple('Visible', 3); //defaults to one
 *      $this->addPluginOption(FORM_SELECT_ELEMENT, 
 *          array('name' => 'City', value => 'NYC', 'display_value' => 'New York', default_selection' => true ) );
 *      $this->addPluginOption(FORM_RADIO_ELEMENT, 
 *          array('name' => 'Gender', value => 'MSP', 'display_value' => 'Minneapolis') );
 *      $this->addPluginOption(FORM_RADIO_ELEMENT, 
 *          array('name' => 'Gender', value => 'LA') );
 *
 *  </code>
 *
 *
 * @author Mark Wilkie <mwilkie[at]gmail[dot]com>
 */

abstract class PluginConfigurationController extends ThinkUpAuthController {

    /**
     * @const Options markup smarty template
     */
    const OPTIONS_TEMPLATE = '_plugin.options.tpl';

    /**
     * @const Text Form element
     */
    const FORM_TEXT_ELEMENT = 'text_element';
    /**
     * @const radio element
     */
    const FORM_RADIO_ELEMENT = 'radio_element';
    /**
     * @const checkbox element
     */
    const FORM_SELECT_ELEMENT = 'select_element';


    /**
     * @var Array list of option elements
     */
    var $option_elements = array();

    /**
     * @var Array list of option element headers
     */
    var $option_headers = array();

    /**
     * @var Array list of not required options
     */
    var $option_not_required = array();

    /**
     * @var Array list of required failed messages
     */
    var $option_required_message = array();

    /**
     * @var Array list select multi
     */
    var $option_select_multiple = array();

    /**
     * @var Array list select visible
     */
    var $option_select_visible = array();

    /**
     * @var Owner
     */
    var $owner;

    /**
     * @var str folder name
     */
    var $folder_name;

    /**
     * @var int plugin id
     */
    var $plugin_id;

    public function __construct($owner, $folder_name) {
        parent::__construct(true);
        $this->owner = $owner;
        $this->folder_name = $folder_name;
        $this->disableCaching();
    }

    /**
     * Generates plugin page options markup - Calls parent::generateView()
     *
     * @return str view markup
     */
    protected function generateView() {
        // if we have some p[lugin option elements defined
        // render them and add to the parent view...
        if(count($this->option_elements) > 0) {            
            $this->setValues();
            $view_mgr = new SmartyThinkUp();
            $view_mgr->disableCaching();
            // assign data
            $view_mgr->assign('option_elements', $this->option_elements);
            $view_mgr->assign('option_elements_json', json_encode($this->option_elements));
            $view_mgr->assign('option_headers', $this->option_headers);
            $view_mgr->assign('option_not_required', $this->option_not_required);
            $view_mgr->assign('option_not_required_json', json_encode($this->option_not_required));
            $view_mgr->assign('option_required_message', $this->option_required_message);
            $view_mgr->assign('option_required_message_json', json_encode($this->option_required_message));
            $view_mgr->assign('option_select_multiple', $this->option_select_multiple);
            $view_mgr->assign('option_select_visible', $this->option_select_visible);
            $view_mgr->assign('plugin_id', $this->plugin_id);
            $view_mgr->assign('is_admin', $this->isAdmin());
            //$view_mgr->assign('is_admin', false);
            $options_markup = '';
            if ($this->profiler_enabled) {
                $view_start_time = microtime(true);
                $options_markup = $view_mgr->fetch(self::OPTIONS_TEMPLATE);
                $view_end_time = microtime(true);
                $total_time = $view_end_time - $view_start_time;
                $profiler = Profiler::getInstance();
                $profiler->add($total_time, "Rendered view (not cached)", false);
            } else  {
                $options_markup = $view_mgr->fetch(self::OPTIONS_TEMPLATE);
            }
            $this->addToView('options_markup', $options_markup);
        }
        return parent::generateView();
    }

    /**
     * Add a header for an option field
     * @param  str Option name
     * @param  str OptionHeader
     */
    public function addPluginOptionHeader($name, $message) {
        $this->option_headers[$name] = $message;
    }

    /**
     * set an option as not required
     * @param  str option name
     */
    public function setPluginOptionNotRequired($name) {
        $this->option_not_required[$name] = true;
    }


    /**
     * Add a required message for an option field
     * @param  str message
     */
    public function addPluginOptionRequiredMessage($name, $message) {
        $this->option_required_message[$name] = $message;
    }

    /**
     * @param  str Constant value FORM_*_ELEMENT
     * @param  array Arguments for a particular element
     */
    public function addPluginOption($option_type, $args) {

        if(isset($args['name'])) {
            
            $element = array('name' => $args['name'], 'type' => $option_type);
            switch($option_type) {
                case self::FORM_SELECT_ELEMENT:
                    $element['values'] = $args['values'];
                    break;                
                case self::FORM_RADIO_ELEMENT:
                    $element['values'] = $args['values'];
                    break;
                default:
                // text field, do nothing...
            }
            if(isset($args['default_value'])) {
                $element['default_value'] = $args['default_value'];
            }
            if(isset($args['label'])) {
                $element['label'] = $args['label'];
            }
            if(isset($args['id'])) {
                $element['id'] = $args['id'];
            }
            if(isset($args['value'])) {
                $element['value'] = $args['value'];
            }            
            $this->option_elements[$args['name']] = $element;
        }
    }

    /**
     * sets the values for options
     */
    public function setValues() {
        $plugin_dao = DAOFactory::getDAO('PluginDAO');
        $plugin_option_dao = DAOFactory::getDAO('PluginOptionDAO');
        $this->plugin_id = $plugin_dao->getPluginId($this->folder_name);
        $options_values  = $plugin_option_dao->getOptions($this->plugin_id);
        $options_hash = $this->optionList2HashByOptionName($options_values);
        foreach( $this->option_elements as $key => $value) {
            if(isset($options_hash[$key])) {
                $this->option_elements[$key]['id'] = $options_hash[$key]->id;
                $this->option_elements[$key]['value'] = $options_hash[$key]->option_value;
            } else {
                if(isset($this->option_elements[$key]['default_value'])) {
                    $this->option_elements[$key]['value'] = $this->option_elements[$key]['default_value'];
                }
            }
        }
    }


    /**
     * Converts a list of plugin options to a hash with option_name as the key
     * @param array A list of Plugin Options
     * @return array A hash table op Options with option_name as the key
     */
    public function optionList2HashByOptionName($option_list) {
        $options_hash = array();
        if($option_list) {
            foreach ($option_list as $option) {
                $options_hash[ $option->option_name ] = $option;
            }
        }
        return $options_hash;
    }

    /**
     * set plugin id for view, ie: $this->plugin_id = $plugin_id;
     * @param int plugin id
     */
     public function setPlugin($plugin) {
         $this->plugin = $plugin;
     }
}