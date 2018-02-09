<?php

require_once('constantes.php');

class banco
{
    private $host     = _BD_HOST_;
    private $db_name  = _BD_DATABASE_;
    private $username = _BD_LOGIN_;
    private $password = _BD_PASSWORD_;
    private $conn;
    private $stmt;
    private $tipo     = "QUERY";
    private $erro     = "";
    private $rowcount = 0;
    private $lastid   = 0;

    function __construct() {
        $this->tipo = "QUERY";
    }

    public function conectar()
    {
        $this->erro="";
        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        }catch(Exception $exception){
            $this->erro = 'Não foi possivel conectar com o Banco de Dados';//$exception->getMessage();
        }
    }

    public function desconectar()
    {
        if (isset($this->stmt) && !is_null($this->stmt))
            $this->stmt = null;
        if (isset($this->conn) && !is_null($this->conn))
            $this->conn = null;
    }

    function temErro() { return $this->erro!=""; }

    function getErro() { return $this->erro;     }

    public function prepara($query)

    {
        try{
            $this->rowcount = -1;
            $this->lastid = -1;
            $this->conectar();
            if ($this->temErro())  return;
            $this->stmt = $this->conn->prepare($query);
            if (preg_match('/insert/i',$query))
                $this->tipo = 'INSERT';
            else if (preg_match('/update/i',$query))
                $this->tipo = 'UPDATE';
            else if (preg_match('/delete/i',$query))
                $this->tipo = 'DELETE';
            else 
                $this->tipo = 'QUERY';
        }catch(Exception $exception){
            $this->erro = 'Erro na preparação da consulta ao banco de dados';//$exception->getMessage();
        }
    }

    public function parametro($nome, $valor)
    {
        try{
            if ($this->temErro())  return;
            $this->stmt->bindParam($nome, $valor);
        }catch(Exception $exception){
            $this->erro = 'Falha na configuração dos parametros da consulta ao banco de dados';//$exception->getMessage();
        }
    }

    public function count()
    {
        try{
            if ($this->temErro())  return;
            return $this->rowcount;
        }catch(Exception $exception){
            $this->erro = 'Erro ao tentar verificar a quantidade de elementos da consulta ao banco de dados';//$exception->getMessage();
        }
    }

    public function lastid()
    {
        try{
            if ($this->temErro())  return;
            return $this->lastid;
        }catch(Exception $exception){
            $this->erro = 'Erro ao tentar verificar o ultimo id inserido no banco de dados';//$exception->getMessage();
        }
    }

    public function executar()
    {
        try{
            if ($this->temErro())  return;
            $this->stmt->execute();
            if ($this->tipo==="QUERY"){
                $data = $this->stmt->fetchAll(PDO::FETCH_OBJ);
                $this->rowcount = $this->stmt->rowcount();
            }else if ($this->tipo==="INSERT"){
                $data = $this->lastid = $this->conn->lastInsertId();
                $this->rowcount = $this->stmt->rowCount();
            }else if ($this->tipo==="UPDATE")
                $data = $this->rowcount = $this->stmt->rowCount();
            else if ($this->tipo==="DELETE")
                $data = $this->rowcount = $this->stmt->rowCount();
            else 
                throw new Exception("deu problema");
            return $data;
        }catch(Exception $exception){
            $this->erro = 'Erro na execução da consulta ao banco de dados';//$exception->getMessage();
        }
        finally{
            $this->desconectar();
        }
    }

    public function listar($query)
    {
        try{
            $this->prepara($query);
            return $this->executar();
        }catch(Exception $exception){
            $this->erro = 'Erro ao tentar listar os elementos do banco de dados';//$exception->getMessage();
        }
        finally{
            $this->desconectar();
        }
    }

    public function obterPeloId($query, $id)
    {
        try{
            $this->prepara($query);
            $this->parametro("id", $id);
            $vet = $this->executar();
            if ($this->temErro())  return -1;
            return $vet[0];
        }catch(Exception $exception){
            $this->erro = 'Erro ao obter um elemento do banco de dados';//$exception->getMessage();
        }
        finally{
            $this->desconectar();
        }        
    }
}
?>