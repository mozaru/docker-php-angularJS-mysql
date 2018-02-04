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
    private $erro="";

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
    function temErro() { return $this->erro!=""; }
    function getErro() { return $this->erro;     }
    public function prepara($query)
    {
        try{
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
            return $this->stmt->rowCount();
        }catch(Exception $exception){
            $this->erro = 'Erro ao tentar verificar a quantidade de elementos da consulta ao banco de dados';//$exception->getMessage();
        }
    }

    public function executar()
    {
        try{
            if ($this->temErro())  return;
            $this->stmt->execute();
            if ($this->tipo==="QUERY")
                return $this->stmt->fetchAll(PDO::FETCH_OBJ);
            else if ($this->tipo==="INSERT")
                return $this->conn->lastInsertId();
            else if ($this->tipo==="UPDATE")
                return $this->stmt->rowCount();
            else if ($this->tipo==="DELETE")
                return $this->stmt->rowCount();
            else 
                return "deu problema";
        }catch(Exception $exception){
            $this->erro = 'Erro na execução da consulta ao banco de dados';//$exception->getMessage();
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
    }
}
?>