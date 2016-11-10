<?php/* * **************************************** *     _____                     *    |  __ \                   *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] * ===================================================================== * Arquivo de configuração dos parâmetros do Dyon. * Nesse arquivo você poderá customizar grande parte dos parâmetros utilizados pelo sistema, assim como deletar e adicionar módulos. */class config {    public static $html_preload = Array(        "title" => "Dyon", // Define o título do sistema        "system_path" => HTTP_ROOT,        "domain_path" => "/dyon",        "css" => Array("core","font-awesome.min" // Define os estilos disponíveis        ),        "javascript" => Array("jquery", "jquery-ui", "mask", "maskmoney", "jscolor.min", "jquery.tooltipster.min", "https://www.gstatic.com/charts/loader.js", "core", "functions", "comments" // Define os scripts disponíveis.        ),    );   /* // Define os parâmetros da base de dados     public static $db = Array(      "host" => "dyonweb.mysql.dbaas.com.br",      "database" => "dyonweb",      "user" => "dyonweb",      "password" => "btichanu16"      ); */    public static $db = Array(        "host" => "localhost",        "database" => "bdyon",        "user" => "admin",        "password" => ""    );    public static $nodes_ids = Array(1, 2, 3, 4, 5, 6, 7, 8);    public static $nodes = Array(        "1" => array(            "table" => "usuario"        ),        "2" => array(            "table" => "evento"        ),        "3" => array(            "table" => "pacote"        ),        "4" => array(            "table" => "parcela"        ),        "5" => array(            "table" => "compra"        ),        "6" => array(            "table" => "thread"        ),        "7" => array(            "table" => "grupo"        ),        "8" => array(            "table" => "casa"        ),        "9" => array(            "table" => "hotsite"        )    );    /*     *  Lista de módulos ativos     */    public static $modules = Array("list" => Array(            "index", "usuario", "eventos", "controle", "cliente", "financeiro", "api", "comentarios", "boards", "notificacao", "hospedagem", "interface"        ),        "index" => Array(            "system_path" => "includes/control/main.php",            "init_func" => "init_module_index",            "javascript" => Array("board","dashboard")        ),        "boards" => Array(            "system_path" => "includes/control/board/board.php",            "init_func" => "init_module_board",            "javascript" => array("board","dashboard")        ),        "usuario" => Array(            "system_path" => "includes/control/usuario/users.php",            "init_func" => "init_module_users"        ),        "eventos" => Array(            "system_path" => "includes/control/evento/events.php",            "init_func" => "init_module_events",            "javascript" => "event"        ),        "controle" => Array(            "system_path" => "includes/control/controle/control.php",            "init_func" => "init_module_control",            "javascript" => "control"        ),        "cliente" => Array(            "system_path" => "includes/control/controle/control.php",            "init_func" => "init_module_control",            "javascript" => "control"        ),        "financeiro" => Array(            "system_path" => "includes/control/financeiro/finances.php",            "init_func" => "init_module_finances",            "javascript" => "finances"        ),        "api" => Array(            "system_path" => "includes/control/api/api.php",            "init_func" => "init_module_api"        ),        "comentarios" => Array(            "system_path" => "includes/control/comentario/comments.php",            "init_func" => "init_module_comments"        ),        "notificacao" => Array(            "system_path" => "includes/control/notification/notification.php",            "init_func" => "init_module_notification"        ),        "hospedagem" => Array(            "system_path" => "includes/control/casa/house.php",            "init_func" => "init_module_house",            "javascript" => "house"        ),        "interface" => Array(            "system_path" => "includes/control/hotsite/hotsites.php",            "init_func" => "init_module_hotsite",            "javascript" => array("hotsite","dragula.min"),            "css" => array("dragula.min","hotsite-content")        )    );            public static $syspath = "";}//Aplica variáveisif (dirname($_SERVER["PHP_SELF"]) == DIRECTORY_SEPARATOR) {    define(HTTP_ROOT, "");} else {    define(HTTP_ROOT, dirname($_SERVER["PHP_SELF"]));}date_default_timezone_set("America/Sao_Paulo");?>