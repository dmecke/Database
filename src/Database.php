<?php
class Database
{
    private $database = null;
    private $connection            = null;
    private $UTF8established       = false;
    private $lastQuery             = null;
    private $debug                 = false;
    private $lastStatement         = null;

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    public function establishUTF8()
    {
        $this->UTF8established = true;
        $this->execute('SET NAMES "utf8"');
        $this->execute('SET CHARACTER SET "utf8"');
    }

    public function execute($q)
    {
        $statement = $this->database->prepare($q);
        $statement = $this->replacePlaceholders($statement, func_get_args());
        $this->lastStatement = $statement;
        return $statement->execute();
    }

    public function querySingle($q)
    {
        $data = $this->doQuery($q, func_get_args());
        if (empty($data)) {
            return array();
        } else {
            return $data[0];
        }
    }

    public function queryArray($q)
    {
        return $this->doQuery($q, func_get_args());
    }

    public function queryScalar($q)
    {
        $data = $this->doQuery($q, func_get_args());
        if (empty($data)) {
            return '';
        } else {
            return array_shift($data[0]);
        }
    }

    public function queryScalarArray($q)
    {
        $data = array();
        $rows = $this->doQuery($q, func_get_args());
        foreach ($rows as $row) {
            $data[] = array_shift($row);
        }
        return $data;
    }

    public function getInsertID()
    {
        return $this->database->lastInsertId();
    }

    public function getAffectedRows()
    {
        return $this->lastStatement->rowCount();
    }

    public function getError()
    {
        return $this->database->errorInfo();
    }

    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    public function beginTransaction()
    {
        $q = 'BEGIN';
        $this->execute($q);
    }

    public function commitTransaction()
    {
        $q = 'COMMIT';
        $this->execute($q);
    }

    public function rollbackTransaction()
    {
        $q = 'ROLLBACK';
        $this->execute($q);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    private function doQuery($q, $args)
    {
        $statement = $this->database->prepare($q);
        $statement = $this->replacePlaceholders($statement, $args);
        try {
            $statement->execute();
        } catch (Exception $e) {
            error_log($q);
            error_log($e->getMessage());
        }
        $data = $statement->fetchAll();
        $this->lastStatement = $statement;
        return $data;
    }

    private function replacePlaceholders($statement, $params)
    {
        $count = substr_count($params[0], '?');
        for ($i = 1; $i < $count + 1; $i++) {
            $statement->bindValue($i, $params[$i]);
        }
        return $statement;
    }
}
