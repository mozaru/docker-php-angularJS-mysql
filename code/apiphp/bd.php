<?php

class banco
{
    private $host     = "bd";
    private $db_name  = "viagem";
    private $username = "operador";
    private $password = "123456";
    private $conn;
    private $stmt;
    private $tipo     = "QUERY";
    function __construct() {
        $this->tipo = "QUERY";
    }

    public function conectar()
    {
        try{
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                $this->conn->exec("set names utf8");
        }catch(PDOException $exception){
                echo "Connection error: " . $exception->getMessage();
        }
    }

    public function prepara($query)
    {
        try{
            $this->conectar();
            $this->stmt = $this->conn->prepare($query);

            if (preg_match('/insert/i',$query))
                $this->tipo = 'INSERT';
            else if (preg_match('/update/i',$query))
                $this->tipo = 'UPDATE';
            else if (preg_match('/delete/i',$query))
                $this->tipo = 'DELETE';
            else 
                $this->tipo = 'QUERY';

        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
    }

    public function parametro($nome, $valor)
    {
        try{
            $this->stmt->bindParam($nome, $valor);
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
            exit();
        }
    }

    public function count()
    {
        return $this->stmt->rowCount();
    }

    public function executar()
    {
        try{
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
        }catch(PDOException $exception){            
            echo "Connection error: " . $exception->getMessage();
        }
    }

    public function listar($query)
    {
        try{
            $this->prepara($query);
            return $this->executar();
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
            exit();
        }
    }

    public function obterPeloId($query, $id)
    {
        try{
            $this->prepara($query);
            $this->parametro("id", $id);
            $vet = $this->executar();
            return $vet[0];
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
            exit();
        }            
    }
}
?>