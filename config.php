
<?php

$cfg['APP_NAME'] = 'Topics'; // application name

$cfg['LOG_LEVEL'] = 3; // 0 - nothing, 1 - errors, 2 - warnings, 3 - all, 4 - all with cache
$cfg['SQL_LOG_LEVEL'] = 1; // 0 - off, 1 - on
$cfg['LOG_STOPWATCH'] = 1; // 0 - off, 1 - on
$cfg['SQL_SEPARATE_LOGGING'] = 1; // 0 - off, 1 - on
$cfg['LOG_CACHE'] = 1; // 0 - off, 1 - on

$cfg['LOG_DIR'] = 'logs\\'; // directory where log files will be saved
$cfg['CACHE_DIR'] = 'cache\\'; // directory where cache files will be saved
$cfg['UPLOAD_DIR'] = 'upload\\'; // directory where uploaded files will be saved
$cfg['GRID_EXPORT_DIR'] = '$GRID_EXPORT_DIR$'; // directory where grid export files will be saved - relative path

$cfg['IS_DEV'] = true; // true if this version is development or false if not

$cfg['APP_REAL_DIR'] = 'C:\\xampp\\htdocs\\topics\\'; // absolute path to the application root directory

$cfg['DB_SERVER'] = 'localhost'; // database server address
$cfg['DB_PORT'] = ''; // database server port
$cfg['DB_USER'] = 'root'; // database user
$cfg['DB_PASS'] = ''; // database user password
$cfg['DB_NAME'] = 'topics'; // database name

$cfg['GRID_SIZE'] = 20; // grid row count, must be greater than 1

$cfg['FULL_DELETE'] = false; // true if content is deleted fully or false if it just has isDeleted = 1

$cfg['PHP_DIR_FULLPATH'] = 'C:\\xampp\\php\\'; // path to the php directory

$cfg['MAIL_SERVER'] = 'smtp.seznam.cz'; // mail server address
$cfg['MAIL_SERVER_PORT'] = 465; // mail server port
$cfg['MAIL_USERNAME'] = 'lv-topics@seznam.cz'; // mail server username login
$cfg['MAIL_PASSWORD'] = 'Vm0wd2QyUXlVWGxWV0d4V1YwZDRWMVl3WkRSWFJteFZVMjA1VjAxV2JETlhhMk0xVmpGYWMySkVUbGhoTWsweFZqQmFTMk15U2tWVWJHaG9UV3N3ZUZadGNFZFRNazE1VTJ0V1ZXSkhhRzlVVmxaM1ZsWmFkR05GWkZwV01ERTFWVEowVjFaWFNraGhSemxWVmpOT00xcFZXbUZqVmtaMFVteHdWMDFFUlRGV1ZFb3dWakZhV0ZOcmFHaFNlbXhXVm0xNFlVMHhXbk5YYlVaclVqQTFSMVV5TVRSVk1rcFhVMnR3VjJKVVJYZFpla3BIVmpGT2RWVnRhRk5sYlhoWFZtMHdlR0l4U2tkWGJHUllZbGhTV0ZSV2FFTlNiRnBZWlVoa1YwMUVSa1pWYkZKRFZqSkdjbUV6YUZaaGExcG9WakJhVDJOdFJrZFhiV2hzWWxob2IxWXhaRFJpTWtsNFZHdGtWbUpHV2xSWmJGWmhZMnhXY1ZGVVJsTk5WMUo1VmpKNFQxWlhTbFpqUldSYVRVWmFNMVpxU2t0V1ZrcFpXa1prYUdFeGNGbFhhMVpoVkRKT2MyTkZaR2hTTW5oVVZGY3hiMkl4V1hoWGJFNVRUVmQ0VjFSVmFHOVhSMHB5VGxac1dtSkdXbWhaTW5oWFkxWktkRkpzVWxkaWEwcElWbXBLTkZReFdsaFRhMlJxVW0xNGFGVXdhRU5UUmxweFUydGFiRlpzV2xwWGExcDNZa2RGZWxGcmJGZFdNMEpJVmtSS1UxWXhWblZWYlhCVFlYcFdkMVp0Y0V0aU1rbDRWMWhvV0dKRk5WUlVWbVEwVmpGU1ZtRkhPVmROYTNCNVZHeGFjMWR0U2tkWGJXaGFUVlp3ZWxreU1VZFNiRkp6Vkcxc1UySnJTbUZXTW5oWFlqSkZlRmRZWkU1V1ZscFVXV3RrVTFsV1VsWlhiVVpPVFZad2VGVXlkREJXTVZweVkwWndXR0V4Y0ROWmEyUkdaV3hHY21KR2FGaFRSVXBKVm10U1MxVXhXWGhYYmxaV1lsZG9WRmxZY0ZkbGJHUllaVWM1YVUxWFVraFdNalZUVkd4T1NHRkdRbFppVkVVd1ZtcEdVMVp0UmtoUFZtUk9ZVE5DTlZaSGVHRmpNV1IwVTJ0b2FGSnNTbGhVVlZwM1ZrWmFjVk5yWkZOaVJrcDZWa2N4YzFVeVNuSlRiVVpYVFc1b1dGZFdXbEpsVmtweVdrWm9hV0Y2Vm5oV1ZFSnJUa1prUjFWc1pGaGhNMUpVVlcxNGQyVkdWbGRoUnpsb1RWWndlbFl5Y0VkV01ERjFZVWhLV2xaWFVrZGFWV1JQVWxaa2MxcEhiRmhTVlhCS1ZtMTBVMU14VlhoWFdHaFhZbXhhVmxsc1pHOVdSbEpZVGxjNVYxWnNjRWhYVkU1dllWVXhjbUpFVWxkTlYyaDJWakJrUzFKck5WZFdiRlpYVFRGS05sWkhkR0ZXYlZaWVZXdG9hMUp0YUZSWmJGcExVMnhrVjFadFJtcE5WMUl3Vld4b2MyRkdTbGRUYlVaaFZqTlNhRll3V25kU2JGcFpZVVprVGxacmNEVldSM2hoVkRKR1YxTnVVbEJXUlRWWVZGYzFiMWRHYkZoamVrWllVbXR3ZVZkcldtOWhWMFkyVm01b1YxWkZTblpWVkVaelZqRldjMWRzYUdsaVZrcFFWa1phWVdReVZrZFdibEpPVmxkU1ZsUlhkSGRTTVd0M1YyNWtXRkl3VmpSWk1HaExWMnhhV0ZWclpHRldWMUpRVlRGa1MxSXhjRWRhUms1WFYwVktNbFp0TVRCVk1VMTRWVmhzVm1FeVVsVlpiWFIzWWpGV2NWTnRPVmRTYlhoNVZtMDFhMVl4V25OalJFSmhWbGROTVZaWGMzaFhSbFp6WVVaa1RsWXlhREpXTVZwaFV6RkplRlJ1VmxKaVJscFlXV3RvUTFkV1drZFZhMlJXVFZad01GVnRkRzlWUmxwMFlVWlNWVlpXY0dGVVZWcHJWbFpHZEZKdGNFNVdNVWwzVmxSS01HRXhaRWhUYkdob1VtMW9WbFpzV25kTk1YQllaVWhLYkZZeFdrcFhhMXBQVkd4YWNtSXpaRmhpUmxwb1dWUktSMVl4VGxsalJuQk9UVzFvV1ZaR1l6RmlNV1JIVjI1R1UySkZjSE5WYlRGVFYyeGtjbFpVUmxoU2EzQmFWVmQ0YzFkR1duUlZhbHBWVm14d2VsWnFSbGRqTVdSellVZHNhVlpyY0ZwV2JHTjRUa2RSZVZaclpGZGliRXBQVm14a1UxZEdVbFpWYTJSc1ZteEtlbFp0TURWV01ERldZbnBLVm1KWVVuWldha1poVW14a2NtVkdaR2hoTTBKUlZsY3hORmxYVFhoalJXaHBVbXMxVDFac1dscGxiRnAwWlVkR1ZrMVZiRFJaYTFwclYwZEtjbU5GT1ZkaVdHZ3pWakJhYzFkWFRrZGFSbVJUWWtad05WWnRNVEJaVmxGNFZteFdUbEpIY3prPQ=='; // mail server password login
$cfg['MAIL_EMAIL'] = 'lv-topics@seznam.cz'; // mail server email

$cfg['APP_URL_BASE'] = 'localhost'; // base URL of application - e.g. "topics.com" or "localhost"

$cfg['MAX_TOPIC_POST_PINS'] = 5; // maximum of topic post pins

$cfg['MAX_GRID_EXPORT_SIZE'] = 10; // grid row count for export, must be greater than GRID_SIZE

?>
        