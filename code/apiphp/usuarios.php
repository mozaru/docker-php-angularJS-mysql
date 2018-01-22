<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

  class usuario
  {
      private $host     = "bd";
      private $db_name  = "viagem";
      private $username = "operador";
      private $password = "123456";
      private $conn;
      
      public function preparaSQL($query)
      {
            try{
                  $this->$conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                  $this->$conn->exec("set names utf8");
            }catch(PDOException $exception){
                  echo "Connection error: " . $exception->getMessage();
                  exit();
            }
            // prepare query statement
            return $this->$conn->prepare($query);
      }
      public function listar()
      {
            $query = "SELECT id, apelido, nome, email, ativo, senha, data FROM usuario order by id";
            $stmt = $this->preparaSQL($query);
            // execute query
            $stmt->execute();

            $vet = $stmt->fetchAll(PDO::FETCH_OBJ);
            return $vet;
      }
      public function obter($id)
      {
            $query = "SELECT id, apelido, nome, email, ativo, senha, data FROM usuario where id=:id";
            $stmt = $this->preparaSQL($query);
            $stmt->bindParam("id",$id);
            // execute query
            $stmt->execute();

            $vet = $stmt->fetchAll(PDO::FETCH_OBJ);
            return $vet[0];
      }
      public function inserir($obj)
      {
            $query = 'INSERT INTO usuario (apelido, nome, email, senha, data) values (:apelido, :nome, :email, :senha, :data)';
            $stmt = $this->preparaSQL($query);
            // execute query
            $stmt->bindParam("apelido",$obj->apelido);
            $stmt->bindParam("nome",$obj->nome);
            $stmt->bindParam("email",$obj->email);
            $stmt->bindParam("senha",md5($obj->senha));
            $stmt->bindParam("data", date('Y-m-d H:i:s'));
            $stmt->execute();
            $obj->id = $this->$conn->lastInsertId();
            return $obj;
      }
      public function remover($id)
      {
            $query = 'DELETE from usuario where id=:id';
            $stmt = $this->preparaSQL($query);
            // execute query
            $stmt->bindParam("id",$id);
            $stmt->execute();

            return $id;
      }
      public function alterar($id, $obj)
      {
            $query = 'UPDATE usuario set apelido=:apelido, nome=:nome, email=:email, senha=:senha where id=:id';
            $stmt = $this->preparaSQL($query);
            // execute query
            $stmt->bindParam("apelido",$obj->apelido);
            $stmt->bindParam("nome",$obj->nome);
            $stmt->bindParam("email",$obj->email);
            $stmt->bindParam("senha",md5($obj->senha));
            $stmt->bindParam("id", $id);
            $stmt->execute();
            return $obj;
      }

      public function resetar($id)
      {
            $query = 'UPDATE usuario set senha=:senha where id=:id';
            $stmt = $this->preparaSQL($query);
            // execute query
            $stmt->bindParam("senha",md5('123456'));
            $stmt->bindParam("id", $id);
            $stmt->execute();
            return $id;
      }

      public function ativar($id)
      {
            $query = 'UPDATE usuario set ativo=1 where id=:id';
            $stmt = $this->preparaSQL($query);
            // execute query
            $stmt->bindParam("id", $id);
            $stmt->execute();
            return $id;
      }

      public function desativar($id)
      {
            $query = 'UPDATE usuario set ativo=0 where id=:id';
            $stmt = $this->preparaSQL($query);
            // execute query
            $stmt->bindParam("id", $id);
            $stmt->execute();
            return $id;
      }
      
}
  

?>
