<?php
namespace Melody\Framework;

use PDO;

class Data
{

    function __construct()
    {
        $this->bdd = Model::getInstance();
    }

    function query($table='')
    {
        return new Query((!empty($table) ? $table : substr(strtolower(get_class($this)), 0, -5)));
    }

    function found_rows()
    {
        $req = $this->bdd->query('SELECT FOUND_ROWS() as rows');
        $data = $req->fetch(PDO::FETCH_NUM);
        $req->closeCursor();

        return $data[0];
    }

    function last_insert_id()
    {
        $req = $this->bdd->query('SELECT LAST_INSERT_ID() as id');
        $data = $req->fetch(PDO::FETCH_NUM);
        $req->closeCursor();

        return $data[0];
    }
}