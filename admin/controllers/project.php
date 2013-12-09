<?php
/**
 * @package      CrowdFunding
 * @subpackage   Components
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2013 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

jimport('itprism.controller.form.backend');

/**
 * CrowdFunding project controller class.
 *
 * @package		ITPrism Components
 * @subpackage	CrowdFunding
 * @since		1.6
 */
class CrowdFundingControllerProject extends ITPrismControllerFormBackend {
    
    /**
     * Save an item
     * 
     * @todo fix funded duration type 
     */
    public function save(){
        
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
        
        $app = JFactory::getApplication();
        /** @var $app JAdministrator **/
        
        $data    = $app->input->post->get('jform', array(), 'array');
        $itemId  = JArrayHelper::getValue($data, "id");
        
        $redirectOptions = array(
            "task"  => $this->getTask(),
            "id"    => $itemId
        );
        
        $model   = $this->getModel();
        /** @var $model CrowdFundingModelProject **/
        
        $form    = $model->getForm($data, false);
        /** @var $form JForm **/
        
        if(!$form){
            throw new Exception($model->getError(), 500);
        }
            
        // Validate the form
        $validData = $model->validate($form, $data);
        
        // @todo fix this
        $validData["duration_type"] = JArrayHelper::getValue($data, "funding_duration_type");
        
        // Check for errors.
        if($validData === false){
            $this->displayNotice($form->getErrors(), $redirectOptions);
            return;
        }
        
        try {
            
            // Get image
            $files      = $app->input->files->get('jform', array(), 'array');
            $image      = JArrayHelper::getValue($files, "image");
            
            $pitchImage = JArrayHelper::getValue($files, "pitch_image");
            
            jimport('joomla.filesystem.folder');
            jimport('joomla.filesystem.file');
            jimport('joomla.filesystem.path');
            jimport('joomla.image.image');
            jimport('itprism.file.upload.image');
            
            // Upload image
            if(!empty($image['name'])) {
            
                $imageNames    = $model->uploadImage($image);
                if(!empty($imageNames["image"])) {
                    $validData = array_merge($validData, $imageNames);
                }
            
            }
            
            // Upload pitch image
            if(!empty($pitchImage['name'])) {
            
                $pitchImageName    = $model->uploadPitchImage($pitchImage);
                if(!empty($pitchImageName)) {
                    $validData["pitch_image"] = $pitchImageName;
                }
            
            }
            
            $model->save($validData);
            
        } catch (Exception $e) {
            
            JLog::add($e->getMessage());
            throw new Exception(JText::_('COM_CROWDFUNDING_ERROR_SYSTEM'));
        
        }
        
        $this->displayMessage(JText::_('COM_CROWDFUNDING_PROJECT_SAVED'), $redirectOptions);
    
    }
    
    /**
     * Delete image
     */
    public function removeImage() {
    
        // Check for request forgeries.
        JSession::checkToken("get") or jexit(JText::_('JINVALID_TOKEN'));
    
        $app = JFactory::getApplication();
        /** @var $app JAdministrator **/
    
        // Get item id
        $itemId    = $app->input->get->getInt("id");
        $imageType = $app->input->get->getCmd("image_type");
        
        $redirectOptions = array(
            "view"   => "projects",
        );
    
        // Check for registered user
        if(!$itemId) {
            $this->displayNotice(JText::_('COM_CROWDFUNDING_ERROR_INVALID_IMAGE'), $redirectOptions);
            return;
        }
    
        try {
    
            jimport('joomla.filesystem.folder');
            jimport('joomla.filesystem.file');
            jimport('joomla.filesystem.path');
            
            $model = $this->getModel();
            
            switch($imageType) {
                
                case "main":
                    $model->removeImage($itemId);
                    break;
                
                case "pitch":
                    $model->removePitchImage($itemId);
                    break;
            }
    
        } catch (Exception $e) {
            JLog::add($e->getMessage());
            throw new Exception(JText::_('COM_CROWDFUNDING_ERROR_SYSTEM'));
        }
        
        $redirectOptions = array(
            "view"   => "project",
            "layout" => "edit",
            "id"     => $itemId
        );
        
        $this->displayMessage(JText::_('COM_CROWDFUNDING_IMAGE_DELETED'), $redirectOptions);
    
    }
    
    
}