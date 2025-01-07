<?php

namespace App\Core;

use App\Core\Datetypes\DateTime;
use App\Exceptions\AException;
use App\Exceptions\InstallationException;

/**
 * Installer is responsible for installing the application
 * 
 * @author Lukas Velek
 */
class Installer {
    private Application $app;
    private DatabaseConnection $db;

    /**
     * Class constructor
     * 
     * @param Application $app Application instance
     * @param DatabaseConnection $db DatabaseConnection instance
     */
    public function __construct(Application $app, DatabaseConnection $db) {
        $this->app = $app;
        $this->db = $db;
    }

    /**
     * Installs the application
     * 
     * 1. Installs the database
     * 2. Encrypts the config file (only sensitive information)
     * 3. Creates "install" file
     */
    public function install() {
        try {
            $this->db->beginTransaction();

            $this->installDb();
            if(!$this->encryptConfigFile()) {
                throw new InstallationException('Could not encrypt the configuration file.');
            }
            if(!$this->createInstallFile()) {
                throw new InstallationException('Could not create the installation file.');
            }

            $this->db->commit();
        } catch(AException $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Creates "install" file
     * 
     * @return bool True on success or false on failure
     */
    private function createInstallFile() {
        $date = new DateTime();
        
        $result = FileManager::saveFile($this->app->cfg['APP_REAL_DIR'] . 'app\\core\\', 'install', 'installed - ' . $date);

        if($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Install the database
     */
    private function installDb() {
        $this->db->installDb();
    }

    /**
     * Encrypts sensitive parameters in the configuration file
     * 
     * @return bool True on success or false on failure
     */
    private function encryptConfigFile() {
        $structure = $this->createConfigFileStructure();

        $keysToEncrypt = [
            'DB_PASS',
            'MAIL_PASSWORD'
        ];

        foreach($this->app->cfg as $key => $value) {
            if(str_contains($value, '\\')) {
                $value = str_replace('\\', '\\\\', $value);
            }
            if(in_array($key, $keysToEncrypt)) {
                $value = $this->encryptValue($value);
            }
            if($value === true) {
                $value = 'true';
            } else if($value === false) {
                $value = 'false';
            }
            $structure = str_replace('$' . $key . '$', $value, $structure);
        }

        $result = FileManager::saveFile($this->app->cfg['APP_REAL_DIR'], 'config.local.php', $structure, true);

        if($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Encrypts passed parameter
     * 
     * @param string $text Text to encrypt
     * @return string Encrypted text
     */
    private function encryptValue(string $text) {
        return CryptManager::encrypt($text);
    }

    /**
     * Returns config file structure
     * 
     * @return string Config file structure
     */
    private function createConfigFileStructure() {
        return '
<?php

$cfg[\'APP_NAME\'] = \'$APP_NAME$\'; // application name

$cfg[\'LOG_LEVEL\'] = $LOG_LEVEL$; // 0 - nothing, 1 - errors, 2 - warnings, 3 - all, 4 - all with cache
$cfg[\'SQL_LOG_LEVEL\'] = $SQL_LOG_LEVEL$; // 0 - off, 1 - on
$cfg[\'LOG_STOPWATCH\'] = $LOG_STOPWATCH$; // 0 - off, 1 - on
$cfg[\'SQL_SEPARATE_LOGGING\'] = $SQL_SEPARATE_LOGGING$; // 0 - off, 1 - on
$cfg[\'LOG_CACHE\'] = $LOG_CACHE$; // 0 - off, 1 - on

$cfg[\'LOG_DIR\'] = \'$LOG_DIR$\'; // directory where log files will be saved
$cfg[\'CACHE_DIR\'] = \'$CACHE_DIR$\'; // directory where cache files will be saved
$cfg[\'UPLOAD_DIR\'] = \'$UPLOAD_DIR$\'; // directory where uploaded files will be saved
$cfg[\'GRID_EXPORT_DIR\'] = \'$GRID_EXPORT_DIR$\'; // directory where grid export files will be saved - relative path

$cfg[\'IS_DEV\'] = $IS_DEV$; // true if this version is development or false if not

$cfg[\'APP_REAL_DIR\'] = \'$APP_REAL_DIR$\'; // absolute path to the application root directory

$cfg[\'DB_SERVER\'] = \'$DB_SERVER$\'; // database server address
$cfg[\'DB_PORT\'] = \'$DB_PORT$\'; // database server port
$cfg[\'DB_USER\'] = \'$DB_USER$\'; // database user
$cfg[\'DB_PASS\'] = \'$DB_PASS$\'; // database user password
$cfg[\'DB_NAME\'] = \'$DB_NAME$\'; // database name

$cfg[\'GRID_SIZE\'] = $GRID_SIZE$; // grid row count, must be greater than 1

$cfg[\'FULL_DELETE\'] = $FULL_DELETE$; // true if content is deleted fully or false if it just has isDeleted = 1

$cfg[\'PHP_DIR_FULLPATH\'] = \'$PHP_DIR_FULLPATH$\'; // path to the php directory

$cfg[\'MAIL_SERVER\'] = \'$MAIL_SERVER$\'; // mail server address
$cfg[\'MAIL_SERVER_PORT\'] = $MAIL_SERVER_PORT$; // mail server port
$cfg[\'MAIL_USERNAME\'] = \'$MAIL_USERNAME$\'; // mail server username login
$cfg[\'MAIL_PASSWORD\'] = \'$MAIL_PASSWORD$\'; // mail server password login
$cfg[\'MAIL_EMAIL\'] = \'$MAIL_EMAIL$\'; // mail server email

$cfg[\'APP_URL_BASE\'] = \'$APP_URL_BASE$\'; // base URL of application - e.g. "topics.com" or "localhost"

$cfg[\'MAX_TOPIC_POST_PINS\'] = $MAX_TOPIC_POST_PINS$; // maximum of topic post pins

$cfg[\'MAX_GRID_EXPORT_SIZE\'] = $MAX_GRID_EXPORT_SIZE$; // grid row count for export, must be greater than GRID_SIZE

?>
        ';
    }
}

?>