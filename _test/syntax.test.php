<?php
/**
 * @group plugin_definitionlist
 */
class plugin_definitionlist_syntax_test extends DokuWikiTest {

    protected $pluginsEnabled = array('definitionlist');

    function test_basic() {
        $in1 = "\n"
              ."  ; Term\n"
              ."  : Definition\n";
        $in2 = "\n  ; Term : Definition\n";
        $expected = "\n<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Definition</dd>\n"
            ."</dl>\n";

        $renderer = new Doku_Renderer_xhtml();
        $actual1 = $renderer->render($in1, 'xhtml');
        $this->assertEquals($expected, $actual1);
        $actual2 = $renderer->render($in2, 'xhtml');
        $this->assertEquals($expected, $actual2);
    }

    function test_multiple_definitions() {
        $in = "\n"
              ."  ; Term\n"
              ."  : Definition one\n"
              ."  : Definition two\n";
        $expected = "\n<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Definition one</dd>\n"
            ."<dd>Definition two</dd>\n"
            ."</dl>\n";

        $renderer = new Doku_Renderer_xhtml();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

    function test_newline_in_definition() {
        $in = "\n"
              ."  ; Term\n"
              ."  : Definition one\n"
              ."continues\n";
        $expected = "\n<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Definition one\ncontinues</dd>\n"
            ."</dl>\n";

        $renderer = new Doku_Renderer_xhtml();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

    function test_newline_in_definition_with_following_para() {
        $in = "\n"
            ."  ; Term\n"
            ."  : Definition one\n"
            ."continues\n"
            ."\n"
            ."Then new paragraph.\n";
        $expected = "\n<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Definition one\ncontinues</dd>\n"
            ."</dl>\n"
            ."\n"
            ."<p>\nThen new paragraph.\n</p>\n";
        $renderer = new Doku_Renderer_xhtml();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

    function test_basic_with_following_preformatted() {
        $in = "\n"
            ."  ; Term\n"
            ."  : Definition\n"
            ."\n"
            ."  Preformatted\n";
        $expected = "\n<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Definition</dd>\n"
            ."</dl>\n"
            ."<pre class=\"code\">Preformatted</pre>\n";
        $renderer = new Doku_Renderer_xhtml();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

    function test_nonfancy() {
        global $conf;
        $in1 = "\n"
              ."  ; Term\n"
              ."  : Definition\n";
        $in2 = "\n  ; Term : Definition\n";
        $expected = "\n<dl class=\"plugin_definitionlist\">\n"
            ."<dt>Term</dt>\n"
            ."<dd>Definition</dd>\n"
            ."</dl>\n";

        $conf['plugin']['definitionlist']['dt_fancy'] = false;
        $renderer = new Doku_Renderer_xhtml();
        $actual1 = $renderer->render($in1, 'xhtml');
        $this->assertEquals($expected, $actual1);
        $actual2 = $renderer->render($in2, 'xhtml');
        $this->assertEquals($expected, $actual2);
        unset($conf['plugin']['definitionlist']);
    }

    function test_custom_class_name() {
        global $conf;
        $in = "\n"
              ."  ; Term\n"
              ."  : Definition\n";
        $expected = "\n<dl class=\"lorem-ipsum\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Definition</dd>\n"
            ."</dl>\n";

        $conf['plugin']['definitionlist']['classname'] = 'lorem-ipsum';
        $renderer = new Doku_Renderer_xhtml();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
        unset($conf['plugin']['definitionlist']);
    }

    function test_two_dlists_with_blank_line_between() {
        $in = "\n"
              ."  ; Term : Def\n"
              ."\n"
              ."  ; Another term : Def\n";
        $expected = "\n"
            ."<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Def</dd>\n"
            ."</dl>\n"
            ."\n"
            ."<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Another term</span></dt>\n"
            ."<dd>Def</dd>\n"
            ."</dl>\n";
        $renderer = new Doku_Renderer();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

    function test_dd_with_ul() {
        $in = "\n"
              ."  ; Term\n"
              ."  : Some parts:\n"
              ."  * Part 1\n"
              ."  * Part 2\n"
              ."  ; Term 2\n"
              ."  : Def\n";
        $expected = "\n"
            ."<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Some parts:<ul>\n"
            ."<li class=\"level1\"><div class=\"li\"> Part 1</div>\n"
            ."</li>\n"
            ."<li class=\"level1\"><div class=\"li\"> Part 2</div>\n"
            ."</li>\n"
            ."</ul>\n"
            ."</dd>\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Def</dd>\n"
            ."</dl>\n";
        $renderer = new Doku_Renderer();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

    function test_dd_with_ul_followed_by_ordered_list() {
        $in = "\n"
              ."  ; Term\n"
              ."  : Some parts:\n"
              ."  * Part 1\n"
              ."  * Part 2\n"
              ."\n"
              ."  - Item\n";
        $expected = "\n"
            ."<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Some parts:<ul>\n"
            ."<li class=\"level1\"><div class=\"li\"> Part 1</div>\n"
            ."</li>\n"
            ."<li class=\"level1\"><div class=\"li\"> Part 2</div>\n"
            ."</li>\n"
            ."</ul>\n"
            ."</dd>\n"
            ."</dl>\n"
            ."\n"
            ."<ol>\n"
            ."<li class=\"level1\"><div class=\"li\"> Item</div>"
            ."\n</li>\n"
            ."</ol>\n";
        $renderer = new Doku_Renderer();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

    function test_dd_with_ul_followed_by_2nd_dl() {
        $in = "\n"
              ."  ; Term\n"
              ."  : Some parts:\n"
              ."  * Part 1\n"
              ."  * Part 2\n"
              ."\n"
              ."  ; Another term : Definition\n";
        $expected = "\n"
            ."<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Some parts:<ul>\n"
            ."<li class=\"level1\"><div class=\"li\"> Part 1</div>\n"
            ."</li>\n"
            ."<li class=\"level1\"><div class=\"li\"> Part 2</div>\n"
            ."</li>\n"
            ."</ul>\n"
            ."</dd>\n"
            ."</dl>\n"
            ."\n"
            ."<dl class=\"plugin_definitionlist\">\n"
            ."<dt><span class='term'>Another term</span></dt>\n"
            ."<dd>Definition</dd>\n"
            ."</dl>\n";
        $renderer = new Doku_Renderer();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

}
