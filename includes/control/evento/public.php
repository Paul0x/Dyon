<?php/* * **************************************** *     _____                     *    |  __ \                    *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] *  ===================================================================== *  File: public.php *  Type: Controller *  ===================================================================== *  */require_once(config::$syspath . "includes/control/evento/events.php");require_once(config::$syspath . "includes/control/hotsite/hotsites.php");require_once("lotes.php");class eventPublicController {    /**     * Constrói o objeto e inicializa a conexão com o banco de dados.     */    public function __construct() {        $this->conn = new conn();    }    public function init($url) {        if ($url[1] != "ajax") {            $this->eventcontroller = new eventController();            $event = $this->eventcontroller->getEventByURL($url[1], true);            $this->renderEvent($event);        } else {            $this->initAjaxLoader();        }    }    private function initAjaxLoader() {        try {            $this->eventcontroller = new eventController();            $event_id = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);            $event = $this->eventcontroller->loadEvent($event_id, false, true, true);            $usercontroller = new userController();            try {                $user = $usercontroller->getUser();            } catch (Exception $ex) {                $user = false;            }            Twig_Autoloader::register();            $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/public');            $this->twig = new Twig_Environment($this->twig_loader);            $mode = filter_input(INPUT_POST, "mode", FILTER_SANITIZE_STRING);            $manager_modes = array(                "load_manager_bottom_bar" => "loadManagerBottomBar",                "load_manager_form" => "loadManagerForm",                "submit_edit_settings" => "submitEditSettings",                "submit_edit_appearance" => "submitEditAppearance",                "load_hotsite_description" => "loadHotsiteDescription",                "submit_edit_description" => "submitEditDescription"            );            $public_modes = array(                "load_lot_selection_form" => "loadLotSelectionForm"            );            if (in_array($mode, array_keys($manager_modes))) {                $this->initManagerAjax($manager_modes[$mode], $event, $user);            } else if (in_array($mode, array_keys($public_modes))) {                $this->initPublicAjax($public_modes[$mode], $event, $user);            } else {                throw new Exception("Requisição Inválida");            }        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    private function initPublicAjax($mode, $event, $user) {                $this->$mode($event, $user);    }    private function initManagerAjax($mode, $event, $user) {        if (!is_a($user, "user") || !$user) {            throw new Exception("Usuário inválido");        }        $this->hotsite = new hotsiteController($event['id']);        if (!$this->eventcontroller->userHasEditPermission($user->getId(), $event['id'])) {            throw new Exception("Usuário não possui permissão de edição.");        }        $this->$mode($event, $user);    }        private function loadLotSelectionForm($event, $user) {        if(!is_numeric($event['id'])) {            throw new Exception("Identificador do evento inválido.");        }                switch($event['tipo']) {            case "p":                $html = $this->loadLotSelectionPaidEvent($event);                break;            case "g":                $html = $this->loadLotSelectionFreeEvent();                break;            case "m":                $html = $this->loadLotSelectionMixedEvent();                break;        }                        echo json_encode(array("success" => "true", "html" => $html));    }        private function loadLotSelectionPaidEvent($event) {        $open_lots = Array();        foreach($event['lista_lotes'] as $index => $lot) {            if($lot['status'] == 2) {                $open_lots[] = $lot;            }                    }                if(count($open_lots) == 0) {            $open_lots = false;        }                           return $this->twig->render("evento/lots/paid_lot_form.twig", Array("event" => $event, "open_lots" => $open_lots, "config" => config::$html_preload));    }    private function loadHotsiteDescription() {        try {            $description = $this->hotsite->getDescription("textarea");            echo json_encode(array("success" => "true", "description" => $description));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    private function submitEditDescription($event, $user) {        try {            $description = filter_input(INPUT_POST, "description", FILTER_UNSAFE_RAW);            $this->hotsite->setDescription($description);            $hotsite_html = $this->renderEvent($event, true, true);            echo json_encode(array("success" => "true", "html" => $hotsite_html));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    private function submitEditSettings($event, $user) {        try {            $hotsite_fields_bool = array("show_schedule", "show_gallery", "show_contacts", "show_likes", "show_sold", "published");            $hotsite_fields_str = array("button_name", "contact_phone", "contact_email", "contact_address");            $fields_array = array();            foreach ($hotsite_fields_str as $index => $field) {                $fields_array[$field] = filter_input(INPUT_POST, $field, FILTER_SANITIZE_FULL_SPECIAL_CHARS);            }            foreach ($hotsite_fields_bool as $index => $field) {                $fields_array[$field] = filter_input(INPUT_POST, $field, FILTER_VALIDATE_INT);            }            $this->hotsite->updateSettings($fields_array);            $hotsite_html = $this->renderEvent($event, true, true);            echo json_encode(array("success" => "true", "html" => $hotsite_html));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    private function submitEditAppearance($event, $user) {        try {            $hotsite_fields_str = array("background_color", "title_color", "date_color", "teaser");            $hotsite_fields_image = array("image_banner");            $fields_array = array();            foreach ($hotsite_fields_str as $index => $field) {                $fields_array[$field] = filter_input(INPUT_POST, $field, FILTER_SANITIZE_FULL_SPECIAL_CHARS);            }            foreach ($hotsite_fields_image as $index => $field) {                $fields_array[$field]['file'] = $_FILES[$field . "_file"];                $fields_array[$field]['extension'] = filter_input(INPUT_POST, $field . "_extension", FILTER_SANITIZE_FULL_SPECIAL_CHARS);                $fields_array[$field]['filename'] = filter_input(INPUT_POST, $field . "_filename", FILTER_SANITIZE_FULL_SPECIAL_CHARS);            }            $this->hotsite->updateAppearance($fields_array);            $hotsite_html = $this->renderEvent($event, true, true);            echo json_encode(array("success" => "true", "html" => $hotsite_html));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    private function loadManagerForm($event, $user) {        try {            $form = filter_input(INPUT_POST, "form", FILTER_SANITIZE_FULL_SPECIAL_CHARS);            switch ($form) {                case "settings":                    $html = $this->twig->render("evento/manager_settings_form.twig", Array("event" => $event, "hotsite" => $this->hotsite->getFields(), "config" => config::$html_preload));                    break;                case "appearance":                    $html = $this->twig->render("evento/manager_appearance_form.twig", Array("event" => $event, "hotsite" => $this->hotsite->getFields(), "config" => config::$html_preload));                    break;            }            echo json_encode(array("success" => "true", "html" => $html));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    private function renderEvent($event, $event_only = false, $no_js = false) {        $usercontroller = new userController();        try {            $user = $usercontroller->getUser();            $user_info = $user->getBasicInfo();            if ($this->eventcontroller->userHasEditPermission($user->getId(), $event['id'])) {                $edit_flag = true;                setup::addJavascript("public/public_event_manager");                setup::addJavascript("lib/tinymce/tinymce.min");            } else {                $edit_flag = false;            }        } catch (Exception $ex) {            $edit_flag = false;        }        $hotsitecontroller = new hotsiteController();        $hotsitecontroller->loadHotsiteByEventId($event['id']);        $hotsite = $hotsitecontroller->getFields();        Twig_Autoloader::register();        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/public');        $this->twig = new Twig_Environment($this->twig_loader);        if (!$event_only) {            echo $this->twig->render("evento/public_event.twig", Array("event" => $event, "hotsite" => $hotsite, "edit_flag" => $edit_flag, "user" => $user_info, "config" => config::$html_preload, "event_interface_flag" => true));        } else {            return $this->twig->render("evento/public_event_template.twig", Array("event" => $event, "hotsite" => $hotsite, "edit_flag" => $edit_flag, "user" => $user_info, "config" => config::$html_preload, "event_interface_flag" => true, "no_js_flag" => $no_js));        }    }    private function loadManagerBottomBar($event, $user) {        try {            $html = $this->twig->render("evento/manager_bottom_bar.twig", Array("event" => $event, "config" => config::$html_preload));            echo json_encode(array("success" => "true", "html" => $html));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }}/** * Método para inicializar a classe de evento, chamada pelo sistema. * @param Array $url */function init_module_public_events($url) {    $controller = new eventPublicController();    $controller->init($url);}