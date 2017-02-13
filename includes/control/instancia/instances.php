<?php/* * **************************************** *     _____                     *    |  __ \                    *    | |  | |_   _  ___  _ __   *    | |  | | | | |/ _ \| '_ \  *    | |__| | |_| | (_) | | | | *    |_____/ \__, |\___/|_| |_| *             __/ |             *            |___/   *            *       Paulo Felipe Possa Parrira [ paul (dot) 0 (at) live (dot) de ] *  ===================================================================== *  File: instances.php *  Type: Controller *  ===================================================================== *  */class instanceController {    public function __construct() {        $this->conn = new conn();    }        public function loadUserInstance(user $user) {        if(!is_numeric($user->getId())) {            throw new Exception("Identificador do usuário inválido.");        }                        $this->conn->prepareselect("instancia_usuario",array("id_instancia","status_usuario","evento_padrao","fluxo_padrao","board_padrao","instancia_padrao"), "id_usuario", $user->getId(), "", "", "", PDO::FETCH_ASSOC, "all");        if(!$this->conn->executa()) {            throw new Exception("O usuário não possui instâncias.");        }                if($this->conn->rowcount == 1) {            $instance = $this->loadInstance($this->conn->fetch[0]);            return array("count" => 1, "instance" => $instance);        } else {            foreach($this->conn->fetch as $index => $userinstance) {                $instances[] = $this->loadInstance($userinstance);            }            return array("count" => count($instances), "instances" => $instances);        }    }        private function loadInstance($instance_index) {        if(is_array($instance_index)) {            $user_instance = true;            $user_instance_info = $instance_index;            $instance_index = $instance_index["id_instancia"];            if(!is_numeric($instance_index)) {                throw new Exception("Identificador da instância inválido.");                            }                    } else {            if(!is_numeric($instance_index)) {                throw new Exception("Identificador da instância inválido.");            }            $user_instance = false;        }                $this->conn->prepareselect("instancia", array("id","nome", "status", "id_plano", "data_criacao", "id_criador"), "id", $instance_index);        if(!$this->conn->executa()) {            throw new Exception("Nenhuma instância encontrada com esse identificador.");        }                $instance = $this->conn->fetch;        if($user_instance) {            $instance['user_info'] = $user_instance_info;        }                return $instance;    }        public function setDefaultInstance(user $user, $instance) {        if(!$user->getId() || !$user->isAuth()) {            throw new Exception("O usuário é inválido.");        }                if(!is_numeric($instance['id'])) {            throw new Exception("O identificador da instância é inválido.");        }                $instance = $this->loadInstance($instance['user_info']);        if($instance['user_info']['status_usuario'] < 1) {            throw new Exception("O usuário não tem permissão para alterar para essa instância.");        }                $this->conn->prepareupdate(0, "instancia_padrao", "instancia_usuario", $user->getId(), "id_usuario",  "INT");        if(!$this->conn->executa()) {            throw new Exception("Não foi possível alterar a instância para padrão. (0)");        }                $this->conn->prepareupdate(1, "instancia_padrao", "instancia_usuario", array($user->getId(),$instance['id']), array("id_usuario","id_instancia"),  "INT");        if(!$this->conn->executa()) {            throw new Exception("Não foi possível alterar a instância para padrão. (1)");        }            }}?>