<?php
/**
 * @package      CrowdFunding
 * @subpackage   Components
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2013 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modelform');

class CrowdFundingModelImport extends JModelForm {
    
    protected function populateState() {
        
        $app = JFactory::getApplication();
        /** @var $app JAdministrator **/
        
        // Load the filter state.
        $value = $app->getUserStateFromRequest('import.context', 'type', "currencies");
        $this->setState('import.context', $value);
        
	}
	
    /**
     * Method to get the record form.
     *
     * @param   array   $data       An optional array of data for the form to interogate.
     * @param   boolean $loadData   True if the form is to load its own data (default case), false if not.
     * @return  JForm   A JForm object on success, false on failure
     * @since   1.6
     */
    public function getForm($data = array(), $loadData = true){
        
        // Get the form.
        $form = $this->loadForm($this->option.'.import', 'import', array('control' => 'jform', 'load_data' => $loadData));
        if(empty($form)){
            return false;
        }
        
        return $form;
    }
    
    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed   The data for the form.
     * @since   1.6
     */
    protected function loadFormData(){
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState($this->option.'.edit.import.data', array());
        return $data;
    }
    
    public function extractFile($file, $destFolder) {
        
        // extract type
        $zipAdapter   = JArchive::getAdapter('zip'); 
        $zipAdapter->extract($file, $destFolder);
        
        $dir          = new DirectoryIterator($destFolder);
        
        $fileName     = JFile::stripExt(JFile::getName($file));
        
        foreach ($dir as $fileinfo) {
            
            $currentFileName  = JFile::stripExt($fileinfo->getFilename());
            
            if (!$fileinfo->isDot() AND strcmp($fileName, $currentFileName) == 0) {
                $filePath     = $destFolder. DIRECTORY_SEPARATOR . JFile::makeSafe($fileinfo->getFilename());
                break;
            }
            
        }
            
        return $filePath;
    }
    
	/**
     * 
     * Import currencies from XML file.
     * The XML file is generated by the current extension ( CrowdFunding )
     * 
     * @param string    $file 	 	A path to file
     * @param bool  	$resetId	Reset existing IDs with new ones.
     */
    public function importCurrencies($file, $resetId = false) {
        
        $xmlstr  = file_get_contents($file);
        $content = new SimpleXMLElement($xmlstr);
        
        if(!empty($content)) {
            $items = array();
            $db    = JFactory::getDbo();
            
            foreach($content as $item) {
                
                // Check for missing ascii characters title
                $title        = JString::trim($item->title);
                if(!$title) {
                    continue;
                }
                
                // Reset ID
                $id =  (!empty($item->id) AND !$resetId) ? JString::trim($item->id) : "null";
                
                $items[] = $id.",".$db->quote($title).",".$db->quote(JString::trim($item->abbr)).",".$db->quote(JString::trim($item->symbol));
            }
            
            unset($content);
           
            $query = $db->getQuery(true);
                
            $query
                ->insert("#__crowdf_currencies")
                ->columns('id, title, abbr, symbol')
                ->values($items);
                
            $db->setQuery($query);
            $db->execute();
            
        }
    }
    
    /**
     * 
     * Import locations from TXT or XML file.
     * The TXT file comes from geodata.org
     * The XML file is generated by the current extension ( CrowdFunding )
     * 
     * @param string    $file 	 	A path to file
     * @param bool  	$resetId	Reset existing IDs with new ones.
     */
    public function importLocations($file, $resetId = false) {
        
        $ext      = JString::strtolower( JFile::getExt($file) );
        
        switch($ext) {
            case "xml":
                $this->importLocationsXml($file, $resetId);
                break;
            default: // TXT
                $this->importLocationsTxt($file, $resetId);
                break;
        }
    }
    
    protected function importLocationsTxt($file, $resetId) {
        
        $content   = file($file);
        
        if(!empty($content)) {
            $items = array();
            $db    = JFactory::getDbo();
            
            unset($file);
            
            $i = 0; 
            $x = 0;
            foreach($content as $geodata) {
                
                $item        = mb_split("\t", $geodata);
                
                // Check for missing ascii characters name
                $name        = JString::trim($item[2]);
                if(!$name) { 
                    // If missing ascii characters name, use utf-8 characters name
                    $name    = JString::trim($item[1]);
                }
                
                // If missing name, skip the record
                if(!$name) {
                    continue;
                }
                
                $id =  (!$resetId) ? JString::trim($item[0]) : "null";
                
                $items[$x][] = $id.",".$db->quote($name).",".$db->quote(JString::trim($item[4])).",".$db->quote(JString::trim($item[5])).",".$db->quote(JString::trim($item[8])).",".$db->quote(JString::trim($item[17]));
                $i++;
                if($i == 500) {
                    $x++;
                    $i=0;
                }
            }
            
            unset($content);
           
            foreach($items as $item) {
                $query = $db->getQuery(true);
                    
                $query
                    ->insert("#__crowdf_locations")
                    ->columns('id, name, latitude, longitude, country_code, timezone')
                    ->values($item);
                    
                $db->setQuery($query);
                $db->execute();
            }
            
            
        }
        
    }
    
    protected function importLocationsXml($file, $resetId) {
        
        $xmlstr  = file_get_contents($file);
        $content = new SimpleXMLElement($xmlstr);
        
        if(!empty($content)) {
            $items = array();
            $db    = JFactory::getDbo();
            
            $i = 0; 
            $x = 0;
            foreach($content->location as $item) {
                
                // Check for missing ascii characters name
                $name        = JString::trim($item->name);
                
                // If missing name, skip the record
                if(!$name) {
                    continue;
                }
                
                // Reset ID
                $id =  (!empty($item->id) AND !$resetId) ? JString::trim($item->id) : "null";
                
                $items[$x][] = $id.",".$db->quote($name).",".$db->quote(JString::trim($item->latitude)).",".$db->quote(JString::trim($item->longitude)).",".$db->quote(JString::trim($item->country_code)).",".$db->quote(JString::trim($item->timezone));
                $i++;
                if($i == 500) {
                    $x++;
                    $i=0;
                }
            }
            
            unset($item);
            unset($content);
           
            foreach($items as $item) {
                $query = $db->getQuery(true);
                    
                $query
                    ->insert("#__crowdf_locations")
                    ->columns('id, name, latitude, longitude, country_code, timezone')
                    ->values($item);
                    
                $db->setQuery($query);
                $db->execute();
            }
            
        }
        
    }
    
    /**
     *
     * Import countries from XML file.
     * The XML file is generated by the current extension ( CrowdFunding ) 
     * or downloaded from https://github.com/umpirsky/country-list
     *
     * @param string    $file 	 	A path to file
     * @param bool  	$resetId	Reset existing IDs with new ones.
     */
    public function importCountries($file, $resetId = false) {
    
        $xmlstr  = file_get_contents($file);
        $content = new SimpleXMLElement($xmlstr);
    
        if(!empty($content)) {
            $items = array();
            $db    = JFactory::getDbo();
    
            foreach($content->country as $item) {
    
                // Check for missing ascii characters title
                $name        = JString::trim($item->name);
                if(!$name) { continue;}
                
                $code = JString::trim($item->code);
    
                // Reset ID
                $id =  (!empty($item->id) AND !$resetId) ? JString::trim($item->id) : "null";
    
                $items[] = $id.",".$db->quote($name).",".$db->quote($code);
                
            }
    
            unset($content);
             
            $query = $db->getQuery(true);
    
            $query
                ->insert("#__crowdf_countries")
                ->columns($db->quoteName(array("id", "name", "code")))
                ->values($items);
    
            $db->setQuery($query);
            $db->execute();
    
        }
    }
    
    /**
     * Import states from XML file.
     * The XML file is generated by the current extension.
     *
     * @param string    $file 	 	A path to file
     */
    public function importStates($file) {
    
        $xmlstr    = file_get_contents($file);
        $content   = new SimpleXMLElement($xmlstr);
    
        $generator = (string)$content->attributes()->generator;
        
        switch($generator) {
            
            case "crowdfunding":
                $this->importCrowdFundingStates($content);
                break;
            
            default:
                $this->importUnofficialStates($content);
                break;
        }
        
        
    }
    
    /**
     * Import states that are based on locations, 
     * and which are connected to locations IDs.
     * 
     * @param SimpleXMLElement $content
     */
    protected function importCrowdFundingStates($content) {
    
        if(!empty($content)) {
    
            $states = array();
            $db     = JFactory::getDbo();
    
            // Prepare data
            foreach($content->state as $item) {
            
                // Check for missing state
                $stateCode        = JString::trim($item->state_code);
                if(!$stateCode) {continue;}
            
                $id = (int)$item->id;
                
                $states[$stateCode][] = "(".$db->quoteName("id")."=".(int)$id.")";
            
            }
            
            // Import data
            foreach($states as $stateCode => $ids) {
        
                $query = $db->getQuery(true);
        
                $query
                    ->update("#__crowdf_locations")
                    ->set($db->quoteName("state_code") ."=". $db->quote($stateCode))
                    ->where(implode(" OR ", $ids));
        
                $db->setQuery($query);
                $db->execute();
            }
        
            unset($states);
            unset($content);
    
        }
    
    }
    
    /**
     * Import states that are based on not official states data,
     * and which are not connected to locations IDs.
     *
     * @param SimpleXMLElement $content
     * 
     * @todo remove this in next major version.
     */
    protected function importUnofficialStates($content) {
        
        if(!empty($content)) {
        
            $states = array();
            $db     = JFactory::getDbo();
        
            foreach($content->city as $item) {
        
                // Check for missing ascii characters title
                $name        = JString::trim($item->name);
                if(!$name) {continue;}
        
                $code = JString::trim($item->state_code);
        
                $states[$code][] = "(".$db->quoteName("name")."=".$db->quote($name)." AND ".$db->quoteName("country_code") ."=".$db->quote("US").")";
        
            }
        
            foreach($states as $stateCode => $cities) {
        
                $query = $db->getQuery(true);
        
                $query
                ->update("#__crowdf_locations")
                ->set($db->quoteName("state_code")." = ". $db->quote($stateCode))
                ->where(implode(" OR ", $cities));
        
                $db->setQuery($query);
                $db->execute();
            }
        
            unset($states);
            unset($content);
        
        }
        
    }
}