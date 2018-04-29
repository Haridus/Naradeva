<?php
require_once('BaseAdapter.php');
	
class Sqlite extends BaseAdapter
{
    /**
     * @param $config
     *
     * @return mixed
     */
    public function doConnect($config)
    {
        $connectionString = 'sqlite:' . $config['database'];
        $connection = new PDO($connectionString, null, null, $config['options']);
/*
        $connection = $this->container->build(
            '\PDO',
            array($connectionString, null, null, $config['options'])
        );
*/
		return $connection;
    }
}
