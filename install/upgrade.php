<?php

// Report all PHP errors
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once 'InstallScript.php';

$install = new Install_ImportScript();
$install->preProcess();
$install->processData('admins_server_groups');
$install->processData('blocks');
$install->processData('blocks');
$install->processData('teambans');
$install->processData('games');
$install->processData('groups');
$install->processData('admins');
$install->processData('servers');
$install->processData('logs');
$install->processData('overrides');
$install->processData('bans');
$install->processData('protests');
$install->processData('submissions');
$install->processData('block');
$install->processData('admins_server_groups');
$install->processData('server_group_overrides');
$install->processData('server_groups');
$install->processData('server_groups_immunity');
$install->processData('servers_server_groups');
$install->processData('comments');
$install->processData('demos');
// $install->processData('teambans');