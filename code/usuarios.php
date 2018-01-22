<?php
  //https://www.codeofaninja.com/2017/02/create-simple-rest-api-in-php.html
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");

  class usuario
  {
    private $host     = "bd";
    private $db_name  = "viagem";
    private $username = "operador";
    private $password = "123456";

    public function ObterTodos()
    {
      $query = "SELECT id, apelido, nome, email, senha, data FROM usuario order by id";
      try{
            $conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $conn->exec("set names utf8");
      }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
            exit();
      }
      // prepare query statement
      $stmt = $conn->prepare($query);
      // execute query
      $stmt->execute();

      $num = $stmt->rowCount();

      // check if more than 0 record found
      if($num>0){

          // products array
          $vet=array();
          //$vet["usuarios"]=array();

          // retrieve our table contents
          // fetch() is faster than fetchAll()
          // http://stackoverflow.com/questions/2770630/pdofetchall-vs-pdofetch-in-a-loop
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $item=array(
                   "id" => $row['id'],
                   "apelido" => $row['apelido'],
                   "nome" => $row['nome'],
                   "email" => html_entity_decode($row['email']),
                   "senha" => $row['senha'],
                   "data" => $row['data']
                );

                //array_push($vet["usuarios"], $item);
		array_push($vet, $item);
           }

           echo json_encode($vet);
        }

        else{
          echo json_encode(array("message" => "No products found."));
        }
    }
    public function Listar()//eh o mesmo do obtertodos, ma scom codigo mais simples
    {
      $query = "SELECT id, apelido, nome, email, senha, data FROM usuario order by id";
      try{
            $conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $conn->exec("set names utf8");
      }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
            exit();
      }
      // prepare query statement
      $stmt = $conn->prepare($query);
      // execute query
      $stmt->execute();

      $vet = $stmt->fetchAll(PDO::FETCH_OBJ);
      echo json_encode($vet);
    }
}
  
  $usuario = new usuario();
  $usuario->Listar();

?>
