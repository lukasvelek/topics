<?php

namespace App\Constants;

class MailTemplates {
    public const NEW_TOPIC_INVITE = 1;

    public static function getTemplateData(int $template) {
        $result = [];

        switch($template) {
            case self::NEW_TOPIC_INVITE:
                $result = [
                    'New topic invite - Topics',
                    "Dear \$USER_NAME$,\r\n
                    you have been invited to topic \$TOPIC_TITLE$. Click \$LINK$ to view your pending invites.\r\n\r\n
                    Topics team"
                ];

                break;
        }

        return $result;
    }
}

?>