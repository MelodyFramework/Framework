<?php
namespace Melody\Framework;

use PDO;

Class Query
{
    public $fields 	= array();
    public $table 	= array();
    public $limit 	= array();
    public $where 	= '1' ;
    public $orderby = '';
    public $isAsc 	= true;
    public $debug	= false;

    function __construct($table)
    {
        $this->table = $table;
    }

    function select()
    {
        $this->fields = func_get_args();
        return $this;
    }

    function where($where)
    {
        $this->where = $where;
        return $this;
    }

    function orderBy($orderby)
    {
        $this->orderby = $orderby;
        return $this;
    }

    function limit($start, $offset)
    {
        $this->limit = array($start, $offset);
        return $this;
    }

    function asc()
    {
        $this->isAsc = true;
        return $this;
    }

    function desc()
    {
        $this->isAsc = false;
        return $this;
    }

    function debug()
    {
        $this->debug = true;
        return $this;
    }

    function exec($one=false)
    {
        $bdd = Database::getInstance();
        $query = 'SELECT '.join(', ',$this->fields).' FROM '.$this->table.' WHERE '.$this->where.(!empty($this->orderby) ? ' ORDER BY '.$this->orderby.($this->isAsc ? ' ASC' : ' DESC') : '').((count($this->limit) != 0) ? ' LIMIT '.$this->limit[0].', '.$this->limit[1] : '');
        $req = $bdd->query($query);
        $buffer = ($one) ? $req->fetch(PDO::FETCH_BOTH) : $req->fetchAll(PDO::FETCH_ASSOC);
        if($this->debug)
            var_dump($buffer, $query, $req->errorInfo());
        $req->closeCursor();

        return $buffer;
    }
}