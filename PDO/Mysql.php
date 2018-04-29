<?php
require_once('BaseAdapter.php');
	

class Mysql extends BaseAdapter
{
    /**
     * @param $config
     *
     * @return mixed
     */
    protected function doConnect($config)
    {
        $connectionString = "mysql:dbname={$config['database']}";
        
        if (isset($config['host'])) {
            $connectionString .= ";host={$config['host']}";
        }
        
        if (isset($config['port'])) {
            $connectionString .= ";port={$config['port']}";
        }

        if (isset($config['unix_socket'])) {
            $connectionString .= ";unix_socket={$config['unix_socket']}";
        }

		$connection = new PDO($connectionString,$config['username'], $config['password'], $config['options']);
		//$connection->errorCode();
		//$connection->errorInfo();
		//$connection->commit();
		//$connection->rollBack();
		//$connection->beginTransaction();
/*
        $connection = $this->container->build(
            '\PDO',
            array($connectionString, $config['username'], $config['password'], $config['options'])
        );
*/
        if (isset($config['charset'])) {
            $connection->prepare("SET NAMES '{$config['charset']}'")->execute();
        }
        
        return $connection;
    }
}
