<?php
/**
 * Allow creation of XHTML definition lists:
 * <dl class="classname">
 *   <dt>term</dt>
 *   <dd>definition</dd>
 * </dl>
 *
 * Syntax:
 *   ; term : definition
 *   ; term
 *   : definition
 *
 * As with other dokuwiki lists, each line must start with 2 spaces or a tab.
 * Nested definition lists are not supported at this time.
 *
 * Additional Macro syntax:
 *   ~~dlist:classname~~
 * is available to set arbitrary classname for the next definition list block.
 *
 * More complex syntax:
 *   ;dtclass| term
 *   :ddclass| definition1
 *   : definition2
 * will generate following XHTML:
 * <dl class="classname">
 *   <dt class="dtclass">term</dt>
 *   <dd class="ddclass">definition1</dd>
 *   <dd>definition2</dd>
 * </dl>
 *
 * This plugin is heavily based on the definitions plugin by Pavel Vitis which
 * in turn drew from the original definition list plugin by Stephane Chamberland.
 * A huge thanks to both of them.
 *
 * Configuration:
 *
 * dt_fancy    Whether to wrap DT content in <span class="term">Term</span>.
 *             Default true.
 * classname   The html class name to be given to the DL element.
 *             Default 'plugin_definitionlist'. This is the class used in the
 *             bundled CSS file.
 *
 * ODT support provided by Gabriel Birke
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Chris Smith <chris [at] jalakai [dot] co [dot] uk>
 * @author     Gabriel Birke <birke@d-scribe.de>
 */

if (!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * Settings:
 *
 * Define the trigger characters:
 * ";" & ":" are the mediawiki settings.
 * "=" & ":" are the settings for the original plugin by Pavel.
 */
if (!defined('DL_DT')) define('DL_DT', ';'); // character to indicate a term (dt)
if (!defined('DL_DD')) define('DL_DD', ':'); // character to indicate a definition (dd)

/**
 *
 */
class syntax_plugin_definitionlist extends DokuWiki_Syntax_Plugin {

    protected $stack = array();    // stack of currently open definition list items - used by handle() method
    protected $dlclass = '';       // register of classname for dl tags

    public function getType() { return 'container'; }
    public function getAllowedTypes() { return array('container','substition','protected','disabled','formatting'); }
    public function getPType() { return 'block'; }          // block, so not surrounded by <p> tags
    public function getSort() { return 10; }                // before preformatted (20)

    /**
     * Connect pattern to lexer
     */
    public function connectTo($mode) {

        $this->Lexer->addSpecialPattern('~~dlist:.*?~~', $mode, 'plugin_definitionlist');

        $this->Lexer->addEntryPattern('\n {2,}(?:'.DL_DT.'[^\n]*?\||'.DL_DT.')', $mode, 'plugin_definitionlist');
        $this->Lexer->addEntryPattern('\n\t{1,}(?:'.DL_DT.'[^\n]*?\||'.DL_DT.')', $mode, 'plugin_definitionlist');

        $this->Lexer->addPattern('\n {2,}(?:'.DL_DT.'[^\n]*?\||'.DL_DT.')', 'plugin_definitionlist');
        $this->Lexer->addPattern('\n {2,}(?:'.DL_DD.'[^\n]*?\||'.DL_DD.')', 'plugin_definitionlist');
        $this->Lexer->addPattern('\n\t{1,}(?:'.DL_DT.'[^\n]*?\||'.DL_DT.')', 'plugin_definitionlist');
        $this->Lexer->addPattern('\n\t{1,}(?:'.DL_DD.'[^\n]*?\||'.DL_DD.')', 'plugin_definitionlist');

        $this->Lexer->addPattern('(?: '.DL_DD.' )', 'plugin_definitionlist');
    }

    public function postConnect() {
        // we end the definition list when we encounter a blank line
        $this->Lexer->addExitPattern('\n(?=[ \t]*\n)','plugin_definitionlist');
    }

    /**
     * Handle the match
     */
    public function handle($match, $state, $pos, &$handler) {
        switch ( $state ) {
            case DOKU_LEXER_SPECIAL:
                    // set register of classname for dl tags
                    $this->dlclass = trim(substr($match, 8, -2));
                    break;

            case DOKU_LEXER_ENTER:
                    array_push($this->stack, 'dt');
                    // set class attribute for the dl tag
                    if (empty($this->dlclass)) $this->dlclass = $this->getConf('classname');
                    $this->_writeCall('dl',DOKU_LEXER_ENTER,$pos,$this->dlclass,$handler);    // open a new DL
                    $match = ltrim($match);
                    $match = trim(substr($match, 1,-1)); // class for the dt tag
                    $this->_writeCall('dt',DOKU_LEXER_ENTER,$pos,$match,$handler);    // always start with a DT
                    break;

            case DOKU_LEXER_MATCHED:
                    $oldtag = array_pop($this->stack);
                    $this->_writeCall($oldtag,DOKU_LEXER_EXIT,$pos,$match,$handler);  // close the current definition list item...

                    $match = ltrim($match);
                    $newtag = ($match[0] == DL_DT) ? 'dt' : 'dd';
                    array_push($this->stack, $newtag);
                    $match = trim(substr($match, 1,-1)); // class for the newtag
                    $this->_writeCall($newtag,DOKU_LEXER_ENTER,$pos,$match,$handler); // ...and open the new dl item
                    break;

            case DOKU_LEXER_EXIT:
                    // clean up & close any dl items on the stack
                    while ($tag = array_pop($this->stack)) {
                        $this->_writeCall($tag,DOKU_LEXER_EXIT,$pos,$match,$handler);
                    }

                    // and finally close the surrounding DL
                    $this->_writeCall('dl',DOKU_LEXER_EXIT,$pos,$match,$handler);
                    // clear register of classname for dl tags
                    $this->dlclass = '';
                    break;

            case DOKU_LEXER_UNMATCHED:
                    $handler->base($match, $state, $pos);    // cdata --- use base() as _writeCall() is prefixed for private/protected
                    break;
        }

        return false;
    }

    /**
     * helper function to simplify writing plugin calls to the instruction list
     *
     * instruction params are of the format:
     *    0 => tag    (string)    'dl','dt','dd'
     *    1 => state  (int)       DOKU_LEXER_??? state constant
     *    2 => match  (string)    class attribute of the tag
     */
    protected function _writeCall($tag, $state, $pos, $match, &$handler) {
        $handler->addPluginCall('definitionlist', array($tag, $state, $match), $state, $pos, $match);
    }

    /**
     * Create output
     */
    public function render($format, &$renderer, $data) {
        if (empty($data)) return false;

        switch  ($format) {
            case 'xhtml' : return $this->render_xhtml($renderer,$data);
            case 'odt'   : return $this->render_odt($renderer,$data);
            default :
                //  handle unknown formats generically - map both 'dt' & 'dd' to paragraphs; ingnore the 'dl' container
                list ($tag, $state, $match) = $data;
                switch ( $state ) {
                    case DOKU_LEXER_ENTER:
                    if ($tag != 'dl') $renderer->p_open();
                    break;
                case DOKU_LEXER_MATCHED:                              // fall-thru
                case DOKU_LEXER_UNMATCHED:                            // defensive, shouldn't occur
                    $renderer->cdata($match);
                    break;
                case DOKU_LEXER_EXIT:
                    if ($tag != 'dl') $renderer->p_close();
                    break;
                }
                return true;
        }

        return false;
    }

    /**
     * create output for the xhtml renderer
     *
     */
    protected function render_xhtml(&$renderer, $data) {
        list($tag,$state,$match) = $data;

        switch ( $state ) {
            case DOKU_LEXER_ENTER:
                $renderer->doc .= $this->_open($tag, $match);
                break;
            case DOKU_LEXER_MATCHED:
            case DOKU_LEXER_UNMATCHED:                            // defensive, shouldn't occur
                $renderer->cdata($tag);
                break;
            case DOKU_LEXER_EXIT:
                $renderer->doc .= $this->_close($tag);
                break;
        }
        return true;
    }

    /**
     * create output for ODT renderer
     *
     * @author:   Gabriel Birke <birke@d-scribe.de>
     */
    protected function render_odt(&$renderer, $data) {
        static $param_styles = array('dd' => 'def_f5_list', 'dt' => 'def_f5_term');
        $this->_set_odt_styles($renderer);

        list ($tag, $state, $match) = $data;

        switch ( $state ) {
            case DOKU_LEXER_ENTER:
                if ($tag == 'dl') {
                    $renderer->p_close();
                } else {
                    $renderer->p_open($param_styles[$tag]);
                }
                break;
            case DOKU_LEXER_MATCHED:
            case DOKU_LEXER_UNMATCHED:                            // defensive, shouldn't occur
                $renderer->cdata($match);
                break;
            case DOKU_LEXER_EXIT:
                if ($tag != 'dl') {
                    $renderer->p_close();
                } else {
                    $renderer->p_open();
                }
                break;
        }

        return true;
    }

    /**
     * set definition list styles, used by render_odt()
     *
     * add definition list styles to the renderer's autostyles property (once only)
     *
     * @param  $renderer    current (odt) renderer object
     * @return void
     */
    protected function _set_odt_styles(&$renderer) {
        static $do_once = true;

        if ($do_once) {
            $renderer->autostyles["def_f5_term"] = '
                <style:style style:name="def_f5_term" style:display-name="def_term" style:family="paragraph">
                    <style:paragraph-properties fo:margin-top="0.18cm" fo:margin-bottom="0cm" fo:keep-together="always" style:page-number="auto" fo:keep-with-next="always"/>
                    <style:text-properties fo:font-weight="bold"/>
                </style:style>';
            $renderer->autostyles["def_f5_list"] = '
                <style:style style:name="def_f5_list" style:display-name="def_list" style:family="paragraph">
                    <style:paragraph-properties fo:margin-left="0.25cm" fo:margin-right="0cm" fo:text-indent="0cm" style:auto-text-indent="false"/>
                </style:style>';

            $do_once = false;
        }
    }

    /**
     * open a definition list tag, used by render_xhtml()
     *
     * @param   $tag  (string)    'dl', 'dt' or 'dd'
     * @param   $class (string)   class attribute of the tag
     * @return  (string)          html used to open the tag
     */
    protected function _open($tag, $class='') {
        if ($tag == 'dl') {
            $wrap = NL;
        } else {
            $wrap = ($tag == 'dt' && $this->getConf('dt_fancy')) ? '<span class="term">' : '';
        }
        if ($class) $tag.= ' class="'.$class.'"';
        return "<$tag>$wrap";
    }

    /**
     * close a definition list tag, used by render_xhtml()
     *
     * @param   $tag  (string)    'dl', 'dt' or 'dd'
     * @return  (string)          html used to close the tag
     */
    protected function _close($tag) {
        $wrap = ($tag == 'dt' && $this->getConf('dt_fancy')) ? '</span>' : '';
        return "$wrap</$tag>\n";
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
