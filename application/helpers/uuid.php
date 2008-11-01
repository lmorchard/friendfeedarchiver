<?php
/**
 * Generate UUIDs
 *
 * @package OpenInterocitor
 * @author  l.m.orchard@pobox.com
 * @licence Share and Enjoy
 */
class uuid_Core
{

    /**
     * Produce a UUID per RFC 4122, version 4 
     * See also: http://us.php.net/manual/en/function.uniqid.php#69164
     */
    public static function uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
    }

}
