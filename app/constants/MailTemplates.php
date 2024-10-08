<?php

namespace App\Constants;

class MailTemplates {
    public const NEW_TOPIC_INVITE = 1;
    public const REGISTRATION_CONFIRMATION = 2;
    public const FORGOTTEN_PASSWORD = 3;

    public static function getTemplateData(int $template, bool $html = true) {
        $result = [];

        switch($template) {
            case self::NEW_TOPIC_INVITE:
                $result = [
                    'New topic invite - Topics',
                    "Dear \$USER_NAME$," . self::newLine($html) . "
                    you have been invited to topic \$TOPIC_TITLE$. Click \$LINK$ to view your pending invites." . self::newLine($html, 2) . "
                    Topics team" . self::newLine($html, 2) . "
                    #\$MAIL_ID$"
                ];

                break;

            case self::REGISTRATION_CONFIRMATION:
                $result = [
                    'New registration confirmation - Topics',
                    "Dear \$USER_NAME$," . self::newLine($html) . "
                    you have created a registration and it must be confirmed. " . self::newLine($html) . "
                    Please click \$LINK$ to confirm registration." . self::newLine($html, 2) . "
                    Topics team" . self::newLine($html, 2) . "
                    #\$MAIL_ID$"
                ];

                break;

            case self::FORGOTTEN_PASSWORD:
                $result = [
                    'Forgotten password - Topics',
                    "Dear \$USER_NAME$," . self::newLine($html) . "
                    you have requested resetting your password. Please click \$LINK$ to reset your password." . self::newLine($html, 2) . "
                    Topics team" . self::newLine($html, 2) . "
                    #\$MAIL_ID$"
                ];

                break;
        }

        return $result;
    }

    private static function newLine(bool $html, int $count = 1) {
        $value = $html ? '<br>' : "\r\n";

        if($count > 1) {
            $tmp = '';
            for($i = 0; $i < $count; $i++) {
                $tmp .= $value;
            }
            $value = $tmp;
        }

        return $value;
    }
}

?>