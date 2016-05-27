<?php

/* * ****************************************
 *     _____                    
 *    |  __ \                   
 *    | |  | |_   _  ___  _ __  
 *    | |  | | | | |/ _ \| '_ \ 
 *    | |__| | |_| | (_) | | | |
 *    |_____/ \__, |\___/|_| |_|
 *             __/ |            
 *            |___/  
 *           
 *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ]
 *  =====================================================================
 *  File: control.php
 *  Type: Controller
 *  =====================================================================
 * 
 */

require_once("includes/sql/sqlcon.php");
require_once("includes/control/evento/events.php");
require_once("includes/control/usuario/users.php");
require_once("includes/lib/Twig/Autoloader.php");
require_once("includes/control/hotsite/hotsite.php");

class hotsiteAdminController {

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct() {
        $this->conn = new conn();
    }

    /**
     * Inicializa a classe de controle, quando chamada pela interface via browser.
     * @param Array $url
     */
    public function init($url) {
        Twig_Autoloader::register();
        try {
            $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates');
            $this->twig = new Twig_Environment($this->twig_loader);

            $this->usercontroller = new userController();
            if (!$this->usercontroller->authUser()) {
                header("location: " . HTTP_ROOT);
            }
            $user = $this->usercontroller->getUser();

            switch ($url[1]) {
                case "ajax":
                    $this->ajaxLoad($user);
                    break;
                default:
                    $this->loadHotsiteAdministrativePage($user);
                    break;
            }
        } catch (Exception $ex) {
            echo $this->twig->render("hotsite/main.twig", Array("error_flag" => $error_flag, "evento" => $evento, "config" => config::$html_preload, "events_select" => $events_select, "user" => $user->getBasicInfo()));
        }
    }

    private function loadHotsiteAdministrativePage($user) {
        try {
            if (isset($_SESSION['hotsitecache'])) {
                unset($_SESSION['hotsitecache']);
            }
            $selectedevent = $user->getSelectedEvent();
            $eventcontroller = new eventController();
            $evento = $eventcontroller->loadEvent($selectedevent);
            $events_select = $eventcontroller->listEvents(true, $user);
            $_SESSION['hotsitecache'] = serialize(new hotsite($this->conn, $evento));
            echo $this->twig->render("hotsite/main.twig", Array("evento" => $evento, "config" => config::$html_preload, "events_select" => $events_select, "user" => $user->getBasicInfo()));
        } catch (Exception $error) {
            $error_flag = $error->getMessage();
            echo $this->twig->render("hotsite/main.twig", Array("error_flag" => $error_flag, "evento" => $evento, "config" => config::$html_preload, "events_select" => $events_select, "user" => $user->getBasicInfo()));
        }
    }

    private function ajaxLoad($user) {
        $mode = filter_input(INPUT_POST, "mode", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        switch ($mode) {
            case "get_hotsite_interface":
                $this->loadHotsiteInterface();
                break;
            case "load_hotsite_config_interface":
                $this->loadHotsiteConfigInterface("html");
                break;
        }
    }

    private function loadHotsiteInterface() {
        $interface_modules = Array();
        $interface_modules['topmenu'] = $this->twig->render("hotsite/topmenu.twig", Array("config" => config::$html_preload));
        $interface_modules['leftmenu'] = $this->twig->render("hotsite/leftmenu.twig", Array("config" => config::$html_preload));
        echo json_encode(array("success" => "true", "modules" => $interface_modules));
    }

    private function loadHotsiteConfigInterface($output) {
        try {
            $hotsite = unserialize($_SESSION['hotsitecache']);
            if(!is_object($hotsite)) {
                throw new Exception("Não foi possível carregar o hotsite.");
            }
            
            $hotsite_config = $hotsite->getHTMLConfigVariables("array");
            echo json_encode(array("success" => "true", "hotsite_config" => $hotsite_config, "html" => $this->twig->render("hotsite/ajax_config_interface.twig", Array("config" => config::$html_preload ))));
        } catch (Exception $ex) {
            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));            
        }
    }
}

    /**
     * Método para inicializar a classe de controle, chamada pelo sistema.
     * @param Array $url
     */
    function init_module_hotsite($url) {
        $eventcontroller = new hotsiteAdminController();
        $eventcontroller->init($url);
    }
    