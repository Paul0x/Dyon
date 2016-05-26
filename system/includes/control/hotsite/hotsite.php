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
 *  File: hotsite.php
 *  Type: Generic Object
 *  =====================================================================
 * 
 */

class hotsite {
    /* Database Connection */

    private $conn;

    /* Hotsite Basic Structure */
    private $event_id;
    private $pages;
    private $event;
    private $status;
    private $last_change;

    /* Hotsite CSS Structure */
    private $text_color;
    private $text_font;
    private $title_color;
    private $title_font;
    private $background_color;
    private $background_image;

    /* Hotsite Pages Structure */
    private $gallery_status;
    private $contact_status;
    private $schedule_status;
    private $faq_status;
    private $blog_status;

    /**
     * Constrói o objeto e inicializa a conexão com o banco de dados.
     */
    public function __construct(conn $conn, $event_id = null) {
        $this->conn = $conn;

        if (!is_null($event_id)) {
            $this->loadHotsite($event_id);
        }
    }

    public function loadHotsite($event) {
        if (is_array($event)) {
            if (!is_numeric($event['id'])) {
                throw new Exception("O identificador do evento está em formato inválido.", 1);
            }
        } else {
            if (!is_numeric($event)) {
                throw new Exception("O identificador do evento está em formato inválido.", 2);
            }
            $eventcontroller = new eventController();
            $event = $eventcontroller->loadEvent($event);
        }
        $fields = array("id", "id_evento", "status", "info", "data_alteracao");
        $this->conn->prepareselect("hotsite", $fields, "id_evento", $event['id']);
        if (!$this->conn->executa()) {
            throw new Exception("Não foi possível executar a ação.", 3);
        }
        $hotsite_array = $this->conn->fetch;

        $variables = $this->getHotsiteVariables($hotsite_array);
    }

    private function getHotsiteVariables(Array $hotsite_array) {
        $variable_list = Array("text_color", "text_font", "title_color", "title_font", "background_image", "background_color", "gallery_status", "contact_status", "schedule_status", "faq_status", "blog_status");
 

        try {
            $info = unserialize($hotsite_array['info']);
        } catch (Exception $ex) {
            throw new Exception("Não foi possível pegar as informações do site.");
        }

        if (is_array($info)) {
            foreach ($info as $index => $variable) {
                if (in_array($index, $variable_list)) {
                    $function = "set" . ucfirst($index);
                    $this->$function($variable);
                }
            }
            return 2;
        } else {
            return 1;
        }
    }

}
