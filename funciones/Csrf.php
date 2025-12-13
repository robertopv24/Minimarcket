<?php

use Minimarcket\Core\Container;
use Minimarcket\Core\Security\CsrfToken;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Core\Security\CsrfToken instead.
 */
class Csrf
{
    private static function getService()
    {
        global $app;
        if (isset($app)) {
            return $app->getContainer()->get(CsrfToken::class);
        } else {
            return new CsrfToken();
        }
    }

    public static function getToken()
    {
        return self::getService()->getToken();
    }

    public static function insertTokenField()
    {
        return self::getService()->insertTokenField();
    }

    public static function validateToken()
    {
        return self::getService()->validateToken();
    }

    public static function validate($token)
    {
        return self::getService()->validate($token);
    }
}
