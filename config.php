<?php

$cfg['APP_NAME'] = '';

$cfg['LOG_LEVEL'] = 1; // 0 - nothing, 1 - errors, 2 - warnings, 3 - all, 4 - all with cache
$cfg['SQL_LOG_LEVEL'] = 0; // 0 - off, 1 - on
$cfg['LOG_STOPWATCH'] = 0; // 0 - off, 1 - on
$cfg['SQL_SEPARATE_LOGGING'] = 0; // 0 - off, 1 - on

$cfg['LOG_DIR'] = ''; // directory where log files will be saved
$cfg['CACHE_DIR'] = ''; // directory where cache files will be saved
$cfg['UPLOAD_DIR'] = ''; // directory where uploaded files will be saved

$cfg['ENABLE_CACHING'] = true; // true if caching is enabled or false if not

$cfg['IS_DEV'] = false; // true if this version is development or false if not

$cfg['APP_REAL_DIR'] = '';

$cfg['DB_SERVER'] = ''; // database server address
$cfg['DB_USER'] = ''; // database user
$cfg['DB_PASS'] = ''; // database user password
$cfg['DB_NAME'] = ''; // database name

$cfg['GRID_SIZE'] = 20; // grid row count, must be greater than 1

$cfg['FULL_DELETE'] = false; // true if content is deleted fully or false if it just has isDeleted = 1

$cfg['PHP_DIR_FULLPATH'] = ''; // path to the php directory

$cfg['MAIL_SERVER'] = ''; // mail server address
$cfg['MAIL_SERVER_PORT'] = 465; // mail server port
$cfg['MAIL_USERNAME'] = ''; // mail server username login
$cfg['MAIL_PASSWORD'] = ''; // mail server password login
$cfg['MAIL_EMAIL'] = ''; // mail server email

$cfg['ID_SERVICE_USER'] = 1; // service user ID

$cfg['APP_URL_BASE'] = ''; // base URL of application - e.g. "topics.com" or "localhost"

?>