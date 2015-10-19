<?php

// Report all PHP errors
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

// Setup config file
require 'bootstrap.php';

/* required info to do the import */
$sb2config = include(WEB_ROOT . 'application/config/sourcebans.php');	// the config file to SourceBans 2

// Old SourceBans Database Information
define('DB_HOST', '');  // The host/ip to your SQL server
define('DB_USER', '');	// The username to connect with
define('DB_PASS', '');	// The password
define('DB_NAME', '');  // Database name	
define('DB_PREFIX', 'op'); // The table prefix for SourceBans
define('DB_PORT','3306'); // The SQL port (Default: 3306)

class Install_ImportScript
{
	protected $instance_old = NULL;
	protected $instance_new = NULL;

	public function __construct()
	{
		global $sb2config;

		/* connect to the database! */
		$old = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
		$new = new mysqli(DB_HOST, $sb2config['components']['db']['username'], $sb2config['components']['db']['password'], 'xeno_sourcebans2');

		/* check connection */
		if ($old->connect_errno || $new->connect_errno)
		{
			printf("Connect failed: %s\n", $mysqli->connect_error);
			exit();
		}
		else
		{
			$this->instance_old = $old;
			$this->instance_new = $new;
		}
	}

	public function __destruct()
	{
		$this->instance_old->close();
		$this->instance_new->close();
	}

	public function preProcess()
	{
		/* Deletes any games registered in SourceBans 2 to make sure stuff works */
		$this->instance_new->query('TRUNCATE `' . $sb2config['components']['db']['tablePrefix'] . '_games`;');
	}

	/* function to do all this magic stuff */
	public function processData($name)
	{
		$template = $this->getTemplate($name);

		if ($result = $this->instance_old->query('SELECT * FROM `' . $template['old_table'] . '`'))
		{
			$this->instance_new->query('SET FOREIGN_KEY_CHECKS = 0;');

			while ($row = $result->fetch_array(MYSQLI_ASSOC))
			{
				$entry = $template['structure'];

				foreach ($row as $key => $value)
				{
					$check = array_search($key, $template['structure']);

					if ($check != false)
					{
						$entry[$check] = $value;
					}
				}

				$this->writeData($entry, $name);
			}

			$this->instance_new->query('SET FOREIGN_KEY_CHECKS = 1;');
			$result->free();
		}
	}

	/* import all converted data into SourceBans 2 */
	private function writeData(&$data, $table)
	{
		global $sb2config;

		$cols = implode(',', array_keys($data));

		foreach (array_values($data) as $value)
		{
			isset($vals) ? $vals .= ',' : $vals = '';

			if ($value === NULL)
			{
				$vals .= "NULL";
			}
			else
			{
				$vals .= '\'' . $this->instance_new->real_escape_string($value) . '\'';
			}
		}

		$result = $this->instance_new->real_query('INSERT INTO `' . $sb2config['components']['db']['tablePrefix'] . $table . '` (' . $cols . ') VALUES (' . $vals . ')');
	}

	/* templates for each process! */
	private function getTemplate($index)
	{
		global $sb2config;

		switch ($index)
		{
			case 'admins':
				return array(
					'old_table' => DB_PREFIX . '_admins',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . '_admins',
					'structure' => array(
						'id' => 'aid',
						'name' => 'user',
						'auth' => 'steam',
						'identity' => 'authid',
						'password' => NULL,
						'password_key' => NULL,
						'group_id' => 'gid',
						'email' => 'email',
						'language' => NULL,
						'theme' => NULL,
						'timezone' => NULL,
						'server_password' => NULL,
						'validation_key' => NULL,
						'login_time' => 'lastvisit',
						'create_time' => time()
					)
				);

				break;

			case 'bans':
				return array(
					'old_table' => DB_PREFIX . '_bans',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'bans',
					'structure' => array(
						'id' => 'bid',
						'type' => 'type',
						'steam' => 'authid',
						'ip' => 'ip',
						'name' => 'name',
						'reason' => 'reason',
						'length' => 'length',
						'server_id' => 'sid',
						'admin_id' => 'aid',
						'admin_ip' => 'adminIp',
						'unban_admin_id' => 'RemovedBy',
						'unban_reason' => 'ureason',
						'unban_time' => 'RemovedOn',
						'create_time' => 'created'
					)
				);

				break;

			case 'blocks':
				return array(
					'old_table' => DB_PREFIX . '_banlog',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'blocks',
					'structure' => array(
						'ban_id' => 'bid',
						'name' => 'name',
						'server_id' => 'sid',
						'create_time' => 'time'
					)
				);

				break;

			case 'groups':
				return array(
					'old_table' => DB_PREFIX . '_groups',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'groups',
					'structure' => array(
						'id' => 'gid',
						'name' => 'name'
					)
				);

				break;

			case 'servers':
				return array(
					'old_table' => DB_PREFIX . '_servers',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'servers',
					'structure' => array(
						'id' => 'sid',
						'ip' => 'ip',
						'port' => 'port',
						'rcon' => 'rcon',
						'game_id' => 'modid',
						'enabled' => 'enabled'
					)
				);

				break;

			case 'server_groups':
				return array(
					'old_table' => DB_PREFIX . '_srvgroups',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'server_groups',
					'structure' => array(
						'id' => 'id',
						'name' => 'name',
						'flags' => 'flags',
						'immunity' => 'immunity'
					)
				);

				break;

			case 'servers_server_groups':
				return array(
					'old_table' => DB_PREFIX . '_servers_groups',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'servers_server_groups',
					'structure' => array(
						'server_id' => 'server_id',
						'group_id' => 'group_id'
					)
				);

				break;

			case 'admins_server_groups':
				return array(
					'old_table' => DB_PREFIX . '_admins_servers_groups',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'admins_server_groups',
					'structure' => array(
						'admin_id' => 'admin_id',
						'group_id' => 'srv_group_id',
						'inherit_order' => ''
					)
				);

				break;

			case 'overrides':
				return array(
					'old_table' => DB_PREFIX . '_overrides',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'overrides',
					'structure' => array(
						'type' => 'type',
						'name' => 'name',
						'flags' => 'flags'
					)
				);

				break;

			case 'server_group_overrides':
				return array(
					'old_table' => DB_PREFIX . '_srvgroups_overrides',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'server_group_overrides',
					'structure' => array(
						'group_id' => 'group_id',
						'type' => 'type',
						'name' => 'name',
						'access' => 'access'
					)
				);

				break;

			case 'comments':
				return array(
					'old_table' => DB_PREFIX . '_comments',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'comments',
					'structure' => array(
						'id' => 'cid',
						'object_type' => 'type',
						'object_id' => 'bid',
						'admin_id' => 'aid',
						'message' => 'commenttxt',
						'update_admin_id' => 'editaid',
						'update_time' => 'edittime',
						'create_time' => 'added'
					)
				);

				break;

			case 'demos':
				return array(
					'old_table' => DB_PREFIX . '_demos',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'demos',
					'structure' => array(
						'object_type' => 'demtype',
						'object_id' => 'demid',
						'filename' => 'origname'
					)
				);

				break;

			case 'games':
				return array(
					'old_table' => DB_PREFIX . '_mods',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'games',
					'structure' => array(
						'id' => 'mid',
						'name' => 'name',
						'folder' => 'modfolder',
						'icon' => 'icon'
					)
				);

				break;

			case 'logs':
				return array(
					'old_table' => DB_PREFIX . '_log',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'logs',
					'structure' => array(
						'id' => 'lid',
						'type' => 'type',
						'title' => 'title',
						'message' => 'message',
						'function' => 'function',
						'query' => 'query',
						'admin_id' => 'aid',
						'admin_ip' => 'host',
						'create_time' => 'created'
					)
				);

				break;

			case 'protests':
				return array(
					'old_table' => DB_PREFIX . '_protests',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'protests',
					'structure' => array(
						'id' => 'pid',
						'ban_id' => 'bid',
						'reason' => 'reason',
						'user_email' => 'email',
						'user_ip' => 'pip',
						'archived' => 'archiv',
						'create_time' => 'datesubmitted'
					)
				);

				break;

			case 'submissions':
				return array(
					'old_table' => DB_PREFIX . '_submissions',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'submissions',
					'structure' => array(
						'id' => 'subid',
						'name' => 'name',
						'steam' => 'SteamId',
						'ip' => 'ip',
						'reason' => 'reason',
						'server_id' => 'server',
						'user_name' => 'subname',
						'user_email' => 'email',
						'user_ip' => 'ip',
						'archived' => 'archiv',
						'create_time' => 'submitted'
					)
				);

				break;

			case 'teambans':
				return array(
					'old_table' => DB_PREFIX . '_ctbans',
					'new_table' => $sb2config['components']['db']['tablePrefix'] . 'teambans',
					'structure' => array(
						'id' => 'bid',
						'type' => 'type',
						'team' => '2',
						'steam' => 'authid',
						'ip' => 'ip',
						'name' => 'name',
						'reason' => 'reason',
						'length' => 'length',
						'server_id' => NULL,
						'admin_id' => 'aid',
						'admin_ip' => 'adminIp',
						'unban_admin_id' => 'RemovedBy',
						'unban_reason' => 'ureason',
						'unban_time' => 'RemovedOn',
						'create_time' => 'created'
					)
				);
		}
	}
}