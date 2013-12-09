<?php
/**
 * @package      CrowdFunding
 * @subpackage   Plugins
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2013 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.helper');
jimport('joomla.plugin.plugin');

/**
* CrowdFunding Modules plugin
*
* @package 		CrowdFunding
* @subpackage	Plugins
*/
class plgSystemCrowdfundingModules extends JPlugin {
	
	public function onAfterDispatch() {
	     
	    $app = JFactory::getApplication();
	    /** @var $app JSite **/
	
	    if($app->isAdmin()) {
	        return true;
	    }
	
	    $document = JFactory::getDocument();
	    /** @var $document JDocumentHTML **/
	
	    $type = $document->getType();
	    if(strcmp("html",$type) != 0) {
	        return true;
	    }
	
	    // It works only for GET request
	    $method = $app->input->getMethod();
	    if(strcmp("GET", $method) !== 0) {
	        return true;
	    }
	
	    // Check component enabled
	    if (!JComponentHelper::isEnabled('com_crowdfunding', true)) {
	        return true;
	    }
	    
	    $view       = $app->input->getCmd("view");
	    $option     = $app->input->getCmd("option");
	    
	    $isCrowdFundingComponent = (strcmp($option, "com_crowdfunding") == 0);
	    $isDetailsPage           = (strcmp($option, "com_crowdfunding") == 0 AND strcmp($view, "details") == 0);
	    
	    // Allowed views for the module CrowdFunding Details
	    $allowedViews = array("backing", "embed");
	    
	    if($this->params->get("module_info_details_page", 0)) {
	        
	        if(!$isCrowdFundingComponent OR !$isDetailsPage) {
	            $this->hideModule("mod_crowdfundinginfo");
	        }
	        
	    }
	    
	    if($this->params->get("module_rewards_details_page", 0)) {
	        
	        if(!$isCrowdFundingComponent OR !$isDetailsPage) {
	            $this->hideModule("mod_crowdfundingrewards");
	        
	        } else { // Check project type. If the reawards are disable, hide the module.
	            
	            $projectId = $app->input->getInt("id");
	            if(!empty($projectId)) {
	               
	                jimport("crowdfunding.project");
	                jimport("crowdfunding.type");
	                
	                $project   = CrowdFundingProject::getInstance($projectId);
	                $type      = $project->getType();
	               
	                // Hide the module crowdfunding rewards, if rewards are disabled for this type.
	                if(!is_null($type) AND !$type->isRewardsEnabled()) { 
                        $this->hideModule("mod_crowdfundingrewards");
	                }
	                
	            }
	            
	        }
	         
	    }
	    
	    // Module Profile Details page
	    if($this->params->get("module_profile_details_page", 0)) {
	         
	        if(!$isCrowdFundingComponent OR !$isDetailsPage) {
	            $this->hideModule("mod_crowdfundingprofile");
	        }
	         
	    }
	    
	    // Backing page
	    if($this->params->get("module_details_backing_page", 0)) {
	        
	        if( (strcmp($option, "com_crowdfunding") != 0) OR (strcmp($option, "com_crowdfunding") == 0 AND !in_array($view, $allowedViews)) ) {
	            $this->hideModule("mod_crowdfundingdetails");
	        }
	    
	    }
	    
	    // Embed page
	    if($this->params->get("module_details_embed_page", 0)) {
	         
	        if( (strcmp($option, "com_crowdfunding") != 0) OR (strcmp($option, "com_crowdfunding") == 0 AND !in_array($view, $allowedViews)) ) {
	            $this->hideModule("mod_crowdfundingdetails");
	        }
	         
	    }
	    
	    // Module Filter Discover page
	    if($this->params->get("module_filters_discover_page", 0)) {
	    
	        if( (strcmp($option, "com_crowdfunding") != 0) OR (strcmp($option, "com_crowdfunding") == 0 AND strcmp($view, "discover") != 0) ) {
	            $this->hideModule("mod_crowdfundingfilters");
	        }
	    
	    }
	    
	    
	}
	
	protected function hideModule($moduleName) {
	    
	    $module = JModuleHelper::getModule($moduleName);
	    $seed   = substr(md5(uniqid(time() * rand(), true)), 0, 10);
	    $module->position = "fp".JApplication::getHash($seed);
	    
	}
	
}