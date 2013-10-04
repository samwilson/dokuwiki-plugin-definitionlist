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
        $expected = "\n<dl>\n"
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
        $expected = "\n<dl>\n"
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
        $expected = "\n<dl>\n"
            ."<dt><span class='term'>Term</span></dt>\n"
            ."<dd>Definition one\ncontinues</dd>\n"
            ."</dl>\n";

        $renderer = new Doku_Renderer_xhtml();
        $actual = $renderer->render($in, 'xhtml');
        $this->assertEquals($expected, $actual);
    }

}
