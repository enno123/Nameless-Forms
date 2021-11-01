<?php 
/*
 *  Made by Partydragen
 *  https://github.com/partydragen/Nameless-Forms
 *  https://partydragen.com/
 *  NamelessMC version 2.0.0-pr12
 *
 *  License: MIT
 *
 *  Forms module - panel form page
 */

// Can the user view the panel?
if(!$user->handlePanelPageLoad('forms.manage')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'forms');
define('PANEL_PAGE', 'forms');
$page_title = $forms_language->get('forms', 'forms');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

if(!isset($_GET['action'])){
    
    // Get forms from database
    $forms = $queries->orderAll('forms', 'id', 'ASC');
    $forms_array = array();
    if(count($forms)){
        foreach($forms as $form){
            $forms_array[] = array(
                'name' => Output::getClean($form->title),
                'edit_link' => URL::build('/panel/form/', 'form=' . Output::getClean($form->id)),
                'delete_link' => URL::build('/panel/forms/', 'action=delete&id=' . Output::getClean($form->id))
            );
        }
    }
    
    // Get statuses from database
    $statuses = DB::getInstance()->query('SELECT * FROM nl2_forms_statuses WHERE deleted = 0')->results();
    $status_array = array();
    if(count($statuses)){
        foreach($statuses as $status){
            $status_array[] = array(
                'id' => $status->id,
                'html' => $status->html,
                'open' => $status->open,
                'edit_link' => URL::build('/panel/forms/statuses', 'action=edit&id=' . Output::getClean($status->id)),
                'delete_link' => URL::build('/panel/forms/statuses', 'action=delete&id=' . Output::getClean($status->id))
            );
        }
    }
    
    $smarty->assign(array(
        'FORM' => $forms_language->get('forms', 'form'),
        'NEW_FORM' => $forms_language->get('forms', 'new_form'),
        'NEW_FORM_LINK' => URL::build('/panel/forms/', 'action=new'),
        'FORMS_LIST' => $forms_array,
        'NEW_STATUS' => $forms_language->get('forms', 'new_status'),
        'NEW_STATUS_LINK' => URL::build('/panel/forms/statuses/', 'action=new'),
        'STATUS' => $forms_language->get('forms', 'status'),
        'STATUSES' => $forms_language->get('forms', 'statuses'),
        'MARKED_AS_OPEN' => $forms_language->get('forms', 'marked_as_open'),
        'STATUS_LIST' => $status_array,
        'NONE_FORMS_DEFINED' => $forms_language->get('forms', 'none_forms_defined'),
        'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
        'CONFIRM_DELETE_FORM' => $forms_language->get('forms', 'delete_form'),
        'CONFIRM_DELETE_STATUS' => $forms_language->get('forms', 'delete_status'),
        'ACTION' => $forms_language->get('forms', 'action'),
        'YES' => $language->get('general', 'yes'),
        'NO' => $language->get('general', 'no')
        
    ));
            
    $template_file = 'forms/forms.tpl';
} else {
    switch($_GET['action']){
        case 'new':
            // New Form
            if(Input::exists()){
                $errors = array();
                if(Token::check(Input::get('token'))){
                    // Validate input
                    $validate = new Validate();
                    $validation = $validate->check($_POST, array(
                        'form_name' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 32
                        ),
                        'form_url' => array(
                            'required' => true,
                            'min' => 2,
                            'max' => 32
                        ),
                        'form_icon' => array(
                            'max' => 64
                        )
                    ));
                                
                    if($validation->passed()){
                        // Create form
                        try {
                            if(strpos(Input::get('form_url'), '/') === 0) {
                                // Get link location
                                if(isset($_POST['link_location'])){
                                    switch($_POST['link_location']){
                                        case 1:
                                        case 2:
                                        case 3:
                                        case 4:
                                            $location = $_POST['link_location'];
                                            break;
                                        default:
                                        $location = 1;
                                    }
                                } else
                                $location = 1;

                                // Enable captcha?
                                if(isset($_POST['captcha']) && $_POST['captcha'] == 'on') $captcha = 1;
                                else $captcha = 0;
                                        
                                // Save to database
                                $queries->create('forms', array(
                                    'url' => Output::getClean(rtrim(Input::get('form_url'), '/')),
                                    'title' => Output::getClean(Input::get('form_name')),
                                    'link_location' => $location,
                                    'icon' => Input::get('form_icon'),
                                    'captcha' => $captcha,
                                    'content' => Output::getClean(Input::get('content'))
                                ));
                                            
                                Session::flash('staff_forms', $forms_language->get('forms', 'form_created_successfully'));
                                Redirect::to(URL::build('/panel/forms'));
                                die();
                            } else {
                                $errors[] = 'Form URL must begin with a /';
                            }
                        } catch(Exception $e){
                            $errors[] = $e->getMessage();
                        }
                    } else {
                        // Errors
                        foreach($validation->errors() as $item){
                            if(strpos($item, 'is required') !== false){
                                switch($item){
                                    case (strpos($item, 'form_name') !== false):
                                        $errors[] = $forms_language->get('forms', 'input_form_name');
                                    break;
                                    case (strpos($item, 'form_url') !== false):
                                        $errors[] = $forms_language->get('forms', 'input_form_url');
                                    break;
                                }
                            } else if(strpos($item, 'minimum') !== false){
                                switch($item){
                                    case (strpos($item, 'form_name') !== false):
                                        $errors[] = $forms_language->get('forms', 'form_name_minimum');
                                    break;
                                    case (strpos($item, 'form_url') !== false):
                                        $errors[] = $forms_language->get('forms', 'form_url_minimum');
                                    break;
                                }
                            } else if(strpos($item, 'maximum') !== false){
                                switch($item){
                                    case (strpos($item, 'form_name') !== false):
                                        $errors[] = $forms_language->get('forms', 'form_name_maximum');
                                    break;
                                    case (strpos($item, 'form_url') !== false):
                                        $errors[] = $forms_language->get('forms', 'form_url_maximum');
                                    break;
                                    case (strpos($item, 'form_icon') !== false):
                                        $errors[] = $forms_language->get('forms', 'form_icon_maximum');
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    // Invalid token
                    $errors[] = $language->get('general', 'invalid_token');
                }
            }
                        
            $smarty->assign(array(
                'CREATING_NEW_FORM' => $forms_language->get('forms', 'creating_new_form'),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/panel/forms'),
                'FORM_NAME' => $forms_language->get('forms', 'form_name'),
                'FORM_NAME_VALUE' => (isset($_POST['form_name']) ? Output::getClean(Input::get('form_name')) : ''),
                'FORM_ICON' => $forms_language->get('forms', 'form_icon'),
                'FORM_ICON_VALUE' => (isset($_POST['form_icon']) ? Input::get('form_icon') : ''),
                'FORM_URL' => $forms_language->get('forms', 'form_url'),
                'FORM_URL_VALUE' => (isset($_POST['form_url']) ? Output::getClean(Input::get('form_url')) : ''),
                'FORM_LINK_LOCATION' => $forms_language->get('forms', 'link_location'),
                'LINK_LOCATION_VALUE' => (isset($_POST['link_location']) ? Output::getClean(Input::get('link_location')) : ''),
                'LINK_NAVBAR' => $language->get('admin', 'page_link_navbar'),
                'LINK_MORE' => $language->get('admin', 'page_link_more'),
                'LINK_FOOTER' => $language->get('admin', 'page_link_footer'),
                'LINK_NONE' => $language->get('admin', 'page_link_none'),
                'CONTENT' => $language->get('admin', 'description'),
                'CONTENT_VALUE' => (isset($_POST['content']) ? Output::getClean(Input::get('content')) : ''),
                'ENABLE_CAPTCHA' => $forms_language->get('forms', 'enable_captcha'),
                'ENABLE_CAPTCHA_VALUE' => (isset($_POST['captcha']) && $_POST['captcha'] == 'on' ? 1 : 0),
            ));
            
            $template->addCSSFiles(array(
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.css' => array(),
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/css/spoiler.css' => array()
            ));

            $template->addJSFiles(array(
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/switchery/switchery.min.js' => array(),
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
                (defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => array()
            ));

            $template->addJSScript(Input::createEditor('inputContent', true));
            $template->addJSScript('
                var elems = Array.prototype.slice.call(document.querySelectorAll(\'.js-switch\'));

                elems.forEach(function(html) {
                    var switchery = new Switchery(html, {color: \'#23923d\', secondaryColor: \'#e56464\'});
                });
            ');
            
            $template_file = 'forms/forms_new.tpl';
        break;
        case 'delete':
            // Delete Form
            if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
                Redirect::to(URL::build('/panel/forms'));
                die();
            }
            
            try {
                $queries->delete('forms', array('id', '=', $_GET['id']));
                $queries->delete('forms_permissions', array('form_id', '=', $_GET['id']));
                $queries->delete('forms_fields', array('form_id', '=', $_GET['id']));
                $queries->delete('forms_replies', array('form_id', '=', $_GET['id']));
                $queries->delete('forms_comments', array('form_id', '=', $_GET['id']));
            } catch(Exception $e){
                die($e->getMessage());
            }

            Session::flash('staff_forms', $forms_language->get('forms', 'form_deleted_successfully'));
            Redirect::to(URL::build('/panel/forms'));
            die();
        break;
        default:
            Redirect::to(URL::build('/panel/forms'));
            die();
        break;
    }
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $template);

if(Session::exists('staff_forms'))
    $success = Session::flash('staff_forms');

if(isset($success))
    $smarty->assign(array(
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ));

if(isset($errors) && count($errors))
    $smarty->assign(array(
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ));

$smarty->assign(array(
    'PARENT_PAGE' => PARENT_PAGE,
    'PAGE' => PANEL_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'INFO' => $language->get('general', 'info'),
    'FORMS' => $forms_language->get('forms', 'forms'),
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit')
));

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);