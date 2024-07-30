<?php

$cfg['APP_NAME'] = '';

$cfg['LOG_LEVEL'] = 1; // 0 - nothing, 1 - errors, 2 - warnings, 3 - all, 4 - all with cache
$cfg['SQL_LOG_LEVEL'] = 0; // 0 - off, 1 - on
$cfg['LOG_STOPWATCH'] = 0; // 0 - off, 1 - on
$cfg['SQL_SEPARATE_LOGGING'] = 0; // 0 - off, 1 - on

$cfg['LOG_DIR'] = '';
$cfg['CACHE_DIR'] = '';
$cfg['UPLOAD_DIR'] = '';

$cfg['ENABLE_CACHING'] = true; // true if caching is enabled or false if not

$cfg['IS_DEV'] = false; // true if this version is development or false if not

$cfg['APP_REAL_DIR'] = '';

$cfg['DB_SERVER'] = ''; // database server address
$cfg['DB_USER'] = ''; // database user
$cfg['DB_PASS'] = ''; // database user password
$cfg['DB_NAME'] = ''; // database name

$cfg['GRID_SIZE'] = 20; // grid row count

$cfg['FULL_DELETE'] = false; // true if content is deleted fully or false if it just has isDeleted = 1

$cfg['PHP_DIR_FULLPATH'] = ''; // path to the php directory

?>