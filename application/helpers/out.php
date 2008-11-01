<?php
/**
 * Template text encoding / escaping shortcuts helper.
 *
 * @package OpenInterocitor
 * @author  l.m.orchard@pobox.com
 * @link    http://decafbad.com/
 * @license Share and Enjoy
 */

class out_Core
{
    /**
     * Escape a string for HTML inclusion.
     *
     * @param string content to escape
     * @return string HTML-encoded content
     */
    public static function H($s, $echo=TRUE) {
        return self::__(htmlentities($s, ENT_NOQUOTES, "UTF-8"), $echo);
    }

    /**
     * Encode a string for URL inclusion.
     *
     * @param string content to encode
     * @return string URL-encoded content
     */
    public static function U($s, $echo=TRUE) {
        return self::__(rawurlencode($s), $echo);
    }

    /**
     * JSON-encode a value
     *
     * @param mixed some data to be encoded
     * @return string JSON-encoded data
     */
    public static function JSON($s, $echo=TRUE) {
        return self::__(json_encode($s), $echo);
    }

    /**
     * Raw output / return of strings.
     */
    public static function __($out, $echo=TRUE) {
        if ($echo) echo $out;
        else return $out;
    }

}
