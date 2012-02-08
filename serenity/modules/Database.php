<?php
namespace Serenity;


class SerenityQuery
{
    private $sql = '';
    private $modelClass;
    private $from = array();
    private $select;
    private $where;
    private $orderBy;
    private $limit;
    private $params = array();

    /**
     * Fetch all matching models from the database
     * @return array
     */
    public function fetchAll()
    {
        if($this->sql == '')
            $this->buildQuery();

        $stmt = sp::db()->query($this->sql, $this->params);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->modelClass);

        $modelArray = array();

        foreach ($stmt as $model)
        {
            $model->onLoadedFromDatabase();
            $modelArray[$model->getPrimaryKeyValue()] = $model;
        }

        return $modelArray;
    }

    /**
     * Fetch a single model from the database
     * @return SerenityModel
     */

    public function fetchOne()
    {
        if(!$this->limit)
            $this->limit = 1;

        $modelArray = $this->fetchAll();

        if(count($modelArray) == 0)
            return null;

        return current($modelArray);
    }

    public function fetchCount()
    {
        $this->buildQuery(true);
        $stmt = sp::db()->query($this->sql, $this->params);
        $count = $stmt->fetchColumn();

        return $count;
    }

    public function sql($sql, $params = array())
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    public function setModelClass($class)
    {
        $this->modelClass = $class;
    }

    /**
     * Add an order by clause to the query
     * @return SerenityQuery
     */

    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Add a where clause to the query
     * @return SerenityQuery
     */
    public function addWhere($where, $param = null)
    {
        if($this->where != "")
        {
            $this->where .= " AND ";
        }

        $this->where .= $where;

        if(!is_null($param))
        {
            $this->params[] = $param . '';
        }

        return $this;
    }

    /**
     * Add an extra table to the from clause
     * @return SerenityQuery
     */
    public function addFrom($from)
    {
        $this->from[] = $from;

        return $this;
    }

    /**
     * Add a limit clause to the query
     * @return SerenityQuery
     */

    public function limit($qty)
    {
        $this->limit = $qty;

        return $this;
    }

    protected function buildQuery($countOnly = false)
    {
        if($countOnly)
        {
            $fromTxt = implode($this->from, ',');
            $this->sql = 'SELECT count(*) FROM ' . $fromTxt;
        }
        else
        {
            $fromTxt = implode($this->from, ',');
            $this->sql = 'SELECT ' . $this->from[0] . '.* FROM ' . $fromTxt;
        }

        if($this->where != "")
            $this->sql .= " WHERE " . $this->where;

        if($this->orderBy != "")
            $this->sql .= " ORDER BY " . $this->orderBy;

        if($this->limit != "")
            $this->sql .= " LIMIT " . $this->limit;
    }
}

/**
 * Serenity Database Class
 * @author Pete
 * Manages the connection to the database
 */
class SerenityDatabase
{
    private $connection;

    private $host;
    private $username;
    private $password;
    private $databaseName;

    public $queryLog = array();

    /**
     * Set connection parameters
     * @param String $host
     * @param String $username
     * @param String $password
     * @param String $databaseName
     */
    function newConnection($host, $username, $password, $databaseName)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->databaseName = $databaseName;
    }

    /**
     * The ID of the last row to be inserted
     * @return number
     */
    function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Performs a raw query after it has already been prepared
     * @param String $query
     * @throws SerenityException
     * @return PDOStatement
     */
    function query($query, $params = array())
    {
        $smt = $this->connection->prepare($query);

        $paramString = "";

        if(sp::app()->isDebugMode() && count($params))
        {
            $paramNumber = 0;
            $paramString = "  -- Params(";

            foreach($params as $param)
            {
                $paramNumber++;
                $paramString .= $paramNumber . ': "' . $param . '", ';
            }
        }

        if(sp::app()->isDebugMode() && count($params))
            $paramString = substr($paramString, 0, strlen($paramString) - 2) . ")";

        $smt->execute($params);

        if(sp::app()->isDebugMode())
        {
            $this->queryLog[] = $query . $paramString;
        }

        $errorInfo = $smt->errorInfo();

        if($errorInfo[2] != null)
        {
            throw new SerenityException("Error in query -> '$query' : " . $errorInfo[2]);
        }

        return $smt;
    }

    /**
     * Connect to database. newConnection() must be called first.
     * @throws SerenityException
     */
    function connect()
    {
        try
        {
            $this->connection = new \PDO("mysql:host=" . $this->host . ";dbname=" . $this->databaseName, $this->username, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

        }
        catch(PDOException $e)
        {
            throw new SerenityException("Error connecting to database.");
        }
    }
}

// Create singleton
$db = new SerenityDatabase();
sp::$dababase = $db;
?>