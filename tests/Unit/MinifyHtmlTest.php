<?php

namespace Tests\Unit;

use App\Http\Middleware\MinifyHtml;
use PHPUnit\Framework\TestCase;

class MinifyHtmlTest extends TestCase
{
    public function test_removes_indentation_blank_lines_and_comments(): void
    {
        $html = "<div>\n    <!-- komentar -->\n\n    <p>Halo</p>\n\n\n    <span>A</span>   \n</div>\n";

        $this->assertSame("<div>\n<p>Halo</p>\n<span>A</span>\n</div>\n", MinifyHtml::minify($html));
    }

    public function test_keeps_a_line_break_between_inline_elements_so_spacing_is_unchanged(): void
    {
        $out = MinifyHtml::minify("<a>satu</a>\n        <a>dua</a>");

        $this->assertSame("<a>satu</a>\n<a>dua</a>", $out);
        $this->assertStringContainsString("\n", $out);
    }

    public function test_script_style_textarea_and_pre_are_left_untouched(): void
    {
        $script = "<script>\n    const a = `\n        indent  \n    `;\n    // komentar\n</script>";
        $style = "<style>\n    .a {\n        color: red;\n    }\n</style>";
        $textarea = "<textarea>\n    baris 1\n\n    baris 2\n</textarea>";
        $pre = "<pre>\n  a\n\n    b\n</pre>";

        $html = "<div>\n    {$script}\n    {$style}\n    {$textarea}\n    {$pre}\n</div>";
        $out = MinifyHtml::minify($html);

        foreach ([$script, $style, $textarea, $pre] as $block) {
            $this->assertStringContainsString($block, $out);
        }
    }

    public function test_conditional_comments_are_preserved_and_normal_ones_removed(): void
    {
        $out = MinifyHtml::minify("<!--[if IE]><p>IE</p><![endif]-->\n<!-- hapus -->\n<p>x</p>");

        $this->assertStringContainsString('<!--[if IE]>', $out);
        $this->assertStringNotContainsString('hapus', $out);
    }

    public function test_multiline_attribute_values_stay_valid(): void
    {
        $html = "<div x-data=\"{\n        a: 1,\n        b: 2\n    }\" class=\"a\n        b\">x</div>";
        $out = MinifyHtml::minify($html);

        $this->assertStringContainsString("x-data=\"{\na: 1,\nb: 2\n}\"", $out);
        $this->assertStringContainsString("class=\"a\nb\"", $out);
    }

    public function test_is_idempotent(): void
    {
        $html = "<ul>\n  <li>a</li>\n\n  <li>b</li>\n</ul>\n<script>\n  var x = 1;\n</script>";
        $once = MinifyHtml::minify($html);

        $this->assertSame($once, MinifyHtml::minify($once));
    }
}
