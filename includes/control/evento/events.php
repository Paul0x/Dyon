<?php/* * **************************************** *     _____                     *    |  __ \                    *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] *  ===================================================================== *  File: events.php *  Type: Controller *  ===================================================================== *  */require_once(config::$syspath . "includes/sql/sqlcon.php");require_once(config::$syspath . "includes/sql/pacotes.php");require_once(config::$syspath . "includes/sql/flow.php");require_once(config::$syspath . "includes/control/usuario/users.php");require_once(config::$syspath . "includes/control/financeiro/finances.php");require_once(config::$syspath . "includes/lib/Twig/Autoloader.php");require_once("lotes.php");define("DYON_EVENT_STATUS_OPEN", 2);define("DYON_EVENT_STATUS_ARCHIVED", 1);class eventController {    /**     * Constrói o objeto e inicializa a conexão com o banco de dados.     */    public function __construct() {        $this->conn = new conn();    }    public function setUser($user) {        if (!is_a($user, "user")) {            throw new Exception("Usuário Inválido.");        }        $this->user = $user;    }    /**     * Gera uma lista de eventos, provendo informações básicas.     * TODO: Utilizar o $search_query para fazer uma busca através de parâmetros.     * @param ARRAY $search_query     * @return ARRAY     * @throws Exception      */    public function listEvents($min_search = false, $user = null) {        $search_field = "";        $search_query = "";        if ($min_search == false) {            $fields = Array("id", "id_usuario", "nome", "data_inicio", "data_fim", "data_criacao", "status");            $instance = $this->user->getUserInstance();            $search_field[] = "id_instancia";            $search_query[] = $instance['id'];        } else {            if (!is_object($user)) {                throw new Exception("Usuário inválido.");            }            $instance = $user->getUserInstance();            $search_field[] = "id_instancia";            $search_query[] = $instance['id'];            $fields = Array("id", "nome");        }        try {            $this->conn->prepareselect("evento", $fields, $search_field, $search_query, "same", "", "", NULL, "all");            $this->conn->executa();            if ($this->conn->rowcount == 0) {                throw new Exception("Nenhum evento encontrado.");            }            $events = $this->conn->fetch;            if ($min_search == false) {                foreach ($events as $index => $event) {                    $datetime = new DateTime($event['data_inicio']);                    $events[$index]['data_inicio'] = $datetime->format("d/m/Y");                    if ($event['data_fim'] != "") {                        $datetime = new DateTime($event['data_fim']);                        $events[$index]['data_fim'] = $datetime->format("d/m/Y");                    }                }            }            return $events;        } catch (Exception $error) {            throw $error;        }    }    public function getEventByURL($url, $return_event = false) {        $url_regex = "/^[A-Za-z0-9]{1,100}$/";        if (!preg_match($url_regex, $url)) {            throw new Exception("A URL informada não está dentro dos padrões.");        }        $this->conn->prepareselect("evento", "id", array("url", "status"), array($url, DYON_EVENT_STATUS_OPEN));        if (!$this->conn->executa()) {            throw new Exception("Nenhum evento encontrado com a URL informada - 01");        }        if ($return_event) {            return $this->loadEvent($this->conn->fetch[0], false, true, true);                   } else {            return $this->conn->fetch[0];        }    }    /**     * Carrega e formata todas as informações pertinentes a um evento.     * @param INT $id     * @return Array     * @throws Exception     */    public function loadEvent($id, $status_required = false, $guest_user = false, $public = false) {        if (!is_numeric($id)) {            throw new Exception("Informações inválidas para captar evento.");        }        if (!$public) {            $usercontroller = new userController();            $user = $usercontroller->getUser();            if (!$guest_user) {                $this->checkEventInstance($id, $user);                $instance = $user->getUserInstance();            }        }        $fields = Array("id", "id_usuario", "nome", "data_inicio", "data_fim", "data_criacao", "max_venda", "status", "descricao", "local", "flag_grupos", "flag_compras", "flag_hospedagem", "url");        $fields_where = array("id");        $values_where = array($id);        if (!$guest_user || !$public) {            $fields_where[] = "id_instancia";            $values_where[] = $instance['id'];        }                try {            $this->conn->prepareselect("evento", $fields, $fields_where, $values_where);            $this->conn->executa();        } catch (Exception $error) {            throw new Exception("Evento não encontrado(0).");        }        if ($this->conn->rowcount == 0) {            throw new Exception("Evento não encontrado(1).");        }        $event = $this->conn->fetch;        if ($status_required && $event['status'] != $status_required) {            throw new Exception("O evento não está disponível.");        }        if ($event['descricao']) {            $event['descricao_br'] = nl2br($event['descricao']);        }        $datetime = new DateTime($event['data_inicio']);        $event['data_inicio_formatted'] = $datetime->format("d/m/Y à\s H:i");        $event['data_inicio_data'] = $datetime->format("d/m/Y");        $event['data_inicio_hora'] = $datetime->format("H:i");        if ($event['data_fim']) {            $datetime->modify($event['data_fim']);            $event['data_fim_formatted'] = $datetime->format("d/m/Y à\s H:i");            $event['data_fim_data'] = $datetime->format("d/m/Y");            $event['data_fim_hora'] = $datetime->format("H:i");        }        $datetime->modify($event['data_criacao']);        $event['data_criacao'] = $datetime->format("d/m/Y à\s H:i");        if (!$guest_user) {            $event['n_vendas'] = $this->countVendas($id, 2, $event["max_venda"]);            $event['n_vendas_genero'] = $this->countVendasByGenre($id, 2);            $event['total_arrecadado'] = $this->sumVendas($id);            $event['total_gasto'] = $this->sumCompras($id);            $event['lucro_estimado'] = "R$" . number_format($event['total_arrecadado'] - $event['total_gasto'], 2, ",", ".");            $event['n_casas'] = $this->countCasas($id);            $event['n_vagas'] = $this->countVagasCasas($id);            $event['criador'] = $this->getEventOwner($event['id_usuario']);        }        try {            $lotes = new loteController($this->conn);            $lotes->loadEventId($id);            $event['lista_lotes'] = $lotes->getEventLotes();            $event['lista_lotes_hierarchy'] = $lotes->getEventLotesByHierarchy();        } catch (Exception $error) {            $event['empty_lotes_flag'] = "Nenhum lote registrado.";        }        return $event;    }    /**     * Edita um evento e faz todas as verificações necessárias para tal ação.     * @param Array $event_old     * @return Array     * @throws Exception     */    private function editEvent($old_event, $new_event, $user) {        $new_event = $this->getFormattedDate($new_event);        $this->checkEventInstance($old_event['id'], $user);        foreach ($new_event as $index => $values) {            if ($values == NULL && !is_numeric($values)) {                throw new Exception("Valores inválidos para edição do evento.");            }        }        $editable_fields = Array("nome", "max_venda", "data_inicio", "data_fim", "local", "descricao", "flag_compras", "flag_grupos", "flag_hospedagem", "url");        foreach ($editable_fields as $field) {            if ($old_event[$field] != $new_event[$field]) {                if ($field == "flag_compras" || $field == "flag_grupos" || $field == "flag_hospedagem") {                    if (!is_numeric($new_event[$field]) || ($new_event[$field] !== 0 && $new_event[$field] !== 1)) {                        $new_event[$field] = 0;                    }                }                $update_fields[] = $field;                $update_values[] = $new_event[$field];                $old_event[$field] = $new_event[$field];                if ($field == "data_inicio" || $field == "data_fim") {                    $old_event[$field . "_data"] = $new_event[$field . "_data"];                    $old_event[$field . "_hora"] = $new_event[$field . "_hora"];                }            }        }        if (!is_array($update_fields)) {            return $old_event;        }        $this->conn->prepareupdate($update_values, $update_fields, "evento", $old_event["id"], "id");        if (!$this->conn->executa()) {            throw new Exception("Ocorreu um erro ao editar o evento.");        }        return $this->loadEvent($old_event['id']);    }    /**     * Pega os dados, verifica, e adiciona um evento.     * @param User Object $user     * @return INT     * @throws Exception     */    private function addEvent(user $user) {        $filters = Array(            "nome" => FILTER_SANITIZE_SPECIAL_CHARS,            "data_inicio_data" => FILTER_SANITIZE_SPECIAL_CHARS,            "data_inicio_hora" => FILTER_SANITIZE_SPECIAL_CHARS,            "data_fim_data" => FILTER_SANITIZE_SPECIAL_CHARS,            "data_fim_hora" => FILTER_SANITIZE_SPECIAL_CHARS,            "max_venda" => FILTER_VALIDATE_INT,            "local" => FILTER_SANITIZE_SPECIAL_CHARS,            "descricao" => FILTER_SANITIZE_SPECIAL_CHARS,            "flag_grupos" => FILTER_VALIDATE_INT,            "flag_compras" => FILTER_VALIDATE_INT,            "flag_hospedagem" => FILTER_VALIDATE_INT        );        $required_fields = array(            "nome",            "data_inicio_data",            "data_inicio_hora",            "flag_grupos",            "flag_compras",            "flag_hospedagem"        );        $optional_fields = array(            "data_fim",            "local",            "descricao"        );        $event = $this->getFormattedDate(filter_input_array(INPUT_POST, $filters));        foreach ($event as $index => $field) {            if (in_array($index, $required_fields) && is_null($field)) {                throw new Exception("Campos inválidos para adicionar o evento.");            }        }        if (!is_numeric($event['max_venda'])) {            $event['max_venda'] = 0;        }        $instance = $user->getUserInstance();        $event['url'] = "boemia";        $field_list = Array("id_instancia", "id_usuario", "nome", "data_inicio", "max_venda", "flag_hospedagem", "flag_compras", "flag_grupos", "url");        $event_to_add = Array($instance['id'], $user->getId(), $event['nome'], $event['data_inicio'], $event['max_venda'], $event['flag_hospedagem'], $event['flag_compras'], $event['flag_grupos'], $event['url']);        foreach ($optional_fields as $index => $field) {            if (!is_null($event[$field]) && $event[$field] != "") {                $field_list[] = $field;                $event_to_add[] = $event[$field];            }        }        $this->conn->prepareinsert("evento", $event_to_add, $field_list, "");        if (!$this->conn->executa()) {            throw new Exception("Ocorreu um erro ao adicionar o evento.");        }        try {            $user->getSelectedEvent();        } catch (Exception $ex) {            $event_id = $this->conn->pegarMax("evento") - 1;            $user->setSelectedEvent($event_id);        }        return $this->conn->pegarMax("evento") - 1;    }    private function archiveEvent() {        $filters = Array(            "id" => FILTER_VALIDATE_INT        );        $event = filter_input_array(INPUT_POST, $filters);        $usercontroller = new userController();        $user = $usercontroller->getUser(DYON_USER_ADMIN);        $this->checkEventInstance($event, $user);        if (!is_numeric($event["id"])) {            throw new Exception("O identificador do evento é inválido.");        }        $this->conn->prepareselect("evento", "status", "id", $event['id']);        if (!$this->conn->executa()) {            throw new Exception("Não foi possível acessar o evento.");        }        if ($this->conn->fetch['status'] == 1) {            $new_status = 2;        } else if ($this->conn->fetch['status'] == 2) {            $new_status = 1;        } else {            throw new Exception("Impossível alterar o status do evento.");        }        $this->conn->prepareupdate($new_status, "status", "evento", $event["id"], "id");        if (!$this->conn->executa()) {            throw new Exception("Não foi possível arquivar o evento selecionado.");        }        return $new_status;    }    /**     * Formata as datas do evento, para serem adicionadas ao banco de dados.     * @param Array $event     * @return Array     * @throws Exception     */    private function getFormattedDate($event) {        if (!$datetime_inicio = DateTime::createFromFormat("d/m/Y H:i", $event['data_inicio_data'] . " " . $event['data_inicio_hora'])) {            throw new Exception("A data de início é inválida.");        }        if (!is_null($event['data_fim_data']) && $event['data_fim_data'] != "") {            if (!$datetime_fim = DateTime::createFromFormat("d/m/Y H:i", $event['data_fim_data'] . " " . $event['data_fim_hora'])) {                throw new Exception("A data de encerramento é inválida.");            }            if ($datetime_inicio->getTimestamp() > $datetime_fim->getTimeStamp()) {                throw new Exception("A data de início não pode ser posterior a data final.");            }            $event['data_fim'] = $datetime_fim->format("Y-m-d H:i:s");        }        $event['data_inicio'] = $datetime_inicio->format("Y-m-d H:i:s");        return $event;    }    /**     * Retorna o nome do usuário, através do ID fornecido.     * @param INT $id     * @return String     * @throws Exception     */    private function getEventOwner($id) {        if (!is_numeric($id)) {            throw new Exception("Informações inválidas para captar evento.");        }        try {            $this->conn->prepareselect("usuario", "nome", "id", $id);            $this->conn->executa();        } catch (Exception $error) {            throw new Exception("Falha ao captar criador do evento.");        }        return $this->conn->fetch[0];    }    /**     * Retorna o número de pacotes vendidos para um evento, de acordo com o status da transação.     * @param INT $id     * @param INT $status     * @return INT     * @throws Exception     */    private function countVendas($id, $status = 2, $max = 0) {        if (!is_numeric($id) || !is_numeric($status)) {            throw new Exception("Informações inválidas para captar número de vendas.");        }        try {            $pacotemodel = new pacoteModel($this->conn);            $count = $pacotemodel->countPacotes($id, $status);            $return_string = $count;            return $return_string;        } catch (Exception $error) {            throw new Exception("Não foi possível contabilizar o número de vendas do evento.");        }    }    /**     * Retorna o número de vendas por gênero     * @param INT $id     * @param INT $status     * @return INT     * @throws Exception     */    private function countVendasByGenre($id, $status = 2) {        if (!is_numeric($id) || !is_numeric($status)) {            throw new Exception("Informações inválidas para captar número de vendas.");        }        try {            $query = "SELECT count(DISTINCT a.id) FROM pacote a INNER JOIN lote b ON a.id_lote = b.id WHERE b.id_evento = $id AND a.status >= $status GROUP BY b.genero";            $pacotes = $this->conn->freeQuery($query, true);            if (!is_numeric($pacotes[0][0])) {                $pacotes[0][0] = 0;            }            if (!is_numeric($pacotes[1][0])) {                $pacotes[1][0] = 0;            }            return $pacotes;        } catch (Exception $error) {            throw new Exception("Não foi possível contabilizar o número de vendas do evento.");        }    }    /**     * Retorna o número de vendas por data     * @param INT $id     * @param INT $status     * @param bool $day     * @param bool $week     * @return Array     * @throws Exception     */    private function countVendasByDate($id, $status = 2, $day = true, $week = false) {        if (!is_numeric($id) || !is_numeric($status)) {            throw new Exception("Informações inválidas para captar número de vendas.");        }        try {            $pacotemodel = new pacoteModel($this->conn);            if ($day) {                $datetime_start = new DateTime();                $datetime_start->modify("-24 hours");                $datetime_end = new DateTime();                $datequery = Array(                    "field" => "data_aprovacao",                    "datetime_start" => $datetime_start,                    "datetime_end" => $datetime_end                );                $pacotes['day'] = $pacotemodel->countPacotes($id, $status, $datequery);            }            if ($week) {                $datetime_start = new DateTime();                $datetime_start->modify("-7 days");                $datetime_end = new DateTime();                $datequery = Array(                    "field" => "data_aprovacao",                    "datetime_start" => $datetime_start,                    "datetime_end" => $datetime_end                );                $pacotes['week'] = $pacotemodel->countPacotes($id, $status, $datequery);            }            return $pacotes;        } catch (Exception $ex) {            throw new Exception("Não foi possível contabilizar o número de vendas do evento [ date ].");        }    }    /**     * Soma o valor todas as vendas de um evento.     * @param INT $id     * @return FLOAT     * @throws Exception     */    private function sumVendas($id) {        if (!is_numeric($id)) {            throw new Exception("Informações inválidas para captar o valor total das vendas.");        }        try {            $this->conn->prepareselect("pacote a", "a.valor", "id_evento", $id, "same", "sum", array("INNER", "lote b"));            $this->conn->executa();            return "R$" . number_format($this->conn->fetch[0], 2, ",", ".");        } catch (Exception $error) {            throw new Exception("Não foi possível contabilizar o valor das vendas do evento.");        }    }    /**     * Soma o valor de todas as compras de um evento.     * @param INT $id     * @param INT $status     * @return FLOAT     * @throws Exception     */    private function sumCompras($id, $status = 2) {        if (!is_numeric($id)) {            throw new Exception("Informações inválidas para captar o valor total das compras.");        }        try {            $this->conn->prepareselect("compra a", "a.valor_unitario", "id_evento", $id, "same", "sum", array("INNER", "compra b"));            $this->conn->executa();            return "R$" . number_format($this->conn->fetch[0], 2, ",", ".");        } catch (Exception $error) {            throw new Exception("Não foi possível contabilizar o valor das compras do evento.");        }    }    /**     * Conta o número de casas disponíveis para um evento.     * @param INT $id     * @return INT     * @throws Exception     */    private function countCasas($id) {        if (!is_numeric($id)) {            throw new Exception("Informações inválidas para captar o número de casas de apoio.");        }        try {            $this->conn->prepareselect("casa", "id", "id_evento", $id, "same", "count");            $this->conn->executa();            return $this->conn->fetch[0];        } catch (Exception $error) {            throw new Exception("Não foi possível contabilizar o número de casas do evento.");        }    }    /**     * Conta o número de vagas disponíveis nas casas do evento.     * @param INT $id     * @return int     * @throws Exception     */    private function countVagasCasas($id) {        if (!is_numeric($id)) {            throw new Exception("Informações inválidas para captar o número de vagas nas casas.");        }        try {            $this->conn->prepareselect("casa", "numero_vagas", "id_evento", $id, "same", "sum");            if (!$this->conn->executa()) {                throw new Exception("Não foi possível contabilizar o número de vagas nas casas do evento.");            }            if (!is_null($this->conn->fetch[0]))                return $this->conn->fetch[0];            else                return 0;        } catch (Exception $error) {            throw new Exception("Não foi possível contabilizar o número de vagas nas casas do evento.");        }    }    public function loadEventHeader($event_id) {        $this->conn->prepareselect("evento", "nome", "id", $event_id);        if (!$this->conn->executa()) {            throw new Exception("Evento não encontrado.");        }        $event['name'] = $this->conn->fetch[0];        $event['id'] = $event_id;        return $event;    }    /**     * Inicializa a classe de controle, quando chamada pela interface via browser.     * @param Array $url     */    public function init($url) {        Twig_Autoloader::register();        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/manager');        $this->twig = new Twig_Environment($this->twig_loader);        $this->usercontroller = new userController();        if (!$this->usercontroller->authUser()) {            header("location: " . HTTP_ROOT);        } else {            try {                $this->user = $this->usercontroller->getUser(DYON_USER_ADMIN);            } catch (Exception $error) {                echo $this->twig->render("evento/load_event.twig", Array("event_error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload));            }            switch ($url[1]) {                case 'ajax':                    switch ($_POST['mode']) {                        case 'get_event_lotes':                            $this->getEventLotes();                            break;                        case "get_dashboard_event":                            $this->getDashBoardEvent();                            break;                        case "load_event_overview_edit":                            $this->loadEventOverviewForm();                            break;                        case "update_event_overview":                            $this->updateEventOverview();                            break;                    }                    break;                default:                    switch ($url[1]) {                        case "create":                            $this->interfaceAddEvent($this->user);                            break;                        default:                            if ($url[2] == "lote") {                                $this->loteController = new loteController($this->conn);                                $this->loteController->init($url);                            } else {                                $this->interfaceLoadEvent($this->user, $url[1]);                            }                            break;                    }                    break;            }        }    }    private function interfaceLoadEvent($user, $event_id) {        $selected_event = $user->getSelectedEvent();        if ($selected_event != $event_id && is_numeric($event_id)) {            $event = $this->loadEvent($event_id);            $user->setSelectedEvent($event_id);        } else {            $event = $this->loadEvent($selected_event);        }        $events_list = $this->listEvents(true, $user);        echo $this->twig->render("evento/load_event.twig", Array("event" => $event, "events_list" => $events_list, "config" => config::$html_preload, "user" => $user->getBasicInfo()));    }    /**     * Chama e gerencia a interface para adicionar um evento.     */    private function interfaceAddEvent() {        try {            $usercontroller = new userController();            $user = $usercontroller->getUser(DYON_USER_ADMIN);            try {                if (isset($_POST['submit'])) {                    $id_evento = $this->addEvent($user);                    header("location: " . config::$html_preload['system_path'] . "/manager/eventos/" . $id_evento);                }            } catch (Exception $error) {                $error_edit_flag = $error->getMessage();            }            echo $this->twig->render("evento/add_event.twig", Array("error_edit_flag" => $error_edit_flag, "config" => config::$html_preload, "user" => $user->getBasicInfo()));        } catch (Exception $error) {            echo $this->twig->render("evento/load_event.twig", Array("event_error_flag" => true, "error" => $error->getMessage(), "config" => config::$html_preload, "user" => $user->getBasicInfo()));        }    }    private function interfaceArchiveEvent($url) {        try {            $usercontroller = new userController();            $user = $usercontroller->getUser(DYON_USER_ADMIN);            if (!$url['ajax']) {                throw new Exception("Requisição inválida.");            }            $status = $this->archiveEvent();            echo json_encode(Array("success" => "true", "status" => $status));        } catch (Exception $error) {            echo json_encode(Array("success" => "false", "error" => $error->getMessage()));        }    }    private function getEventLotes() {        try {            $usercontroller = new userController();            $user = $usercontroller->getUser(DYON_USER_ADMIN);            $lotecontroller = new loteController($this->conn);            $event = filter_input(INPUT_POST, "event", FILTER_VALIDATE_INT);            $lotecontroller->loadEventId($event);            $lotes = $lotecontroller->getEventLotesByHierarchy();            echo json_encode(Array("success" => "true", "lotes" => $lotes));        } catch (Exception $error) {            echo json_encode(Array("success" => "false", "error" => $error->getMessage()));        }    }    private function checkEventInstance($event_id, user $user, $force_db_check = false) {        try {            if (!is_numeric($event_id)) {                throw new Exception("Identificador do evento em formato inválido.");            }            $instance = $user->getUserInstance();            $this->conn->prepareselect("evento", "id_instancia", array("id", "id_instancia"), array($event_id, $instance['id']));            if (!$this->conn->executa()) {                throw new Exception("Evento inválido para a instância selecionada.");            }            if ($force_db_check) {                $user->refreshUserInstance();            }            return true;        } catch (Exception $ex) {            return false;        }    }    private function getDashBoardEvent() {        try {            $financecontroller = new financesController();            $pacotecontroller = new pacoteController($this->conn);            $event_id = $this->user->getSelectedEvent();            $event = $this->loadEvent($event_id);            $event['vendas_date'] = $this->countVendasByDate($event['id'], 2, true, true);            $event['finances_date'] = $financecontroller->getReceitaByDate($event['id'], true, true, true);            try {                $pacotes_aprovacao = $pacotecontroller->listPacotesByParcela(array("id_evento" => $event['id']));                $event['pacotes_aprovacao'] = $pacotes_aprovacao['count']['query'];            } catch (Exception $ex) {                $event['pacotes_aprovacao'] = 0;            }            $html = $this->twig->render("evento/dashboard.twig", Array("event" => $event, "config" => config::$html_preload, "user" => $this->user->getBasicInfo()));            echo json_encode(array("success" => "true", "event" => $event, "html" => $html));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    private function loadEventOverviewForm() {        try {            $event_id = filter_input(INPUT_POST, "event_id");            $event = $this->loadEvent($event_id);            $html = $this->twig->render("evento/edit_event_overview.twig", Array("event" => $event, "config" => config::$html_preload, "user" => $this->user->getBasicInfo()));            echo json_encode(array("success" => "true", "event" => $event, "html" => $html));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }    private function updateEventOverview() {        try {            $event_id = filter_input(INPUT_POST, "event_id");            $overview_info = filter_input(INPUT_POST, "overview_info", FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);            $event = $this->editEvent($this->loadEvent($event_id), $overview_info, $this->user);            $html = $this->twig->render("evento/event_overview.twig", Array("event" => $event, "config" => config::$html_preload));            echo json_encode(array("success" => "true", "event" => $event, "html" => $html));        } catch (Exception $ex) {            echo json_encode(array("success" => "false", "error" => $ex->getMessage()));        }    }}/** * Método para inicializar a classe de evento, chamada pelo sistema. * @param Array $url */function init_module_events($url) {    $eventcontroller = new eventController();    $eventcontroller->init($url);}