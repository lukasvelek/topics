<?php

namespace App\Exceptions;

use Throwable;

class BadCredentialsException extends AException {
    public function __construct(?string $userId, ?string $username, string $processName = 'authentication', ?Throwable $previous = null) {
        $userInfo = '';

        if($userId === null && $username === null) {
            throw new RequiredAttributeIsNotSetException('userId or username', 'BadCredentialsException', $previous);
        }

        if($userId !== null) {
            $userInfo = '#' . $userId;
        } else if($username !== null) {
            $userInfo = $username;
        }

        parent::__construct('BadCredentialsException', 'User ' . $userInfo . ' has entered bad credentials during ' . $processName . '.', $previous);
    }
}

?>