<?php
namespace Serenity;


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

        if(sf::app()->isDebugMode())
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
sf::$dababase = $db;
?>
