<?php

/**
 * This code is mix of WP and phpBB :)
 * */
class erLhcoreClassBBCode
{    
   /**
    * Callback to convert URI match to HTML A element.
    *
    * This function was backported from 2.5.0 to 2.3.2. Regex callback for make_clickable().
    *
    * @since 2.3.2
    * @access private
    *
    * @param array $matches Single Regex Match.
    * @return string HTML A element with URI address.
    */
   public static function _make_url_clickable_cb( $matches ) {
       $url = $matches[2];

       $parts = explode('&lt;',$url);

       $url = array_shift($parts);

       if ( ')' == $matches[3] && strpos( $url, '(' ) ) {
           // If the trailing character is a closing parethesis, and the URL has an opening parenthesis in it, add the closing parenthesis to the URL.
           // Then we can let the parenthesis balancer do its thing below.
           $url .= $matches[3];
           $suffix = '';
       } else {
           $suffix = $matches[3];
       }
       // Include parentheses in the URL only if paired
       while ( substr_count( $url, '(' ) < substr_count( $url, ')' ) ) {
           $suffix = strrchr( $url, ')' ) . $suffix;
           $url = substr( $url, 0, strrpos( $url, ')' ) );
       }

       if (str_ends_with($url,'&quot')) {
           $url = substr($url, 0, -5);
           $suffix = '&quot' . $suffix;
       }

       $url = self::esc_url($url);
       if ( empty($url) )
           return $matches[0];
       return $matches[1] . "<a href=\"$url\" class=\"link\" rel=\"noreferrer\" target=\"_blank\">$url</a>" . $suffix . (!empty($parts) ? '&lt;'.implode('', $parts) : '');
   }
   
   /**
    * Checks and cleans a URL.
    *
    * A number of characters are removed from the URL. If the URL is for displaying
    * (the default behaviour) ampersands are also replaced. The {@see 'clean_url'} filter
    * is applied to the returned cleaned URL.
    *
    * @since 2.8.0
    *
    * @param string $url       The URL to be cleaned.
    * @param array  $protocols Optional. An array of acceptable protocols.
    *		                    Defaults to return value of wp_allowed_protocols()
    * @param string $_context  Private. Use esc_url_raw() for database usage.
    * @return string The cleaned $url after the {@see 'clean_url'} filter is applied.
    */
   public static function esc_url( $url, $protocols = null, $_context = 'display' ) {
       $original_url = $url;
       if ( '' == $url )
           return $url;
       $url = str_replace( ' ', '%20', $url );
       $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url);
       if ( '' === $url ) {
           return $url;
       }
       if ( 0 !== stripos( $url, 'mailto:' ) ) {
           $strip = array('%0d', '%0a', '%0D', '%0A');
           $url = self::_deep_replace($strip, $url);
       }
       $url = str_replace(';//', '://', $url);
       /* If the URL doesn't appear to contain a scheme, we
        * presume it needs https:// prepended (unless a relative
        * link starting with /, # or ? or a php file).
       */
       if ( strpos($url, ':') === false && ! in_array( $url[0], array( '/', '#', '?' ) ) &&
           ! preg_match('/^[a-z0-9-]+?\.php/i', $url) )
               $url = 'https://' . $url;
           // Replace ampersands and single quotes only when displaying.
           if ( 'display' == $_context ) {
               $url = self::wp_kses_normalize_entities( $url );
               $url = str_replace( '&amp;', '&#038;', $url );
               $url = str_replace( "'", '&#039;', $url );
           }
           if ( ( false !== strpos( $url, '[' ) ) || ( false !== strpos( $url, ']' ) ) ) {
               $parsed = self::wp_parse_url( $url );
               $front  = '';
               if ( isset( $parsed['scheme'] ) ) {
                   $front .= $parsed['scheme'] . '://';
               } elseif ( '/' === $url[0] ) {
                   $front .= '//';
               }
               if ( isset( $parsed['user'] ) ) {
                   $front .= $parsed['user'];
               }
               if ( isset( $parsed['pass'] ) ) {
                   $front .= ':' . $parsed['pass'];
               }
               if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
                   $front .= '@';
               }
               if ( isset( $parsed['host'] ) ) {
                   $front .= $parsed['host'];
               }
               if ( isset( $parsed['port'] ) ) {
                   $front .= ':' . $parsed['port'];
               }
               $end_dirty = str_replace( $front, '', $url );
               $end_clean = str_replace( array( '[', ']' ), array( '%5B', '%5D' ), $end_dirty );
               $url       = str_replace( $end_dirty, $end_clean, $url );
           }
           if ( '/' === $url[0] ) {
               $good_protocol_url = $url;
           } else {
               if ( ! is_array( $protocols ) )
                   $protocols = $protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );

               $good_protocol_url = self::wp_kses_bad_protocol( $url, $protocols );
               if ( strtolower( $good_protocol_url ) != strtolower( $url ) )
                   return '';
           }

           /**
            * Filters a string cleaned and escaped for output as a URL.
            *
            * @since 2.3.0
            *
            * @param string $good_protocol_url The cleaned URL to be returned.
            * @param string $original_url      The URL prior to cleaning.
            * @param string $_context          If 'display', replace ampersands and single quotes only.
            */
           return $url;           
           //self::esc_url( $good_protocol_url, $original_url, $_context ); //apply_filters( 'erLhcoreClassBBCode::clean_url', $good_protocol_url, $original_url, $_context );
   }
   
   /**
    * A wrapper for PHP's parse_url() function that handles consistency in the return
    * values across PHP versions.
    *
    * PHP 5.4.7 expanded parse_url()'s ability to handle non-absolute url's, including
    * schemeless and relative url's with :// in the path. This function works around
    * those limitations providing a standard output on PHP 5.2~5.4+.
    *
    * Secondly, across various PHP versions, schemeless URLs starting containing a ":"
    * in the query are being handled inconsistently. This function works around those
    * differences as well.
    *
    * Error suppression is used as prior to PHP 5.3.3, an E_WARNING would be generated
    * when URL parsing failed.
    *
    * @since 4.4.0
    * @since 4.7.0 The $component parameter was added for parity with PHP's parse_url().
    *
    * @param string $url       The URL to parse.
    * @param int    $component The specific component to retrieve. Use one of the PHP
    *                          predefined constants to specify which one.
    *                          Defaults to -1 (= return all parts as an array).
    *                          @see http://php.net/manual/en/function.parse-url.php
    * @return mixed False on parse failure; Array of URL components on success;
    *               When a specific component has been requested: null if the component
    *               doesn't exist in the given URL; a string or - in the case of
    *               PHP_URL_PORT - integer when it does. See parse_url()'s return values.
    */
   public static function wp_parse_url( $url, $component = -1 ) {
       $to_unset = array();
       $url = strval( $url );
       if ( '//' === substr( $url, 0, 2 ) ) {
           $to_unset[] = 'scheme';
           $url = 'placeholder:' . $url;
       } elseif ( '/' === substr( $url, 0, 1 ) ) {
           $to_unset[] = 'scheme';
           $to_unset[] = 'host';
           $url = 'placeholder://placeholder' . $url;
       }
       $parts = @parse_url( $url );
       if ( false === $parts ) {
           // Parsing failure.
           return $parts;
       }
       // Remove the placeholder values.
       foreach ( $to_unset as $key ) {
           unset( $parts[ $key ] );
       }
       return self::_get_component_from_parsed_url_array( $parts, $component );
   }
   
   /**
    * Retrieve a specific component from a parsed URL array.
    *
    * @internal
    *
    * @since 4.7.0
    *
    * @param array|false $url_parts The parsed URL. Can be false if the URL failed to parse.
    * @param int    $component The specific component to retrieve. Use one of the PHP
    *                          predefined constants to specify which one.
    *                          Defaults to -1 (= return all parts as an array).
    *                          @see http://php.net/manual/en/function.parse-url.php
    * @return mixed False on parse failure; Array of URL components on success;
    *               When a specific component has been requested: null if the component
    *               doesn't exist in the given URL; a string or - in the case of
    *               PHP_URL_PORT - integer when it does. See parse_url()'s return values.
    */
   public static function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
       if ( -1 === $component ) {
           return $url_parts;
       }
       $key = self::_wp_translate_php_url_constant_to_key( $component );
       if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
           return $url_parts[ $key ];
       } else {
           return null;
       }
   }
   
   /**
    * Translate a PHP_URL_* constant to the named array keys PHP uses.
    *
    * @internal
    *
    * @since 4.7.0
    *
    * @see   http://php.net/manual/en/url.constants.php
    *
    * @param int $constant PHP_URL_* constant.
    * @return string|bool The named key or false.
    */
   public static function _wp_translate_php_url_constant_to_key( $constant ) {
       $translation = array(
           PHP_URL_SCHEME   => 'scheme',
           PHP_URL_HOST     => 'host',
           PHP_URL_PORT     => 'port',
           PHP_URL_USER     => 'user',
           PHP_URL_PASS     => 'pass',
           PHP_URL_PATH     => 'path',
           PHP_URL_QUERY    => 'query',
           PHP_URL_FRAGMENT => 'fragment',
       );
       if ( isset( $translation[ $constant ] ) ) {
           return $translation[ $constant ];
       } else {
           return false;
       }
   }
   
   
   /**
    * Converts and fixes HTML entities.
    *
    * This function normalizes HTML entities. It will convert `AT&T` to the correct
    * `AT&amp;T`, `&#00058;` to `&#58;`, `&#XYZZY;` to `&amp;#XYZZY;` and so on.
    *
    * @since 1.0.0
    *
    * @param string $string Content to normalize entities
    * @return string Content with normalized entities
    */
   public static function wp_kses_normalize_entities($string) {
       // Disarm all entities by converting & to &amp;
       $string = str_replace('&', '&amp;', $string);
       // Change back the allowed entities in our entity whitelist
       $string = preg_replace_callback('/&amp;([A-Za-z]{2,8}[0-9]{0,2});/', 'erLhcoreClassBBCode::wp_kses_named_entities', $string);
       $string = preg_replace_callback('/&amp;#(0*[0-9]{1,7});/', 'erLhcoreClassBBCode::wp_kses_normalize_entities2', $string);
       $string = preg_replace_callback('/&amp;#[Xx](0*[0-9A-Fa-f]{1,6});/', 'erLhcoreClassBBCode::wp_kses_normalize_entities3', $string);
       return $string;
   }
   
   /**
    * Callback for wp_kses_normalize_entities() for regular expression.
    *
    * This function helps wp_kses_normalize_entities() to only accept valid Unicode
    * numeric entities in hex form.
    *
    * @since 2.7.0
    * @access private
    *
    * @param array $matches preg_replace_callback() matches array
    * @return string Correctly encoded entity
    */
   public static function wp_kses_normalize_entities3($matches) {
       if ( empty($matches[1]) )
           return '';
       $hexchars = $matches[1];
       return ( ! self::valid_unicode( hexdec( $hexchars ) ) ) ? "&amp;#x$hexchars;" : '&#x'.ltrim($hexchars,'0').';';
   }
   
   /**
    * Helper function to determine if a Unicode value is valid.
    *
    * @since 2.7.0
    *
    * @param int $i Unicode value
    * @return bool True if the value was a valid Unicode number
    */
   public static function valid_unicode($i) {
       return ( $i == 0x9 || $i == 0xa || $i == 0xd ||
           ($i >= 0x20 && $i <= 0xd7ff) ||
           ($i >= 0xe000 && $i <= 0xfffd) ||
           ($i >= 0x10000 && $i <= 0x10ffff) );
   }
   
   /**
    * Callback for wp_kses_normalize_entities() regular expression.
    *
    * This function helps wp_kses_normalize_entities() to only accept 16-bit
    * values and nothing more for `&#number;` entities.
    *
    * @access private
    * @since 1.0.0
    *
    * @param array $matches preg_replace_callback() matches array
    * @return string Correctly encoded entity
    */
   public static function wp_kses_normalize_entities2($matches) {
       if ( empty($matches[1]) )
           return '';
       $i = $matches[1];
       if (self::valid_unicode($i)) {
           $i = str_pad(ltrim($i,'0'), 3, '0', STR_PAD_LEFT);
           $i = "&#$i;";
       } else {
           $i = "&amp;#$i;";
       }
       return $i;
   }
   
   /**
    * Callback for wp_kses_normalize_entities() regular expression.
    *
    * This function only accepts valid named entity references, which are finite,
    * case-sensitive, and highly scrutinized by HTML and XML validators.
    *
    * @since 3.0.0
    *
    * @global array $allowedentitynames
    *
    * @param array $matches preg_replace_callback() matches array
    * @return string Correctly encoded entity
    */
   public static function wp_kses_named_entities($matches) {       
       $allowedentitynames = array(
           'nbsp',    'iexcl',  'cent',    'pound',  'curren', 'yen',
           'brvbar',  'sect',   'uml',     'copy',   'ordf',   'laquo',
           'not',     'shy',    'reg',     'macr',   'deg',    'plusmn',
           'acute',   'micro',  'para',    'middot', 'cedil',  'ordm',
           'raquo',   'iquest', 'Agrave',  'Aacute', 'Acirc',  'Atilde',
           'Auml',    'Aring',  'AElig',   'Ccedil', 'Egrave', 'Eacute',
           'Ecirc',   'Euml',   'Igrave',  'Iacute', 'Icirc',  'Iuml',
           'ETH',     'Ntilde', 'Ograve',  'Oacute', 'Ocirc',  'Otilde',
           'Ouml',    'times',  'Oslash',  'Ugrave', 'Uacute', 'Ucirc',
           'Uuml',    'Yacute', 'THORN',   'szlig',  'agrave', 'aacute',
           'acirc',   'atilde', 'auml',    'aring',  'aelig',  'ccedil',
           'egrave',  'eacute', 'ecirc',   'euml',   'igrave', 'iacute',
           'icirc',   'iuml',   'eth',     'ntilde', 'ograve', 'oacute',
           'ocirc',   'otilde', 'ouml',    'divide', 'oslash', 'ugrave',
           'uacute',  'ucirc',  'uuml',    'yacute', 'thorn',  'yuml',
           'quot',    'amp',    'lt',      'gt',     'apos',   'OElig',
           'oelig',   'Scaron', 'scaron',  'Yuml',   'circ',   'tilde',
           'ensp',    'emsp',   'thinsp',  'zwnj',   'zwj',    'lrm',
           'rlm',     'ndash',  'mdash',   'lsquo',  'rsquo',  'sbquo',
           'ldquo',   'rdquo',  'bdquo',   'dagger', 'Dagger', 'permil',
           'lsaquo',  'rsaquo', 'euro',    'fnof',   'Alpha',  'Beta',
           'Gamma',   'Delta',  'Epsilon', 'Zeta',   'Eta',    'Theta',
           'Iota',    'Kappa',  'Lambda',  'Mu',     'Nu',     'Xi',
           'Omicron', 'Pi',     'Rho',     'Sigma',  'Tau',    'Upsilon',
           'Phi',     'Chi',    'Psi',     'Omega',  'alpha',  'beta',
           'gamma',   'delta',  'epsilon', 'zeta',   'eta',    'theta',
           'iota',    'kappa',  'lambda',  'mu',     'nu',     'xi',
           'omicron', 'pi',     'rho',     'sigmaf', 'sigma',  'tau',
           'upsilon', 'phi',    'chi',     'psi',    'omega',  'thetasym',
           'upsih',   'piv',    'bull',    'hellip', 'prime',  'Prime',
           'oline',   'frasl',  'weierp',  'image',  'real',   'trade',
           'alefsym', 'larr',   'uarr',    'rarr',   'darr',   'harr',
           'crarr',   'lArr',   'uArr',    'rArr',   'dArr',   'hArr',
           'forall',  'part',   'exist',   'empty',  'nabla',  'isin',
           'notin',   'ni',     'prod',    'sum',    'minus',  'lowast',
           'radic',   'prop',   'infin',   'ang',    'and',    'or',
           'cap',     'cup',    'int',     'sim',    'cong',   'asymp',
           'ne',      'equiv',  'le',      'ge',     'sub',    'sup',
           'nsub',    'sube',   'supe',    'oplus',  'otimes', 'perp',
           'sdot',    'lceil',  'rceil',   'lfloor', 'rfloor', 'lang',
           'rang',    'loz',    'spades',  'clubs',  'hearts', 'diams',
           'sup1',    'sup2',   'sup3',    'frac14', 'frac12', 'frac34',
           'there4',
       );
       
       if ( empty($matches[1]) )
           return '';
       $i = $matches[1];
       return ( ! in_array( $i, $allowedentitynames ) ) ? "&amp;$i;" : "&$i;";
   }
   
   /**
    * Sanitize string from bad protocols.
    *
    * This function removes all non-allowed protocols from the beginning of
    * $string. It ignores whitespace and the case of the letters, and it does
    * understand HTML entities. It does its work in a while loop, so it won't be
    * fooled by a string like "javascript:javascript:alert(57)".
    *
    * @since 1.0.0
    *
    * @param string $string            Content to filter bad protocols from
    * @param array  $allowed_protocols Allowed protocols to keep
    * @return string Filtered content
    */
   public static function wp_kses_bad_protocol($string, $allowed_protocols) {
       $string = self::wp_kses_no_null($string);
       $iterations = 0;
       do {
           $original_string = $string;
           $string = self::wp_kses_bad_protocol_once($string, $allowed_protocols);
       } while ( $original_string != $string && ++$iterations < 6 );
       if ( $original_string != $string )
           return '';
       return $string;
   }
   
   /**
    * Removes any invalid control characters in $string.
    *
    * Also removes any instance of the '\0' string.
    *
    * @since 1.0.0
    *
    * @param string $string
    * @param array $options Set 'slash_zero' => 'keep' when '\0' is allowed. Default is 'remove'.
    * @return string
    */
   public static function wp_kses_no_null( $string, $options = null ) {
       if ( ! isset( $options['slash_zero'] ) ) {
           $options = array( 'slash_zero' => 'remove' );
       }
       $string = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $string );
       if ( 'remove' == $options['slash_zero'] ) {
           $string = preg_replace( '/\\\\+0+/', '', $string );
       }
       return $string;
   }
   
   /**
    * Sanitizes content from bad protocols and other characters.
    *
    * This function searches for URL protocols at the beginning of $string, while
    * handling whitespace and HTML entities.
    *
    * @since 1.0.0
    *
    * @param string $string            Content to check for bad protocols
    * @param string $allowed_protocols Allowed protocols
    * @return string Sanitized content
    */
   public static function wp_kses_bad_protocol_once($string, $allowed_protocols, $count = 1 ) {
       $string2 = preg_split( '/:|&#0*58;|&#x0*3a;/i', $string, 2 );
       if ( isset($string2[1]) && ! preg_match('%/\?%', $string2[0]) ) {
           $string = trim( $string2[1] );
           $protocol = self::wp_kses_bad_protocol_once2( $string2[0], $allowed_protocols );
           if ( 'feed:' == $protocol ) {
               if ( $count > 2 )
                   return '';
               $string = self::wp_kses_bad_protocol_once( $string, $allowed_protocols, ++$count );
               if ( empty( $string ) )
                   return $string;
           }
           $string = $protocol . $string;
       }
       return $string;
   }
   
   
   /**
    * Callback for wp_kses_bad_protocol_once() regular expression.
    *
    * This function processes URL protocols, checks to see if they're in the
    * whitelist or not, and returns different data depending on the answer.
    *
    * @access private
    * @since 1.0.0
    *
    * @param string $string            URI scheme to check against the whitelist
    * @param string $allowed_protocols Allowed protocols
    * @return string Sanitized content
    */
   public static function wp_kses_bad_protocol_once2( $string, $allowed_protocols ) {
       $string2 = self::wp_kses_decode_entities($string);
       $string2 = preg_replace('/\s/', '', $string2);
       $string2 = self::wp_kses_no_null($string2);
       $string2 = strtolower($string2);
       $allowed = false;
       foreach ( (array) $allowed_protocols as $one_protocol )
           if ( strtolower($one_protocol) == $string2 ) {
               $allowed = true;
               break;
           }
       if ($allowed)
           return "$string2:";
       else
           return '';
   }
   
   /**
    * Convert all entities to their character counterparts.
    *
    * This function decodes numeric HTML entities (`&#65;` and `&#x41;`).
    * It doesn't do anything with other entities like &auml;, but we don't
    * need them in the URL protocol whitelisting system anyway.
    *
    * @since 1.0.0
    *
    * @param string $string Content to change entities
    * @return string Content after decoded entities
    */
   public static function wp_kses_decode_entities($string) {
       $string = preg_replace_callback('/&#([0-9]+);/', 'erLhcoreClassBBCode::_wp_kses_decode_entities_chr', $string);
       $string = preg_replace_callback('/&#[Xx]([0-9A-Fa-f]+);/', 'erLhcoreClassBBCode::_wp_kses_decode_entities_chr_hexdec', $string);
       return $string;
   }
   
   /**
    * Regex callback for wp_kses_decode_entities()
    *
    * @since 2.9.0
    *
    * @param array $match preg match
    * @return string
    */
   public static function _wp_kses_decode_entities_chr( $match ) {
       return chr( $match[1] );
   }
   /**
    * Regex callback for wp_kses_decode_entities()
    *
    * @since 2.9.0
    *
    * @param array $match preg match
    * @return string
    */
   public static function _wp_kses_decode_entities_chr_hexdec( $match ) {
       return chr( hexdec( $match[1] ) );
   }
   

   /**
    * Perform a deep string replace operation to ensure the values in $search are no longer present
    *
    * Repeats the replacement operation until it no longer replaces anything so as to remove "nested" values
    * e.g. $subject = '%0%0%0DDD', $search ='%0D', $result ='' rather than the '%0%0DD' that
    * str_replace would return
    *
    * @since 2.8.1
    * @access private
    *
    * @param string|array $search  The value being searched for, otherwise known as the needle.
    *                              An array may be used to designate multiple needles.
    * @param string       $subject The string being searched and replaced on, otherwise known as the haystack.
    * @return string The string with the replaced svalues.
    */
   public static function _deep_replace( $search, $subject ) {
       $subject = (string) $subject;
       $count = 1;
       while ( $count ) {
           $subject = str_replace( $search, '', $subject, $count );
       }
       return $subject;
   }

   public static $replaceEmoji = array(
       ':\)' => "\u{1F642}",
       ':D:' => "\u{1F600}",
       ':D' => "\u{1F600}",
       ':\(' => "\u{1F641}",
       ':o:' => "\u{1F62E}",
       ':o' => "\u{1F62E}",
       ':p:' => "\u{1F61B}",
       ':p' => "\u{1F61B}",
       ';\)' => "\u{1F609}",
       ';\(' => "\u{1F622}",
       ':x' => "\u{1F910}",
       ':\*' => "\u{1F617}",
       ';\*' => "\u{1F618}",
       ':\/' => "\u{1F615}"
   );

   public static function parseEmoji($text) {

       // Smileys to find...
       for ($i = 0; $i < 2; $i++) {
           $text = preg_replace_callback('/(^|\s)(' .implode("|",array_keys(self::$replaceEmoji)) . ')(\s|$)/', function($match) {
               $char = str_replace(array('/','(',')','*'),array('\/','\(','\)','\*'),$match[2]);
               return $match[1] . erLhcoreClassBBCode::$replaceEmoji[$char] . $match[3];
           },  $text);
       }

       return $text;
   }

   public static function BBCode2Html($text, $paramsMessage = []) {

       if (!isset($paramsMessage['keep_nl']) || $paramsMessage['keep_nl'] === false) {
           $text = trim($text);
       }

       $text = self::parseEmoji($text);

       // Smileys to find...
       $in = array(
       );

       // And replace them by...
       $out = array(
       );

       if (self::isBBCodeTagSupported('[li]', $paramsMessage)) {
           $in[] = '[/*]';
           $in[] = '[*]';
           $out[] = '</li>';
           $out[] = '<li>';

           $text = str_replace($in, $out, $text);
       }

       if ((strpos($text,'##') !== false || strpos($text,'__') !== false || strpos($text,'**') !== false) && class_exists('Michelf\Markdown')){
           $text = trim(\LiveHelperChat\Helpers\LHCMarkdown::defaultTransform($text));
       }

    	// BBCode to find...
    	$in = array( 	  '/\[b\](.*?)\[\/b\]/ms' => '[b]',
    					 '/\[i\](.*?)\[\/i\]/ms' => '[i]',
    					 '/\[u\](.*?)\[\/u\]/ms' => '[u]',
    					 '/\[mark\](.*?)\[\/mark\]/ms' => '[mark]',
    					 '/\[s\](.*?)\[\/s\]/ms' => '[s]',
    					 '/\[list\=([0-9]+)\](.*?)\[\/list\]/ms' => '[listol]',
    					 '/\[list\](.*?)\[\/list\]/ms' => '[list]',
    					 '/\[\*\]\s?(.*?)\n/ms' => '[li]',
    					 '/\[fs([0-9]+)\](.*?)\[\/fs\]/ms' => '[fs12]',
    					 '/\[color\=([A-Za-z0-9]{2,6})\](.*?)\[\/color\]/ms' => '[color]',
    					 '/\[level\=([A-Za-z0-9\-\s]{2,60})\](.*?)\[\/level\]/ms' => '[level]'
    	);

       // And replace them by...
       $out = array(   '[b]' => '<strong>\1</strong>',
                       '[i]' => '<em>\1</em>',
                       '[u]' => '<u>\1</u>',
                       '[mark]' => '<mark>\1</mark>',
                       '[s]' => '<strike>\1</strike>',
                       '[listol]' => '<ol class="default-list" start="\1">\2</ol>',
                       '[list]' => '<ul class="default-list" >\1</ul>',
                       '[li]' => '<li>\1</li>',
                       '[fs12]' => '<span style="font-size:\1pt">\2</span>',
                       '[color]' => '<span style="color:#\1">\2</span>',
                       '[level]' => '<span class="\1">\2</span>'
       );


        $filteredBBCode = ['search' => [],'replace' => []];
        foreach ($in as $matchRule => $bbCode) {
            if (self::isBBCodeTagSupported($bbCode, $paramsMessage)) {
                $filteredBBCode['search'][] = $matchRule;
                $filteredBBCode['replace'][] = $out[$bbCode];
            }
        }

    	$text = preg_replace($filteredBBCode['search'], $filteredBBCode['replace'], $text);

    	// paragraphs
    	$text = nl2br($text);

    	// clean some tags to remain strict
    	// not very elegant, but it works. No time to do better ;)
    	if (!function_exists('removeBr')) {
    		function removeBr($s) {
    			return str_replace("<br />", "", $s[0]);
    		}
    	}

    	$text = preg_replace_callback('/<pre>(.*?)<\/pre>/ms', "removeBr", $text);
    	$text = preg_replace_callback('/<code>(.*?)<\/code>/ms', "removeBr", $text);
    	$text = preg_replace('/<p><pre>(.*?)<\/pre><\/p>/ms', "<pre>\\1</pre>", $text);

    	$text = preg_replace_callback('/<ul>(.*?)<\/ul>/ms', "removeBr", $text);
    	$text = preg_replace('/<p><ul>(.*?)<\/ul><\/p>/ms', "<ul>\\1</ul>", $text);

    	return $text;
    }

    public static function _make_url_embed_image($matches) {

        $in = htmlspecialchars_decode($matches[2]);
        $in = trim($in);

        $url = self::esc_url($in);
        if ( empty($url) )
            return '[img]' . $matches[2] . '[img]';

        $prepend = '';
        $append = '';
        if (isset($matches[1]) && $matches[1] != '' && !empty(self::esc_url($matches[1]))) {
            $prepend = '<a class="link" rel="noreferrer" target="_blank" href="' . $matches[1] . '">';
            $append = '</a>';
        }

        $hash = md5(trim($matches[2] . (isset($matches[1]) ? $matches[1] : '')));
        self::$plainHash['embedimg'.$hash] = "<div class=\"img_embed\">{$prepend}<img title=\"\" onclick='lhinst.zoomImage(this)' class='action-image img-fluid img-remote' src=\"".$url."\" alt=\"\" />{$append}</div>";

        return 'embedimg'.$hash;
   }

   public static function _make_embed_map($matches)
   {
       $parts = explode(',',trim($matches[1]));

       if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
           $id = rand(0,1000) . time();
           return "<div id='msg-location-".$id."' style='height:300px'><script>lhinst.showMessageLocation(" . $id . "," . (float)$parts[0] . "," . (float)$parts[1] . ")</script></div>";
       }

       return ;
   }

   public static function _make_url_embed($matches){

        $in = str_replace('"','',htmlspecialchars_decode($matches[1]));
        $in = trim($in);
        
        $url = self::esc_url($in);
        if ( empty($url) )
            return '[url='.$matches[1].']' . $matches[2] . '[/url]';

       $hash = md5(trim($matches[1] . $matches[2]));
       self::$plainHash['link'.$hash] = '<a class="link" target="_blank" rel="noreferrer" href="'.$url.'">' . $matches[2] . '</a>';
       return 'link'.$hash;
   }
   
   public static function _make_code($matches){
        $hash = md5(trim($matches[1]));
        self::$plainHash['codeblock'.$hash] = '<pre class="blockquote blockquote-code"><code>' . trim($matches[1]) . '</code></pre>';
        return 'codeblock'.$hash;
   }

   public static function _make_code_plain($matches) {
        $hash = md5(trim($matches[1]));
        self::$plainHash['code'.$hash] = '<code>' . trim($matches[1]) . '</code>';
        return 'code'.$hash;
   }

   /**
    * Callback to convert URL match to HTML A element.
    *
    * This function was backported from 2.5.0 to 2.3.2. Regex callback for make_clickable().
    *
    * @since 2.3.2
    * @access private
    *
    * @param array $matches Single Regex Match.
    * @return string HTML A element with URL address.
    */
   public static function _make_web_ftp_clickable_cb( $matches ) {
       $ret = '';
       $dest = $matches[2];
       $dest = 'https://' . $dest;
       // removed trailing [.,;:)] from URL
       if ( in_array( substr($dest, -1), array('.', ',', ';', ':', ')') ) === true ) {
           $ret = substr($dest, -1);
           $dest = substr($dest, 0, strlen($dest)-1);
       }
       $dest = self::esc_url($dest);
       if ( empty($dest) )
           return $matches[0];
       
       return $matches[1] . "<a href=\"$dest\" class=\"link\" rel=\"noreferrer\" target=\"_blank\">$dest</a>$ret";
   }
   
   /**
     * Callback to convert email address match to HTML A element.
     *
     * This function was backported from 2.5.0 to 2.3.2. Regex callback for make_clickable().
     *
     * @since 2.3.2
     * @access private
     *
     * @param array $matches Single Regex Match.
     * @return string HTML A element with email address.
     */
   public static function _make_email_clickable_cb( $matches ) {
    	$email = $matches[2] . '@' . $matches[3];
    	return $matches[1] . "<a rel=\"noreferrer\" target=\"_blank\" class=\"link\" href=\"mailto:$email\">$email</a>";
   }

   
   public static function _make_paypal_button($matches){

         if (filter_var($matches[1],FILTER_VALIDATE_EMAIL)) {
            return '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
            <input type="hidden" name="cmd" value="_donations">
            <input type="hidden" name="business" value="'.$matches[1].'">
            <input type="hidden" name="lc" value="US">
            <input type="hidden" name="no_note" value="0">
            <input type="hidden" name="currency_code" value="USD">
            <input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_SM.gif:NonHostedGuest">
            <input type="image" title="Support an artist" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
            <img alt="" border="0" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/en_US/i/scr/pixel.gif" width="1" height="1">
            </form>';
        } else {
            return $matches[0];
        }
   }

   private static $plainHash = [];

   public static function _make_plain_text($matches)
   {
       $key = 'plain_'.md5($matches[0]);
       self::$plainHash[$key] = $matches[1];
       return $key;
   }

   public static function _date_format($matches)
   {
       return date($matches[1], (int)$matches[2]);
   }

   public static function _make_button_action($matches) {
        return "<button type=\"button\" class=\"btn btn-xs text-white fs13 btn-secondary\" onclick=\"lhinst.buttonAction($(this),'" . htmlspecialchars(strip_tags($matches[1])) . "')\">" . htmlspecialchars($matches[2]) . "</button>";
   }

   public static function _make_link_trigger($matches) {
       return "<a class=\"action-image link-trigger-button\" data-id=\"{msg_id}\" data-payload=\"".htmlspecialchars(strip_tags($matches[1]))."\" onclick='lhinst.updateTriggerClicked(\"".htmlspecialchars(strip_tags($matches[1]))."\",{msg_id},$(this))'>" . htmlspecialchars($matches[2]) . "</a>";
   }

   public static function _make_youtube_block($matches) {

         $data = parse_url($matches[1]);

         if (isset($data['query'])){
             parse_str($data['query'],$query);
             if (stristr($data['host'],'youtube.com') && isset($query['v']) && ($query['v'] != '')) {
                 return '<iframe class="w-100 h-100" title="YouTube video player" width="480" height="300" src="https://www.youtube.com/embed/'.urlencode($query['v']).'" frameborder="0" allowfullscreen></iframe>';
             } else {
                 return $matches[0];
             }
         } else {
             return $matches[0];
         }
   }

   public static function getHost() {
        return erLhcoreClassSystem::getHost();
   }

   public static function _make_upload_link($matches){
       $data = $matches[1];
       return '<a class="action-image alert-link text-decoration-underline" onclick="lhinst.chooseFile()">' . htmlspecialchars($data) . '</a>';
   }

   public static function _make_base_link($matches) {
       $data = htmlspecialchars($matches[1]);
       $url = self::getHost() . erLhcoreClassDesign::baseurl($data);
       return  $url;
   }

   public static function _make_url_file($matches, $paramsMessage  = [])
   {
   		if (isset($matches[1])){
   		    $mainData = explode(' ',$matches[1]);
   		    $parts = explode('_',$mainData[0]);
   			$fileID = $parts[0];
   			$hash = isset($parts[1]) ? $parts[1] : null;
   			$displayType = isset($parts[2]) ? $parts[2] : null;
   			try {
   				$file = erLhcoreClassModelChatFile::fetch($fileID);

   				if (is_object($file)) {

                    $URLHash = '';
                    if ($file->chat_id > 0 && !isset($paramsMessage['operator_render'])) {
                        $tsHash = time();
                        $temporaryHash = sha1($file->id . '_' . $file->hash . '_' . $tsHash . '_' . erConfigClassLhConfig::getInstance()->getSetting( 'site', 'secrethash' ));
                        $URLHash = "/(vhash)/{$temporaryHash}/(vts)/{$tsHash}";
                    }

                    // Check that user has permission to see the chat. Let say if user purposely types file bbcode
                    $disableZoom = false;
                    if ($hash == $file->security_hash) {
                        $fileExtension = strtolower($file->extension);
                        if (in_array($fileExtension,['jfif','jpg','jpeg','png','gif','webp'])){
                            // Make link if required
                            $prepend = '';
                            $append = '';
                            if (isset($mainData[1])) {
                                $subpartParts = explode('=',$mainData[1]);
                                if ($subpartParts[0] == 'link' || $subpartParts[0] == 'linkdirect') {
                                    if (!isset($subpartParts[1])) {
                                        $prepend = '<a class="link" rel="noreferrer" target="_blank" href="'. self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}/(inline)/true{$URLHash}\">";
                                        $append = '</a>';
                                        $disableZoom = true;

                                        if ($subpartParts[0] == 'linkdirect') {
                                            return"<a href=\"" . self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}{$URLHash}\" target=\"_blank\" rel=\"noreferrer\" class=\"link\" >" . erTranslationClassLhTranslation::getInstance()->getTranslation('file/file', 'Download file') . ' - ' . htmlspecialchars($file->upload_name) . ' [' . $file->extension . ']' . "</a>";
                                        }

                                    } else {
                                        $url = self::esc_url($subpartParts[1]);
                                        if ($url != ''){
                                            $prepend = '<a class="link" rel="noreferrer" target="_blank" href="' . self::esc_url($subpartParts[1]) . '">';
                                            $append = '</a>';
                                            $disableZoom = true;
                                        }
                                    }
                                }
                            } else {
                                $prepend = '<div class="position-relative">';
                                $append = '<a class="hidden-download" target="_blank" rel="noreferrer" href="'. self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}{$URLHash}".'/(inline)/true"></a></div>';
                            }

                            $imageSizeAttr = '';
                            $requireVerification = true;
                            if (in_array($fileExtension,['jfif','jpg','jpeg','png'])) {
                                $width = $file->width > 0 ? $file->width : 0;
                                $height = $file->height > 0 ? $file->height : 0;

                                if ($width == 0 || $height == 0) {
                                    list($width, $height) = getimagesize($file->file_path_server);
                                }

                                if ($width > 0 && $height > 0 && $width < 10000 && $height < 10000) {
                                    $imageSizeAttr = ' width="'.(int)$width.'" height="'.(int)$height.'" ';
                                }


                                if (isset($paramsMessage['img_verify_min_dim'])) {
                                    $minDim = (int)$paramsMessage['img_verify_min_dim'];
                                    $metaMsgArray = $file->meta_msg_array;

                                    if (
                                        ($width < $minDim && $height < $minDim) ||
                                        (isset($metaMsgArray['verified']['success']) && $metaMsgArray['verified']['success'] === true && (!isset($metaMsgArray['verified']['sensitive']) || $metaMsgArray['verified']['sensitive'] === false))
                                    ) {
                                        $requireVerification = false;
                                    }
                                }
                            }

                            if ($requireVerification == true && (!isset($paramsMessage['print_admin']) || $paramsMessage['print_admin'] === false) && isset($paramsMessage['download_policy']) && $paramsMessage['download_policy'] !== 0 && isset($paramsMessage['operator_render']) && $paramsMessage['operator_render'] === true && isset($paramsMessage['sender']) && $paramsMessage['sender'] === 0) {
                                return "<lhc-image download_policy={$paramsMessage['download_policy']} {$imageSizeAttr} file_id=\"{$file->id}\" id=\"img-reveal-holder-{$file->id}\" hash=\"{$hash}\" title=\"".htmlspecialchars($file->upload_name)."\" disable_zoom=\"".($disableZoom ? 'true' : 'false')."\"></lhc-image>";
                            } else {
                                if (isset($displayType) && $displayType == 'rawimg') {
                                    return '<img onclick="lhinst.zoomImage(this)" '.$imageSizeAttr.' id="img-file-' . $file->id . '" title="'.htmlspecialchars($file->upload_name).'" class="action-image img-fluid" src="' . self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}{$URLHash}" . '" alt="'.htmlspecialchars($file->upload_name).'" />';
                                } else {
                                    return $prepend . '<img ' . ($disableZoom === false ? 'onclick="lhinst.zoomImage(this)"' : '') . $imageSizeAttr . ' id="img-file-' . $file->id . '" title="'.htmlspecialchars($file->upload_name).'" class="action-image img-fluid" src="' . self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}{$URLHash}" . '" alt="'.htmlspecialchars($file->upload_name).'" />' . $append;
                                }
                            }
                        }

                        $audio = '';
                        if (in_array($fileExtension,['mp3','wav','ogg','oga','m4a'])) {
                            return '<a rel="noreferrer" class="hidden-download audio-download" href="'. self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}{$URLHash}".'"></a><audio preload="none" style="width: 230px" controls><source src="' . self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}" . '" type="' . $file->type . '"></audio>';
                        } elseif (in_array($fileExtension,['mp4','avi','mov','ogg','3gpp'])) {
                            $audio = '<br><div class="embed-responsive embed-responsive-16by9"><video class="embed-responsive-item" controls><source src="' . self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}{$URLHash}" . '"></video></div>';
                        }

                        return "<a href=\"" . self::getHost() . erLhcoreClassDesign::baseurl('file/downloadfile') . "/{$file->id}/{$hash}{$URLHash}\" target=\"_blank\" rel=\"noreferrer\" class=\"link\" >" . erTranslationClassLhTranslation::getInstance()->getTranslation('file/file', 'Download file') . ' - ' . htmlspecialchars($file->upload_name) . ' [' . $file->extension . ']' . "</a>" . $audio;
                    }
                }

   			} catch (Exception $e) {

   			}

   			return '';
   		}
   		return '';
   }

   public static function _make_url_survey($matches)
   {
       if (isset($matches[1])){
                  
           list($surveyId, $surveyItemId) = explode('_',str_replace(array('"','&quot;'),'', $matches[1]));
           
           try {
                             
               if (is_numeric($surveyItemId) && is_numeric($surveyId)) {

                   $surveyItem = erLhAbstractModelSurveyItem::fetch($surveyItemId);

                   if ($surveyId == $surveyItem->survey_id) 
                   {
                       $survey = erLhAbstractModelSurvey::fetch($surveyId);
                       return "<a href=\"" . self::getHost()  . erLhcoreClassDesign::baseurl('survey/collected')."/{$survey->id}?show={$surveyItem->id}\" target=\"_blank\" rel=\"noreferrer\" class=\"link\" >" . erTranslationClassLhTranslation::getInstance()->getTranslation('file/file','Collected survey data') . ' - ' . htmlspecialchars($survey->name) . "</a>";
                   }
               }
               
           } catch (Exception $e) {
       
           }
       
           return '';
       }
       return '';
   }
   
   public static function _make_url_mail_file($matches){

   		if (isset($matches[1])){
   			list($fileID,$hash) = explode('_',$matches[1]);
   			try {
   				$file = erLhcoreClassModelChatFile::fetch($fileID);

   				// Check that user has permission to see the chat. Let say if user purposely types file bbcode
   				if ($hash == $file->security_hash) {
   					return self::getHost() . erLhcoreClassDesign::baseurldirect('file/downloadfile')."/{$file->id}/{$hash}";
   				}
   			} catch (Exception $e) {

   			}

   			return '';
   		}
   		return '';
   }

   public static function _split_str_by_whitespace( $string, $goal ) {
        $chunks = array();

        $string_nullspace = strtr( $string, "\r\n\t\v\f ", "\000\000\000\000\000\000" );

        while ( $goal < strlen( $string_nullspace ) ) {
            $pos = strrpos( substr( $string_nullspace, 0, $goal + 1 ), "\000" );

            if ( false === $pos ) {
                $pos = strpos( $string_nullspace, "\000", $goal + 1 );
                if ( false === $pos ) {
                    break;
                }
            }

            $chunks[] = substr( $string, 0, $pos + 1 );
            $string = substr( $string, $pos + 1 );
            $string_nullspace = substr( $string_nullspace, $pos + 1 );
        }

        if ( $string ) {
            $chunks[] = $string;
        }

        return $chunks;
    }

   // https://github.com/WordPress/WordPress/blob/6e5e29c5bf49ad2be6a2c3a3d4fb3f5af6853b5b/wp-includes/formatting.php
   public static function make_clickable_text( $text ) {
       $r = '';
       $textarr = preg_split( '/(<[^<>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE ); // split out HTML tags
       $nested_code_pre = 0; // Keep track of how many levels link is nested inside <pre> or <code>
       foreach ( $textarr as $piece ) {
           if ( preg_match( '|^<code[\s>]|i', $piece ) || preg_match( '|^<pre[\s>]|i', $piece ) || preg_match( '|^<script[\s>]|i', $piece ) || preg_match( '|^<style[\s>]|i', $piece ) )
               $nested_code_pre++;
           elseif ( $nested_code_pre && ( '</code>' === strtolower( $piece ) || '</pre>' === strtolower( $piece ) || '</script>' === strtolower( $piece ) || '</style>' === strtolower( $piece ) ) )
           $nested_code_pre--;
           if ( $nested_code_pre || empty( $piece ) || ( $piece[0] === '<' && ! preg_match( '|^<\s*[\w]{1,20}+://|', $piece ) ) ) {
               $r .= $piece;
               continue;
           }
           // Long strings might contain expensive edge cases ...
           if ( 10000 < strlen( $piece ) ) {
               // ... break it up
               foreach ( self::_split_str_by_whitespace( $piece, 2100 ) as $chunk ) { // 2100: Extra room for scheme and leading and trailing paretheses
                   if ( 2101 < strlen( $chunk ) ) {
                       $r .= $chunk; // Too big, no whitespace: bail.
                   } else {
                       $r .= self::make_clickable_text( $chunk );
                   }
               }
           } else {
               $ret = " $piece "; // Pad with whitespace to simplify the regexes
               $url_clickable = '~
				([\\s(<.,;:!?])                                        # 1: Leading whitespace, or punctuation
				(                                                      # 2: URL
					[\\w]{1,20}+://                                # Scheme and hier-part prefix
					(?=\S{1,2000}\s)                               # Limit to URLs less than about 2000 characters long
					[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]*+         # Non-punctuation URL character
					(?:                                            # Unroll the Loop: Only allow puctuation URL character if followed by a non-punctuation URL character
						[\'.,;:!?)]                            # Punctuation URL character
						[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]++ # Non-punctuation URL character
					)*
				)
				(\)?)                                                  # 3: Trailing closing parenthesis (for parethesis balancing post processing)
			~xS'; // The regex is a non-anchored pattern and does not have a single fixed starting character.

               // Tell PCRE to spend more time optimizing since, when used on a page load, it will probably be used several times.
               $ret = preg_replace_callback( $url_clickable, 'erLhcoreClassBBCode::_make_url_clickable_cb', $ret );
               $ret = preg_replace_callback( '#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', 'erLhcoreClassBBCode::_make_web_ftp_clickable_cb', $ret );
               $ret = preg_replace_callback( '#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', 'erLhcoreClassBBCode::_make_email_clickable_cb', $ret );
               $ret = substr( $ret, 1, -1 ); // Remove our whitespace padding.
               $r .= $ret;
           }
       }
       
       // Cleanup of accidental links within links
       return preg_replace( '#(<a([ \r\n\t]+[^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i', "$1$3</a>", $r );
   }

   public static function makeQuote($matches)
   {
       if (isset($matches[2])) {
           return '<blockquote class="blockquote"> ' . $matches[2] . ' </blockquote>';
       } else if ($matches[1]) {
           return '<blockquote class="blockquote"> ' . $matches[1] . ' </blockquote>';
       } else {
           return $matches[0];
       }
   }

   public static function extractMetaByMessage(& $msg) {
       $meta = array();
       if ($msg != "" && strpos($msg,'[html_snippet]') !== false) {
           $matches = array();
           preg_match_all('/\[html_snippet\](.*?)\[\/html_snippet\]/is',$msg,$matches);
           foreach ($matches[0] as $index => $match) {
               $msg = str_replace($match,'',$msg);
               $meta['html_snippet'][] = $matches[1][$index];
           }
       }
       return $meta;
   }

   public static function makeSubmessages($msg, $paramsMessage = array()) {

       $replacer = 'IMG_REPLACE';

       if (strpos($msg,'[/quote]') !== false) {
           $replacer = '';
       }

        // Links wraps images
       $msg = preg_replace('#\[url\="?(.*?)"?\]\[file="?(.*?)_img"?\]\[\/url\]#is','[file=\2_img link=\1]',$msg);

       // pure files
       $msg = preg_replace('#\[file="?(.*?)_img"?(.*?)\]#is',$replacer.'[file=\1_img\2]'.$replacer,$msg);

       // Images within links
       $msg = preg_replace('#\[url\="?(.*?)"?\]\[img\](.*?)\[\/img\]\[\/url\]#is','[img=\1]\2[/img]',$msg);

       // Pure images
       $msg = preg_replace('#\[img(.*?)\](.*?)\[\/img\]#is',$replacer.'[img\1]\2[/img]'.$replacer,$msg);

       $msg = trim($msg);

       if (strpos($msg,'IMG_REPLACE') !== false) {
           $messages = array_filter(explode('IMG_REPLACE', $msg), function($v) {
               return $v != "";
           });
           $totalMessages = count($messages);
       } else {
           $totalMessages = 1;
           $messages = [$msg];
       }

       $messagesData = array();
       foreach ($messages as $indexMessage => $message) {
           if (trim($message) != '')
           {
               $msgRendered = erLhcoreClassBBCode::make_clickable(htmlspecialchars($message), $paramsMessage);

               $messagesDataItem['body'] = $msgRendered;
               $messagesDataItem['flags'] = [];

               $msgRenderedMedia = strip_tags($msgRendered);
               $emojiMessage = trim(preg_replace('#([\x{2B50}-\x{2B55}]|[\x{23F0}-\x{23F3}]|[\x{231A}-\x{231B}]|[\x{1F600}-\x{1F64F}]|[\x{1F910}-\x{1F9FF}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}])#u','', $msgRendered));

               if ($msgRenderedMedia == '') {
                   $messagesDataItem['flags'][] = 'img';
               }

               if ($emojiMessage == '') {
                   $messagesDataItem['flags'][] = 'emoji';
               }

                // New line at the top of message and it's not the first message
               if (substr($message,0,1) == "\n" && $indexMessage > 0) {
                   $messagesDataItem['flags'][] = 'nlt';
               }

               // New line at the end of message and it's not the last message
               if (substr($message,-1) == "\n" && $indexMessage + 1 != $totalMessages) {
                   $messagesDataItem['flags'][] = 'nl';
               }

               $messagesData[] = $messagesDataItem;
           }
       }

       return $messagesData;
   }

   public static function isBBCodeTagSupported($bbcode, $paramsMessage) {
       static $dataBBCode = null;
       if ($dataBBCode === null) {
           $dataBBCode = erLhcoreClassModelChatConfig::fetch('bbcode_options')->data;
           if (!isset($dataBBCode['div'])) {
               $dataBBCode['div'] = [];
           }
           if (!isset($dataBBCode['dio'])) {
               $dataBBCode['dio'] = [];
           }
       }

       if ($bbcode == 'white_list') {
           if (((isset($paramsMessage['sender']) && $paramsMessage['sender'] == 0) || (isset($paramsMessage['user_id_raw']) && $paramsMessage['user_id_raw'] == 0)) && !empty($dataBBCode['url_whitelist'])) {
               return $dataBBCode['url_whitelist'];
           } else {
               return false;
           }
       }

       if ((isset($paramsMessage['sender']) && $paramsMessage['sender'] == 0) || (isset($paramsMessage['user_id_raw']) && $paramsMessage['user_id_raw'] == 0) ) {
           return !in_array($bbcode,$dataBBCode['div']);
       } else {
           return !in_array($bbcode,$dataBBCode['dio']);
       }
   }

    public static function obfuscateLinks($text, $whitelist, $placeholder)
    {
        // Only match URLs with protocols (http/https/ftp) or www prefix
        $regex = '/\b(?:https?:\/\/[\w.-]+(?:\/\S*)?|ftp:\/\/[\w.-]+(?:\/\S*)?|www\.[\w.-]+\.[a-z]{2,}(?:\/\S*)?)\b/i';

        return preg_replace_callback($regex, function ($matches) use ($whitelist, $placeholder) {
            $original = $matches[0];
            $url = $original;

            // Extract domain for whitelist check
            preg_match('/(?:https?:\/\/|ftp:\/\/)?(?:www\.)?([\w.-]+\.[a-z]{2,}|\d+\.\d+\.\d+\.\d+)/i', $url, $domainMatch);
            $domain = strtolower($domainMatch[1] ?? '');

            // Check if the domain is in the whitelist
            foreach ($whitelist as $allowed) {
                $allowed = strtolower(trim($allowed));
                if (empty($allowed)) {
                    continue;
                }
                
                // Check if domain exactly matches or is a subdomain of the allowed domain
                if ($domain === $allowed || str_ends_with($domain, '.' . $allowed)) {
                    return $original; // Leave it untouched
                }
            }

            // Replace protocol
            if (stripos($url, 'https://') === 0) {
                $url = 'https[:]//' . substr($url, 8);
            } elseif (stripos($url, 'http://') === 0) {
                $url = 'http[:]//' . substr($url, 7);
            } elseif (stripos($url, 'ftp://') === 0) {
                $url = 'ftp[:]//' . substr($url, 6);
            }

            // Replace dots in the domain and full string with [.]
            $url = preg_replace('/\./', '[.]', $url);

            return str_replace('{url}',$url, $placeholder);

        }, $text);
    }

   // Converts bbcode and general links to hmtl code
   public static function make_clickable($ret, $paramsMessage = array()) {

        if (!isset($paramsMessage['keep_nl']) || $paramsMessage['keep_nl'] === false) {
            $ret = ' ' . $ret;
        }

        $ret = str_replace("\r\n","\n",$ret);

        $makeLinksClickable = true;

        if (isset($paramsMessage['see_sensitive_information']) && $paramsMessage['see_sensitive_information'] === false && $paramsMessage['sender'] == 0) {
           $ret = \LiveHelperChat\Models\LHCAbstract\ChatMessagesGhosting::maskMessage($ret);
        }

        if (self::isBBCodeTagSupported('[code]',$paramsMessage)) {
           $ret = str_replace("```\n", '```', $ret);
           $ret = str_replace("```\r", '```', $ret);
           $ret = preg_replace_callback('/```(.*?)```/ms', "erLhcoreClassBBCode::_make_code", $ret);
           $ret = preg_replace_callback('/`(.*?)`/ms', "erLhcoreClassBBCode::_make_code_plain", $ret);
           $ret = preg_replace_callback('/\[code\](.*?)\[\/code\]/ms', "erLhcoreClassBBCode::_make_code", $ret);
        }

        if (self::isBBCodeTagSupported('[plain]',$paramsMessage)) {
           $ret = preg_replace_callback('#\[plain\](.*?)\[/plain\]#is', 'erLhcoreClassBBCode::_make_plain_text', $ret);
        }

        if (self::isBBCodeTagSupported('[dateformat]',$paramsMessage)) {
           $ret = preg_replace_callback('#\[dateformat=([A-Za-z0-9:,\/.\-\s]{1,60})\](.*?)\[/dateformat\]#is', 'erLhcoreClassBBCode::_date_format', $ret);
        }

        erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.before_make_clickable',array('msg' => & $ret, 'makeLinksClickable' => & $makeLinksClickable));

        /*
         * BBCode options
         * */
        if (self::isBBCodeTagSupported('[baseurl]',$paramsMessage)) {
            $ret = preg_replace_callback('#\[baseurl\](.*?)\[/baseurl\]#is', 'erLhcoreClassBBCode::_make_base_link', $ret);
        }

        if (self::isBBCodeTagSupported('[img]',$paramsMessage)) {
            $ret = preg_replace_callback('/\[img=?(.*?)\](.*?)\[\/img\]/ms', "erLhcoreClassBBCode::_make_url_embed_image", $ret);
        }

        if (self::isBBCodeTagSupported('[loc]',$paramsMessage)) {
            $ret = preg_replace_callback('/\[loc\](.*?)\[\/loc\]/ms', "erLhcoreClassBBCode::_make_embed_map", $ret);
        }

        if (($whiteList = self::isBBCodeTagSupported('white_list',$paramsMessage)) !== false) {
            $whiteListParams = explode('||', $whiteList);
            $ret = self::obfuscateLinks($ret, explode(',', $whiteListParams[0]), $whiteListParams[1] ?? '{url}');
        }

        if (self::isBBCodeTagSupported('[url]',$paramsMessage)) {
            // Convert markdown links to bbcode
            $pattern = '/\[(.*?)\]\((.*?)\)/';
            preg_match_all($pattern, $ret, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $ret = str_replace($match[0], '[url="' . $match[2] . '"]' . $match[1] . '[/url]', $ret);
            }
            $ret = preg_replace_callback('/\[url\="?(.*?)"?\](.*?)\[\/url\]/ms', "erLhcoreClassBBCode::_make_url_embed", $ret);
        } else {
            $makeLinksClickable = false;
        }

        if (isset($paramsMessage['sender']) && $paramsMessage['sender'] == 0) {
            $ret = preg_replace('/\[html\](.*?)\[\/html\]/ms','',$ret);
        } else if (isset($paramsMessage['html_as_text']) && $paramsMessage['html_as_text'] == true) {
            $ret = preg_replace_callback('/\[html\](.*?)\[\/html\]/ms', function ($matches) {
                $messageId = erLhcoreClassChat::generateHash(10);
                return '<button class="btn btn-xs btn-secondary fs13 btn-sm p-1 mb-1" onclick="$(\'#message-more-'. $messageId.'\').toggleClass(\'hide\')" >'.erTranslationClassLhTranslation::getInstance()->getTranslation('chat/syncuser', 'Hide/Show HTML Code').'</button><span id="message-more-'. $messageId.'" class="hide"><br/><code class="rounded mx170 text-dark">'.trim($matches[1]).'</code></span>';
            }, $ret);
        }

        $ret = preg_replace_callback('/\[html\](.*?)\[\/html\]/ms', function ($matches) use ($paramsMessage) {
            $html = htmlspecialchars_decode($matches[1]);

            $html = preg_replace_callback('/"window\.parent\.(.*)"/ms',function ($matches){
                return "'lhinst.executeRemoteCommands([\"lhc_eval:" . $matches[1] . "\"])'";
            },$html);

            // Remove JS if it's not the first run
            if (!isset($paramsMessage['render_js']) || $paramsMessage['render_js'] == false) {
                $html = preg_replace("/<script.*?\/script>/s", "", $html);
            }

            return $html;

        }, $ret);

        if ($makeLinksClickable == true) {
            $ret = self::make_clickable_text($ret);
        }

    	$ret = self::BBCode2Html($ret, $paramsMessage);

       if (self::isBBCodeTagSupported('[paypal]',$paramsMessage)) {
           // Paypal button
           $ret = preg_replace_callback('#\[paypal\](.*?)\[/paypal\]#is', 'erLhcoreClassBBCode::_make_paypal_button', $ret);
       }

        if (self::isBBCodeTagSupported('[quote]',$paramsMessage)) {
            // Quote
            $ret = preg_replace_callback('#\[quote\](.*?)\[/quote\]#is', 'erLhcoreClassBBCode::makeQuote', $ret);
            $ret = preg_replace_callback('#\[quote="?([0-9]+)"?\](.*?)\[/quote\]#is', 'erLhcoreClassBBCode::makeQuote', $ret);
        }

        if (self::isBBCodeTagSupported('[youtube]',$paramsMessage)) {
            // Youtube block
            $ret = preg_replace_callback('#\[youtube\](.*?)\[/youtube\]#is', 'erLhcoreClassBBCode::_make_youtube_block', $ret);
        }

        if (self::isBBCodeTagSupported('[fupload]',$paramsMessage)) {
            // File upload link directly in chat message
            $ret = preg_replace_callback('#\[fupload\](.*?)\[/fupload\]#is', 'erLhcoreClassBBCode::_make_upload_link', $ret);
        }

        if (self::isBBCodeTagSupported('[button_action]',$paramsMessage)) {
            $ret = preg_replace_callback('#\[button_action="?(.*?)"?\](.*?)\[/button_action\]#is', 'erLhcoreClassBBCode::_make_button_action', $ret);
        }

        if (self::isBBCodeTagSupported('[link_trigger]',$paramsMessage)) {
            $ret = preg_replace_callback('#\[link_trigger="?([0-9]+)"?\](.*?)\[/link_trigger\]#is', 'erLhcoreClassBBCode::_make_link_trigger', $ret);
        }

    	if (strpos($ret,'[translation]') !== false) {
            // For the admin we show original and translated text
            if (isset($paramsMessage['html_as_text']) && $paramsMessage['html_as_text'] == true) {
                $ret = preg_replace('#\[translation\](.*?)\[/translation\]#is', '<span class="tr-msg">$1</span>', $ret);
            } else {
                // This is visitor translated message. We show original message for the visitor
                if (isset($paramsMessage['sender']) && $paramsMessage['sender'] > 0) {
                    // This is admin message. We show translated content only
                    $translations = array();
                    preg_match('#\[translation\](.*?)\[/translation\]#is',$ret, $translations);
                    if (isset($translations[1])) {
                        $ret = $translations[1];
                    }

                } else {
                    $ret = preg_replace('#\[translation\](.*?)\[/translation\]#is', '', $ret);
                }
            }
        }

        if (self::isBBCodeTagSupported('[file]',$paramsMessage)) {
            $ret = preg_replace_callback('#\[file="?(.*?)"?\]#is', function($matches) use ($paramsMessage) {
                return self::_make_url_file($matches, $paramsMessage);
            }, $ret);
        }

        if (self::isBBCodeTagSupported('[survey]',$paramsMessage)) {
            // Survey
            $ret = preg_replace_callback('#\[survey="?(.*?)"?\]#is', 'erLhcoreClassBBCode::_make_url_survey', $ret);
        }

       if (!isset($paramsMessage['keep_nl']) || $paramsMessage['keep_nl'] === false) {
           $ret = trim($ret);
       }

        if (isset($paramsMessage['msg_id']) && $paramsMessage['msg_id'] > 0) {
            $ret = str_replace('{msg_id}',$paramsMessage['msg_id'], $ret);
        }

        if (!empty(self::$plainHash)) {
            $ret = str_replace(array_keys(self::$plainHash), self::$plainHash, $ret);
            self::$plainHash = [];
        }

        erLhcoreClassChatEventDispatcher::getInstance()->dispatch('chat.after_make_clickable',array('msg' => & $ret));

    	return $ret;
   }
   
   public static function parseForMail($ret){
   		// File block
   		$ret = preg_replace_callback('#\[file="?(.*?)"?\]#is', 'erLhcoreClassBBCode::_make_url_mail_file', $ret);
   		return trim($ret);
   }
   
   // Makes plain text from BB code
   public static function make_plain($ret){
        $ret = ' ' . $ret;

       // BBCode to find...
       $in = array( 	 '/\[b\](.*?)\[\/b\]/ms',
           '/\[i\](.*?)\[\/i\]/ms',
           '/\[u\](.*?)\[\/u\]/ms',
           '/\[list\=(.*?)\](.*?)\[\/list\]/ms',
           '/\[list\](.*?)\[\/list\]/ms',
           '/\[\*\]\s?(.*?)\n/ms',
           '/\[img\](.*?)\[\/img\]/ms',
           '/\[url\="?(.*?)"?\](.*?)\[\/url\]/ms',
           '/\[quote\]/ms',
           '/\[quote\=([0-9]+)\]/ms',
           '/\[\/quote\]/ms',
           '/\[fs([0-9]+)\](.*?)\[\/fs\]/ms',
           '/\[level\=([A-Za-z0-9\-\s]{2,60})\](.*?)\[\/level\]/ms',
           '/\[button_action\=([A-Za-z0-9_\-\s]{2,60})\](.*?)\[\/button_action\]/ms',
           '/\n/ms',
       );

       // And replace them by...
       $out = array(	 '\1',
           '\1',
           '\1',
           '\2',
           '\1',
           '\1',
           '',
           '\2 \1',
           '',
           '',
           '',
           '\2',
           '\2',
           '',
           ' ',
       );

    	$ret = preg_replace($in, $out, $ret);

        $ret = trim($ret);
        return $ret;
   }

}


?>
