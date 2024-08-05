<?php

namespace App\Constants;

class MailTemplates {
    public const NEW_TOPIC_INVITE = 1;

    public static function getTemplateData(int $template, bool $html = true) {
        $result = [];

        switch($template) {
            case self::NEW_TOPIC_INVITE:
                $result = [
                    'New topic invite - Topics',
                    "Dear \$USER_NAME$," . ($html ? '<br>' : "\r\n") . "
                    you have been invited to topic \$TOPIC_TITLE$. Click \$LINK$ to view your pending invites." . ($html ? '<br><br>' : "\r\n\r\n") . "
                    Topics team"
                ];

                break;
        }

        return $result;
    }
}

?>