DROP USER IF EXISTS 'operador'@'localhost';
drop database if exists viagem;

create database viagem;

use viagem;

CREATE USER 'operador' IDENTIFIED BY '123456';

CREATE TABLE usuario (
       id INT not null auto_increment,
       apelido varchar(20) not null,
       nome VARCHAR(50) not null,
       email VARCHAR(50) not null,
       ativo boolean not null default 1,
       senha varchar(40) not null,
       data datetime not null,
       primary key (id)
);

insert into usuario (apelido, nome, email, senha, data) values
    ( 'admin', 'administrador', 'admin.silva@gmail.com', md5('123'), NOW()),
    ( 'mozar', 'Mozar Baptista da Silva', 'mozar.silva@gmail.com', md5('123'), NOW());

commit;

GRANT ALL ON viagem.* TO 'operador';
FLUSH PRIVILEGES;