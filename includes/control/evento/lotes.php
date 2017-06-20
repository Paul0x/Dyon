<?php/* * **************************************** *     _____                     *    |  __ \                    *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] *  ===================================================================== *  File: lote.php *  Type: Controller *  ===================================================================== *   */define("DYON_LOTE_STATUS_OPEN", 2);define("DYON_LOTE_STATUS_CLOSED", 1);define("DYON_LOTE_STATUS_HEADER", 3);class loteController {    private $event_id;    private $conn;    public function __construct($conn) {        $this->conn = $conn;    }    /**     * Carrega um id numérico para ser atríbuido aos lotes.     * @param int $id     * @throws Exception     */    public function loadEventId($id) {        if (!is_numeric($id)) {            throw new Exception("O identificador do evento precisa ser numérico.");        }        $this->conn->prepareselect("evento", "id", "id", $id);        if (!$this->conn->executa() || $this->conn->rowcount != 1) {            throw new Exception("Evento não existente.");        }        $this->event_id = $id;    }    /**     * Pega todos os lotes referentes à um evento.     * @param bool $simple     * @return Array     * @throws Exception     */    public function getEventLotes($simple = true) {        if (!isset($this->event_id)) {            throw new Exception("Insira um identificador de evento antes de realizar essa ação.");        }        try {            $userController = new userController();            $userController->getUser();            if ($simple == false) {                $fields = Array("id", "nome", "valor", "data_criacao", "max_venda", "status", "genero", "id_parent");            } else {                $fields = Array("id", "nome", "valor", "status", "max_venda", "genero", "id_parent");            }            $this->conn->prepareselect("lote", $fields, "id_evento", $this->event_id, "same", "", "", PDO::FETCH_ASSOC, "all");            if (!$this->conn->executa()) {                throw new Exception("Não foi possível captar os lotes referentes ao evento.");            }            if ($this->conn->rowcount == 0) {                throw new Exception("Nenhum lote registrado.");            }            $lotes = $this->conn->fetch;            foreach ($lotes as $index => $lote) {                $lotes[$index] = $this->formatLote($lote);            }            return $lotes;        } catch (Exception $error) {            throw new Exception($error->getMessage());        }    }    /**     * Pega todos os lotes referentes à um evento, utilizando a hierarquia.     * @param bool $simple     * @return Array     * @throws Exception     */    public function getEventLotesByHierarchy($lotes = null) {        if (!isset($this->event_id)) {            throw new Exception("Insira um identificador de evento antes de realizar essa ação.");        }        try {            $userController = new userController();            $userController->getUser();            $fields = Array("id", "nome", "valor", "status", "max_venda", "genero", "id_parent");            if (is_null($lotes)) {                if (!is_numeric($this->event_id)) {                    throw new Exception("Identificador do evento inválido.");                }                $query = "SELECT id, nome, valor, status, max_venda, genero, id_parent FROM lote WHERE id_evento = " . $this->event_id . " AND id_parent is NULL";                $lotes = $this->conn->freeQuery($query, true, true, PDO::FETCH_ASSOC);                                if (is_array($lotes)) {                    foreach($lotes as $index => $lote) {                        $lotes[$index] = $this->formatLote($lote);                    }                    $lotes = $this->getEventLotesByHierarchy($lotes);                    return $lotes;                } else {                    throw new Exception("Nenhum lote encontrado.");                }            }            foreach ($lotes as $index => $lote) {                $this->conn->prepareselect("lote", $fields, array("id_evento", "id_parent"), array($this->event_id, $lote['id']), "same", "", "", PDO::FETCH_ASSOC, "all");                if (!$this->conn->executa()) {                    continue;                }                if ($this->conn->rowcount == 0) {                    continue;                }                $lotes_fetched = $this->conn->fetch;                if (is_array($lotes_fetched)) {                    foreach ($lotes_fetched as $idx => $lote) {                        $lotes_fetched[$idx] = $this->formatLote($lote);                    }                    $lotes[$index]['lotes'] = $this->getEventLotesByHierarchy($lotes_fetched);                    foreach($lotes[$index]['lotes'] as $idx2 => $lt) {                        $lotes[$index]['vendidos'] += $lt['vendidos'];                    }                }            }            return $lotes;        } catch (Exception $error) {            throw new Exception($error->getMessage());        }    }    private function formatLote($lote) {        if ($lote['status'] == 1) {            $lote['status_string'] = "Fechado";        } else if ($lote['status'] == 2) {            $lote['status_string'] = "Aberto";        }        $lote['valor'] = "R$" . number_format($lote['valor'], 2, ',', '.');        if ($lote['max_venda'] == 0) {            $lote['max_venda'] = "Ilimitado";        }        $this->conn->prepareselect("pacote", "id", array("id_lote", "status IN (2,3,4)"), array($lote['id']), array("="), "count");        if (!$this->conn->executa() || $this->conn->fetch[0] == NULL) {            $lote['vendidos'] = 0;        } else {            $lote['vendidos'] = $this->conn->fetch[0];        }        return $lote;    }    public function getLoteById($lote_id) {        if (!is_numeric($lote_id)) {            throw new Exception("Identificador do lote inválido.");        }        $fields = Array("id", "id_evento", "nome", "valor", "status", "max_venda", "genero", "id_parent");        $this->conn->prepareselect("lote", $fields, "id", $lote_id);        if (!$this->conn->executa() || $this->conn->rowcount != 1) {            throw new Exception("Nenhum lote encontrado com esse identificador.");        }        $lote = $this->conn->fetch;        if ($lote['status'] == 1) {            $lote['status_string'] = "Fechado";        } else if ($lote['status'] == 2) {            $lote['status_string'] = "Aberto";        }        $lote['valor_str'] = "R$" . number_format($lote['valor'], 2, ',', '.');        return $lote;    }    private function addLote($user) {        if (!isset($this->event_id)) {            throw new Exception("Insira um identificador de evento antes de realizar essa ação.");        }        try {            if (!is_object($user)) {                throw new Exception("Usuário inválido para adicionar lote.");            }            $filters = Array(                "nome" => FILTER_SANITIZE_SPECIAL_CHARS,                "valor" => FILTER_VALIDATE_FLOAT,                "max_venda" => FILTER_VALIDATE_INT,                "genero" => FILTER_SANITIZE_SPECIAL_CHARS,                "tipo" => FILTER_VALIDATE_INT,                "parent" => FILTER_VALIDATE_INT            );            $lote = filter_input_array(INPUT_POST, $filters);            foreach ($lote as $index => $values) {                if ($values == NULL && !is_numeric($values) && ($index != "genero" && $index != "valor" && $index != "parent" && $index != "max_venda")) {                    throw new Exception("Valores inválidos para adição de lote. " . $index);                }            }            if ($lote["genero"] != "" && ($lote["genero"] != "m" && $lote["genero"] != "f")) {                throw new Exception("Insira um gênero válido para o pacote.");            }            if ($lote['tipo'] != 0 && $lote['tipo'] != 1) {                throw new Exception("Tipo do lote inválido.");            }            if ($lote['tipo'] == 0) {                $status = DYON_LOTE_STATUS_OPEN;                if ($lote['valor'] == "" || !is_numeric($lote['valor'])) {                    $lote['valor'] = 0;                }            } else {                $status = DYON_LOTE_STATUS_HEADER;                $lote['valor'] = 0;            }            if (!is_numeric($lote['max_venda'])) {                $lote['max_venda'] = 0;            }            $lote_to_add = Array($lote['nome'], $lote['valor'], $lote['max_venda'], $this->event_id, $status);            $field_list = Array("nome", "valor", "max_venda", "id_evento", "status");            if ($lote["genero"] != "") {                $lote_to_add[] = $lote["genero"];                $field_list[] = "genero";                $lote_to_add[] = 0;                $field_list[] = "flag_multisex";            } else {                $lote_to_add[] = 1;                $field_list[] = "flag_multisex";            }            if ($lote['parent'] != "" && is_numeric($lote['parent'])) {                $lote = $this->getLoteById($lote['parent']);                $lote_to_add[] = $lote['id'];                $field_list[] = "id_parent";            }            $this->conn->prepareinsert("lote", $lote_to_add, $field_list, "");            if (!$this->conn->executa()) {                throw new Exception("Erro ao adicionar evento.");            }            $lote_to_add[1] = "R$" . number_format($lote_to_add[1], 2, ',', '.');            $lote_to_add[3] = $this->conn->pegarMax("lote") - 1;            return $lote_to_add;        } catch (Exception $ex) {            throw new Exception($ex->getMessage());        }    }    public function editStatus($user, $lote_id = NULL) {        if (!isset($this->event_id)) {            throw new Exception("Insira um identificador de evento antes de realizar essa ação.");        }        try {            if (!is_object($user)) {                throw new Exception("Usuário inválido para adicionar lote.");            }            if (!isset($lote_id)) {                $lote_id = filter_input(INPUT_POST, "lote_id", FILTER_VALIDATE_INT);            }            if (!is_numeric($lote_id)) {                throw new Exception("O identificado do lote é inválido.");            }            $this->conn->prepareselect("lote", "status", "id", $lote_id);            if (!$this->conn->executa()) {                throw new Exception("Não foi possível pegar o status do lote.");            }            if ($this->conn->fetch['status'] == 2) {                $new_status = 1;            } else if ($this->conn->fetch['status'] == 1) {                $new_status = 2;            } else {                throw new Exception("Não é possível alterar o status desse lote.");            }            $this->conn->prepareupdate($new_status, "status", "lote", $lote_id, "id");            if (!$this->conn->executa()) {                throw new Exception("Não foi possível alterar o status do lote.");            }            return $new_status;        } catch (Exception $ex) {            throw new Exception($ex->getMessage());        }    }    public function checkLoteStatus($id_lote) {        if (!is_numeric($id_lote)) {            throw new Exception("O identificador do lote é inválido.");        }        $lote = $this->getLoteById($id_lote);        $this->conn->prepareselect("pacote", "id", array("id_lote", "status IN (2,3,4)"), array($id_lote), array("="), "count");        if (!$this->conn->executa() || $this->conn->fetch[0] == NULL) {            $lote['vendidos'] = 0;        } else {            $lote['vendidos'] = $this->conn->fetch[0];        }        if ($lote['vendidos'] >= $lote['max_venda']) {            $this->conn->prepareupdate(1, "status", "lote", $id_lote, "id");            if (!$this->conn->executa()) {                throw new Exception("Não foi possível alterar o status do lote.");            }        }    }    public function interfaceEditStatus($url) {        try {            $usercontroller = new userController();            $user = $usercontroller->getUser(DYON_USER_ADMIN);            if (!$url["ajax"]) {                throw new Exception("Requisição inválida.");            }            $this->loadEventId($url[1]);            $status = $this->editStatus($user);            echo json_encode(Array("success" => "true", "status" => $status));        } catch (Exception $error) {            echo json_encode(Array("success" => "false", "error" => $error->getMessage()));        }    }    public function interfaceAddLote($url) {        try {            $usercontroller = new userController();            $user = $usercontroller->getUser(DYON_USER_ADMIN);            $eventController = new eventController();            $event = $eventController->loadEvent($url[1]);            try {                if (isset($_POST['submit'])) {                    $this->loadEventId($url[1]);                    $lote = $this->addLote($user);                    $confirm_add_flag = true;                }            } catch (Exception $error) {                $error_add_flag = $error->getMessage();            }            echo json_encode(Array("success" => "true", "lote" => $lote, "html" => $this->twig->render("evento/add_lote.twig", Array("error_add_flag" => $error_add_flag, "confirm_add_flag" => $confirm_add_flag, "ajax" => true, "event" => $event, "config" => config::$html_preload,))));        } catch (Exception $error) {            if ($url["ajax"]) {                echo $this->twig->render("evento/add_lote.twig", Array("ajax" => true, "ajax_error" => $error->getMessage(), "config" => config::$html_preload));            } else {                echo json_encode(Array("success" => "false", "error" => $error->getMessage()));            }        }    }    public function init($url) {        Twig_Autoloader::register();        $this->twig_loader = new Twig_Loader_Filesystem('includes/interface/templates/manager');        $this->twig = new Twig_Environment($this->twig_loader);        switch ($url[3]) {            case "add":                $this->interfaceAddLote($url);                break;            case "status":                $this->interfaceEditStatus($url);                break;            default:                header("location: " . HTTP_ROOT . "/eventos/" . $url[1]);        }    }}?>