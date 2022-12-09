<?php

class ES_Html2Text {

	const ENCODING = 'UTF-8';

	protected $htmlFuncFlags;

	/**
	 * Contains the HTML content to convert.
	 *
	 * @type string
	 */
	protected $html;

	/**
	 * Contains the converted, formatted text.
	 *
	 * @type string
	 */
	protected $text;

	/**
	 * List of preg* regular expression patterns to search for,
	 * used in conjunction with $replace.
	 *
	 * @type array
	 * @see $replace
	 */
	protected $search = array(
		"/\r/", // Non-legal carriage return
		"/[\n\t]+/", // Newlines and tabs
		'/<head\b[^>]*>.*?<\/head>/i', // <head>
		'/<script\b[^>]*>.*?<\/script>/i', // <script>s -- which strip_tags supposedly has problems with
		'/<style\b[^>]*>.*?<\/style>/i', // <style>s -- which strip_tags supposedly has problems with
		'/<i\b[^>]*>(.*?)<\/i>/i', // <i>
		'/<em\b[^>]*>(.*?)<\/em>/i', // <em>
		'/(<ul\b[^>]*>|<\/ul>)/i', // <ul> and </ul>
		'/(<ol\b[^>]*>|<\/ol>)/i', // <ol> and </ol>
		'/(<dl\b[^>]*>|<\/dl>)/i', // <dl> and </dl>
		'/<li\b[^>]*>(.*?)<\/li>/i', // <li> and </li>
		'/<dd\b[^>]*>(.*?)<\/dd>/i', // <dd> and </dd>
		'/<dt\b[^>]*>(.*?)<\/dt>/i', // <dt> and </dt>
		'/<li\b[^>]*>/i', // <li>
		'/<hr\b[^>]*>/i', // <hr>
		'/<div\b[^>]*>/i', // <div>
		'/(<table\b[^>]*>|<\/table>)/i', // <table> and </table>
		'/(<tr\b[^>]*>|<\/tr>)/i', // <tr> and </tr>
		'/<td\b[^>]*>(.*?)<\/td>/i', // <td> and </td>
		'/<span class="_html2text_ignore">.+?<\/span>/i', // <span class="_html2text_ignore">...</span>
		'/<(img)\b[^>]*alt=\"([^>"]+)\"[^>]*>/i', // <img> with alt tag
	);

	/**
	 * List of pattern replacements corresponding to patterns searched.
	 *
	 * @type array
	 * @see $search
	 */
	protected $replace = array(
		'', // Non-legal carriage return
		' ', // Newlines and tabs
		'', // <head>
		'', // <script>s -- which strip_tags supposedly has problems with
		'', // <style>s -- which strip_tags supposedly has problems with
		'_\\1_', // <i>
		'_\\1_', // <em>
		"\n\n", // <ul> and </ul>
		"\n\n", // <ol> and </ol>
		"\n\n", // <dl> and </dl>
		"\t* \\1\n", // <li> and </li>
		" \\1\n", // <dd> and </dd>
		"\t* \\1", // <dt> and </dt>
		"\n\t* ", // <li>
		"\n-------------------------\n", // <hr>
		"<div>\n", // <div>
		"\n\n", // <table> and </table>
		"\n", // <tr> and </tr>
		"\t\t\\1\n", // <td> and </td>
		'', // <span class="_html2text_ignore">...</span>
		'[\\2]', // <img> with alt tag
	);

	/**
	 * List of preg* regular expression patterns to search for,
	 * used in conjunction with $entReplace.
	 *
	 * @type array
	 * @see $entReplace
	 */
	protected $entSearch = array(
		'/&#153;/i', // TM symbol in win-1252
		'/&#151;/i', // m-dash in win-1252
		'/&(amp|#38);/i', // Ampersand: see converter()
		'/[ ]{2,}/', // Runs of spaces, post-handling
	);

	/**
	 * List of pattern replacements corresponding to patterns searched.
	 *
	 * @type array
	 * @see $entSearch
	 */
	protected $entReplace = array(
		'™', // TM symbol
		'—', // m-dash
		'|+|amp|+|', // Ampersand: see converter()
		' ', // Runs of spaces, post-handling
	);

	/**
	 * List of preg* regular expression patterns to search for
	 * and replace using callback function.
	 *
	 * @type array
	 */
	protected $callbackSearch = array(
		'/<(h)[123456]( [^>]*)?>(.*?)<\/h[123456]>/i', // h1 - h6
		'/[ ]*<(p)( [^>]*)?>(.*?)<\/p>[ ]*/si', // <p> with surrounding whitespace.
		'/<(br)[^>]*>[ ]*/i', // <br> with leading whitespace after the newline.
		'/<(b)( [^>]*)?>(.*?)<\/b>/i', // <b>
		'/<(strong)( [^>]*)?>(.*?)<\/strong>/i', // <strong>
		'/<(th)( [^>]*)?>(.*?)<\/th>/i', // <th> and </th>
		'/<(a) [^>]*href=("|\')([^"\']+)\2([^>]*)>(.*?)<\/a>/i', // <a href="">
	);

	/**
	 * List of preg* regular expression patterns to search for in PRE body,
	 * used in conjunction with $preReplace.
	 *
	 * @type array
	 * @see $preReplace
	 */
	protected $preSearch = array(
		"/\n/",
		"/\t/",
		'/ /',
		'/<pre[^>]*>/',
		'/<\/pre>/',
	);

	/**
	 * List of pattern replacements corresponding to patterns searched for PRE body.
	 *
	 * @type array
	 * @see $preSearch
	 */
	protected $preReplace = array(
		'<br>',
		'&nbsp;&nbsp;&nbsp;&nbsp;',
		'&nbsp;',
		'',
		'',
	);

	/**
	 * Temporary workspace used during PRE processing.
	 *
	 * @type string
	 */
	protected $preContent = '';

	/**
	 * Contains the base URL that relative links should resolve to.
	 *
	 * @type string
	 */
	protected $baseurl = '';

	/**
	 * Indicates whether content in the $html variable has been converted yet.
	 *
	 * @type boolean
	 * @see $html, $text
	 */
	protected $converted = false;

	/**
	 * Contains URL addresses from links to be rendered in plain text.
	 *
	 * @type array
	 * @see buildlinkList()
	 */
	protected $linkList = array();

	/**
	 * Various configuration options (able to be set in the constructor)
	 *
	 * @type array
	 */
	protected $options = array(
		'do_links' => 'inline', // 'none'
		// 'inline' (show links inline)
		// 'nextline' (show links on the next line)
		// 'table' (if a table of link URLs should be listed after the text.
		// 'bbcode' (show links as bbcode)

		'width'    => 70, // Maximum width of the formatted text, in columns.
		// Set this value to 0 (or less) to ignore word wrapping
		// and not constrain text to a fixed-width column.
	);

	/**
	 * Legacy class constructor
	 *
	 * @param unknown $html     (optional)
	 * @param unknown $fromFile (optional)
	 * @param array   $options  (optional)
	 */
	private function legacyConstruct( $html = '', $fromFile = false, array $options = array() ) {
		$this->set_html( $html, $fromFile );
		$this->options = array_merge( $this->options, $options );
	}


	/**
	 * Class constructor
	 *
	 * @param string $html    (optional) Source HTML
	 * @param array  $options (optional) Set configuration options
	 * @return unknown
	 */
	public function __construct( $html = '', $options = array() ) {

		/**
		 * It is a good practice to put func_get_args() at the start of the function to avoid a changed function parameter to leak into the original arguments.
		 * By taking the copy early this is avoided. This behavior changed in PHP7: [https://www.php.net/manual/en/function.func-get-args.php#refsect1-function.func-get-args-notes]
		 */
		$func_args = func_get_args();

		// for backwards compatibility
		if ( ! is_array( $options ) ) {
			return call_user_func_array( array( $this, 'legacyConstruct' ), $func_args );
		}

		$this->html          = $html;
		$this->options       = array_merge( $this->options, $options );
		$this->htmlFuncFlags = ( PHP_VERSION_ID < 50400 )
			? ENT_COMPAT
			: ENT_COMPAT | ENT_HTML5;
	}


	/**
	 * Get the source HTML
	 *
	 * @return string
	 */
	public function getHtml() {
		return $this->html;
	}


	/**
	 * Set the source HTML
	 *
	 * @param string $html HTML source content
	 */
	public function setHtml( $html ) {
		$this->html      = $html;
		$this->converted = false;
	}


	/**
	 * Sets the given HTML.
	 *
	 * @deprecated
	 * @param unknown $html
	 * @param unknown $from_file (optional)
	 * @return unknown
	 */
	public function set_html( $html, $from_file = false ) {
		if ( $from_file ) {
			throw new \InvalidArgumentException( 'Argument from_file no longer supported' );
		}

		return $this->setHtml( $html );
	}


	/**
	 * Returns the text, converted from HTML.
	 *
	 * @return string
	 */
	public function getText() {
		if ( ! $this->converted ) {
			$this->convert();
		}

		return $this->text;
	}


	/**
	 * Returns the text.
	 *
	 * @deprecated
	 * @return unknown
	 */
	public function get_text() {
		return $this->getText();
	}


	/**
	 * Prints the processed text.
	 *
	 * @deprecated
	 */
	public function print_text() {
		print esc_html( $this->getText() );
	}


	/**
	 * Prints the text.
	 *
	 * @deprecated
	 * @return unknown
	 */
	public function p() {
		return $this->print_text();
	}


	/**
	 * Sets a base URL to handle relative links.
	 *
	 * @param string $baseurl
	 */
	public function setBaseUrl( $baseurl ) {
		$this->baseurl = $baseurl;
	}


	/**
	 * Sets base URL.
	 *
	 * @deprecated
	 * @param unknown $baseurl
	 * @return unknown
	 */
	public function set_base_url( $baseurl ) {
		return $this->setBaseUrl( $baseurl );
	}


	protected function convert() {
		if ( function_exists( 'mb_internal_encoding' ) ) {
			$origEncoding = mb_internal_encoding();
			mb_internal_encoding( self::ENCODING );
		}

		$this->doConvert();

		if ( function_exists( 'mb_internal_encoding' ) ) {
			mb_internal_encoding( $origEncoding );
		}
	}


	protected function doConvert() {
		$this->linkList = array();

		$text = trim( $this->html );

		$this->converter( $text );

		if ( $this->linkList ) {
			$text .= "\n\nLinks:\n------\n";
			foreach ( $this->linkList as $i => $url ) {
				$text .= '[' . ( $i + 1 ) . '] ' . $url . "\n";
			}
		}

		$this->text = $text;

		$this->converted = true;
	}


	/**
	 * Converts the given text.
	 *
	 * @param unknown $text (reference)
	 */
	protected function converter( &$text ) {
		$this->convertBlockquotes( $text );
		$this->convertPre( $text );
		$text = preg_replace( $this->search, $this->replace, $text );
		$text = preg_replace_callback( $this->callbackSearch, array( $this, 'pregCallback' ), $text );
		$text = strip_tags( $text );
		$text = preg_replace( $this->entSearch, $this->entReplace, $text );
		$text = html_entity_decode( $text, $this->htmlFuncFlags, self::ENCODING );

		// Remove unknown/unhandled entities (this cannot be done in search-and-replace block)
		$text = preg_replace( '/&([a-zA-Z0-9]{2,6}|#[0-9]{2,4});/', '', $text );

		// Convert "|+|amp|+|" into "&", need to be done after handling of unknown entities
		// This properly handles situation of "&amp;quot;" in input string
		$text = str_replace( '|+|amp|+|', '&', $text );

		// Normalise empty lines
		$text = preg_replace( "/\n\s+\n/", "\n\n", $text );
		$text = preg_replace( "/[\n]{3,}/", "\n\n", $text );

		// remove leading empty lines (can be produced by eg. P tag on the beginning)
		$text = ltrim( $text, "\n" );

		if ( $this->options['width'] > 0 ) {
			$text = wordwrap( $text, $this->options['width'] );
		}
	}


	/**
	 * Helper function called by preg_replace() on link replacement.
	 *
	 * Maintains an internal list of links to be displayed at the end of the
	 * text, with numeric indices to the original point in the text they
	 * appeared. Also makes an effort at identifying and handling absolute
	 * and relative links.
	 *
	 * @param string $link         URL of the link
	 * @param string $display      Part of the text to associate number with
	 * @param null   $linkOverride
	 * @return string
	 */
	protected function buildlinkList( $link, $display, $linkOverride = null ) {
		$linkMethod = ( $linkOverride ) ? $linkOverride : $this->options['do_links'];
		if ( 'none' == $linkMethod ) {
			return $display;
		}

		// Ignored link types
		if ( preg_match( '!^(javascript:|mailto:|#)!i', $link ) ) {
			return $display;
		}

		if ( preg_match( '!^([a-z][a-z0-9.+-]+:)!i', $link ) ) {
			$url = $link;
		} else {
			$url = $this->baseurl;
			if ( substr( $link, 0, 1 ) != '/' ) {
				$url .= '/';
			}
			$url .= $link;
		}

		if ( 'table' == $linkMethod ) {
			$index = array_search( $url, $this->linkList );
			if ( false === $index ) {
				$index            = count( $this->linkList );
				$this->linkList[] = $url;
			}

			return $display . ' [' . ( $index + 1 ) . ']';
		} elseif ( 'nextline' == $linkMethod ) {
			return $display . "\n[" . $url . ']';
		} elseif ( 'bbcode' == $linkMethod ) {
			return sprintf( '[url=%s]%s[/url]', $url, $display );
		} else {
			// link_method defaults to inline
			return $display . ' [' . $url . ']';
		}
	}


	/**
	 * Converts text from pre element.
	 *
	 * @param unknown $text (reference)
	 */
	protected function convertPre( &$text ) {
		// get the content of PRE element
		while ( preg_match( '/<pre[^>]*>(.*)<\/pre>/ismU', $text, $matches ) ) {
			// Replace br tags with newlines to prevent the search-and-replace callback from killing whitespace
			$this->preContent = preg_replace( '/(<br\b[^>]*>)/i', "\n", $matches[1] );

			// Run our defined tags search-and-replace with callback
			$this->preContent = preg_replace_callback(
				$this->callbackSearch,
				array( $this, 'pregCallback' ),
				$this->preContent
			);

			// convert the content
			$this->preContent = sprintf(
				'<div><br>%s<br></div>',
				preg_replace( $this->preSearch, $this->preReplace, $this->preContent )
			);

			// replace the content (use callback because content can contain $0 variable)
			$text = preg_replace_callback(
				'/<pre[^>]*>.*<\/pre>/ismU',
				array( $this, 'pregPreCallback' ),
				$text,
				1
			);

			// free memory
			$this->preContent = '';
		}
	}


	/**
	 * Helper function for BLOCKQUOTE body conversion.
	 *
	 * @param string $text (reference) HTML content
	 */
	protected function convertBlockquotes( &$text ) {
		if ( preg_match_all( '/<\/*blockquote[^>]*>/i', $text, $matches, PREG_OFFSET_CAPTURE ) ) {
			$originalText = $text;
			$start        = 0;
			$taglen       = 0;
			$level        = 0;
			$diff         = 0;
			foreach ( $matches[0] as $m ) {
				$m[1] = strlen( substr( $originalText, 0, $m[1] ) );
				if ( '<' == $m[0][0] && '/' == $m[0][1] ) {
					$level--;
					if ( $level < 0 ) {
						$level = 0; // malformed HTML: go to next blockquote
					} elseif ( 0 == $level ) {
						$end = $m[1];
						$len = $end - $taglen - $start;
						// Get blockquote content
						$body = substr( $text, $start + $taglen - $diff, $len );

						// Set text width
						$pWidth = $this->options['width'];
						if ( $this->options['width'] > 0 ) {
							$this->options['width'] -= 2;
						}

						// Convert blockquote content
						$body = trim( $body );
						$this->converter( $body );
						// Add citation markers and create PRE block
						$body = preg_replace( '/((^|\n)>*)/', '\\1> ', trim( $body ) );
						$body = '<pre>' . htmlspecialchars( $body, $this->htmlFuncFlags, self::ENCODING ) . '</pre>';
						// Re-set text width
						$this->options['width'] = $pWidth;
						// Replace content
						$text = substr( $text, 0, $start - $diff )
								. $body
								. substr( $text, $end + strlen( $m[0] ) - $diff );

						$diff += $len + $taglen + strlen( $m[0] ) - strlen( $body );
						unset( $body );
					}
				} else {
					if ( 0 == $level ) {
						$start  = $m[1];
						$taglen = strlen( $m[0] );
					}
					$level++;
				}
			}
		}
	}


	/**
	 * Callback function for preg_replace_callback use.
	 *
	 * @param array $matches PREG matches
	 * @return string
	 */
	protected function pregCallback( $matches ) {
		switch ( strtolower( $matches[1] ) ) {
			case 'p':
				// Replace newlines with spaces.
				$para = str_replace( "\n", ' ', $matches[3] );

				// Trim trailing and leading whitespace within the tag.
				$para = trim( $para );

				// Add trailing newlines for this para.
				return "\n" . $para . "\n";
			case 'br':
				return "\n";
			case 'b':
			case 'strong':
				return $this->toupper( $matches[3] );
			case 'th':
				return $this->toupper( "\t\t" . $matches[3] . "\n" );
			case 'h':
				return $this->toupper( "\n\n" . $matches[3] . "\n\n" );
			case 'a':
				// override the link method
				$linkOverride = null;
				if ( preg_match( '/_html2text_link_(\w+)/', $matches[4], $linkOverrideMatch ) ) {
					$linkOverride = $linkOverrideMatch[1];
				}
				// Remove spaces in URL (#1487805)
				$url = str_replace( ' ', '', $matches[3] );

				return $this->buildlinkList( $url, $matches[5], $linkOverride );
		}

		return '';
	}


	/**
	 * Callback function for preg_replace_callback use in PRE content handler.
	 *
	 * @param array $matches PREG matches
	 * @return string
	 */
	protected function pregPreCallback( $matches ) {
		return $this->preContent;
	}


	/**
	 * Strtoupper function with HTML tags and entities handling.
	 *
	 * @param string $str Text to convert
	 * @return string Converted text
	 */
	protected function toupper( $str ) {
		// string can contain HTML tags
		$chunks = preg_split( '/(<[^>]*>)/', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

		// convert toupper only the text between HTML tags
		foreach ( $chunks as $i => $chunk ) {
			if ( '<' != $chunk[0] ) {
				$chunks[ $i ] = $this->strtoupper( $chunk );
			}
		}

		return implode( $chunks );
	}


	/**
	 * Strtoupper multibyte wrapper function with HTML entities handling.
	 *
	 * @param string $str Text to convert
	 * @return string Converted text
	 */
	protected function strtoupper( $str ) {
		$str = html_entity_decode( $str, $this->htmlFuncFlags, self::ENCODING );
		$str = strtoupper( $str );
		$str = htmlspecialchars( $str, $this->htmlFuncFlags, self::ENCODING );

		return $str;
	}


}
