<?php
namespace Serenity;


class SerenityQuery
{
	public $sql;
	
	public $modelClass;
	public $from;
	public $select;
	public $where;
	public $orderBy;
	
	public function fetch()
	{
		$this->buildQuery();

		$stmt = sp::db()->query($this->sql);
        $stmt->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $this->modelClass);

        $modelArray = array();
        
        foreach ($stmt as $model)
        	$modelArray[] = $model;
        	
        return $modelArray;	
	}	
	
	public function fetchOne()
	{
		$modelArray = $this->fetch();
		
        if(count($modelArray) == 0)
            return null;

        return $modelArray[0];
	}
	
	public function orderBy($orderBy)
	{
		$this->orderBy = $orderBy;
		
		return $this;
	}
	
	public function addWhere($where)
	{
		if($this->where != "")
		{
			$this->where .= " AND ";
		}
		
		$this->where .= $where;
		
		return $this;
	}
	
	protected function buildQuery()
	{
		$this->sql = "SELECT * FROM " . $this->from;
		
		if($this->where != "")
			$this->sql .= " WHERE " . $this->where;

		if($this->orderBy != "")
			$this->sql .= " ORDER BY " . $this->orderBy;
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
    function query($query)
    {
        $smt = $this->connection->query($query);

        if(sp::app()->isDebugMode())
        {
            $this->queryLog[] = $query;
        }

        if($smt == false)
        {
            $errorInfo =  $this->connection->errorInfo();
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
