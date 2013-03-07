<?php
/*
Plugin Name: WP-Syntax Download Extension
Plugin URI: http://wordpress.org/extend/plugins/wp-syntax-download-extension
Description:  Make WP-Syntax highlighted code snippets downloadable.
Version: 1.1.0
Author: Akinori MUSHA
Author URI: http://akinori.org/
License: New BSD Licence
Text Domain: wpsde
Domain Path: languages
*/

/*
 * WP-Syntax Download Extension
 *
 * Copyright (c) 2010, 2011, 2012, 2013 Akinori MUSHA
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

class WP_Syntax_DownloadExtention {
    public static function process_download_request($postid, $filename, $download_p) {
        if (preg_match("/[\\r\\n]/u", $filename)) {
            header('HTTP', true, 400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo("Bad file name.\n");
            exit();
        }

        $post = &get_post($postid);

        if (!$post) {
            header('HTTP', true, 404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo("Post not found.\n");
            exit();
        }

        $content = $post->post_content;

        $doc = self::html_documentize($content);
        $nodelist = self::xpath($doc, sprintf("//pre[@filename='%s']/text()",
                                              preg_replace("/'/u", "''", $filename)));

        if ($nodelist->length == 0) {
            header('HTTP', true, 404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo("Code snippet not found.\n");
            exit();
        }

        $text = $nodelist->item(0)->nodeValue;
        $text = preg_replace("/^\\r?\\n/u", "", $text);

        if ($download_p) {
            header('Content-Type: application/octet-stream');
            header(sprintf('Content-Disposition: attachment; filename="%s"',
                           preg_replace("/\"/u", "\\\"", $filename)));
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
        }
        echo $text;
        exit();
    }

    public static function html_documentize($html) {
        // loadHTML() defaults the encoding to iso-8859-1!
        $doc = DOMDocument::loadHTML(<<< HTML
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  </head>
  <body>$html</body>
</html>
HTML
            );

        return $doc;
    }

    public static function xpath($doc, $expr) {
        $xpath = new DOMXPath($doc);
        return $xpath->query($expr);
    }

    public static function xpath_at($doc, $expr) {
        return self::xpath($doc, $expr)->item(0);
    }

    public static function html_body($doc) {
        return preg_replace(",^.*?<body>(.*)</body>.*$,siu", "$1", $doc->saveHTML(), 1);
    }

    public static function get_wp_syntax_var($name) {
        $class = new ReflectionClass('WP_Syntax');
        $array = $class->getDefaultProperties();
        return $array[$name];
    }

    public static function beforeFilter($content) {
        if (!is_callable('WP_Syntax', 'substituteToken'))
            return $content;

        return preg_replace_callback(
            "/\s*(<pre(?:\s+(?:[^>]+)|\s*)>)(.*)?(<\/pre>)\s*/siU",
            array(__CLASS__, 'substituteToken'),
            $content);
    }

    public static function substituteToken(&$match) {
        $pre_open  = $match[1];
        $code_text = $match[2];
        $pre_close = $match[3];

        $doc = self::html_documentize("$pre_open$pre_close");
        $pre = self::xpath_at($doc, "//body/pre");
        $body = $pre->parentNode;

        $lang = null;
        $line = null;
        $highlight = null;
        $filename = null;
        $escaped = false;
        $src = null;
        $extra_attributes = array();

        foreach ($pre->attributes as $attribute) {
            $name  = $attribute->name;
            $value = $attribute->value;

            switch ($name) {
            case "lang":
                $lang = $value;
                continue 2;
            case "line":
                $line = $value;
                continue 2;
            case "highlight":
                $highlight = $value;
                continue 2;
            case "src":
                $src = $value;
                continue 2;
            case "escaped":
                $escaped = ($value == "true");
                continue 2;
            case "filename":
                $filename = $value;
                break;
            }
            array_push($extra_attributes, $name);
        }

        if (is_null($filename)) {
            return $match[0];
        }

        foreach ($extra_attributes as $name) {
            $pre->removeAttribute($name);
        }
        $pre->setAttribute("escaped", "true");

        if ($escaped) {
            $code_text = htmlspecialchars_decode($code_text);
        }
        $pre->appendChild($doc->createTextNode($code_text));

        $html = self::html_body($doc);

        $wp_syntax_match = array($html, $lang, $line, "false", $highlight, $src, $code_text, $filename);

        return WP_Syntax::substituteToken($wp_syntax_match);
    }

    public static function afterFilter($content) {
        if (!is_callable('WP_Syntax', 'highlight'))
            return $content;

        /*
         * Do stuff instead of WP_Syntax::afterFilter(), which has
         * then become void since tags are gone.
         */
        return preg_replace_callback(
            '/<p>\s*' . self::get_wp_syntax_var('token') . '(\d{3})\s*<\/p>/si',
            array(__CLASS__, 'highlight'),
            $content);
    }

    public static function highlight($match) {
        $wp_syntax_matches = self::get_wp_syntax_var('matches');

        $html = WP_Syntax::highlight($match);
        $doc = self::html_documentize($html);
        $div = self::xpath_at($doc, "//body/div");

        $i = intval($match[1]);
        $wp_syntax_match = $wp_syntax_matches[$i];

        $filename = $wp_syntax_match[7];

        if (is_null($filename)) {
            return $html;
        }

        $this_url = plugins_url(basename(__FILE__), __FILE__);
        $postid = get_the_ID();

        $raw_url = sprintf("%s/%s/%s", $this_url, $postid, $filename);
        $download_url = sprintf("%s/%s/download/%s", $this_url, $postid, $filename);

        $div_caption = $doc->createElement("div");
        $div_caption->setAttribute("class", "wp_syntax_download");

        $div_filename = $doc->createElement("div");
        $div_filename->setAttribute("class", "wp_syntax_download_filename");

        $anchor = $doc->createElement("a");
        $anchor_name = "file-$filename";
        $anchor->setAttribute("name", $anchor_name);
        $anchor->setAttribute("href", "#" . urlencode($anchor_name));
        $anchor->setAttribute("title", $filename);
        $anchor->appendChild($doc->createTextNode($filename));

        $div_filename->appendChild($anchor);

        $div_actions = $doc->createElement("div");
        $div_actions->setAttribute("class", "wp_syntax_download_actions");

        $raw = $doc->createElement("a");
        $raw->setAttribute("href", $raw_url);
        $raw->appendChild($doc->createTextNode("raw"));

        $download = $doc->createElement("a");
        $download->setAttribute("href", $download_url);
        $download->appendChild($doc->createTextNode("download"));

        $div_actions->appendChild($raw);
        $div_actions->appendChild($doc->createTextNode(" "));
        $div_actions->appendChild($download);

        $div_caption->appendChild($div_filename);
        $div_caption->appendChild($div_actions);

        $div->insertBefore($div_caption, $div->firstChild);

        return self::html_body($doc);
    }

    public static function tinyMCEConfig($init) {
        $init["extended_valid_elements"] = preg_replace('/((?:^|,)pre\[.*?)\]/', '$1|dir|title|wrap|filename]', $init['extended_valid_elements']);

        return $init;
    }

    public static function default_css_url () {
        return plugins_url(basename(__FILE__, '.php') . ".css", __FILE__);
    }

    public static function head() {
        if (get_option('wpsde_disable_default_css') != 't') {
            printf(<<< HEAD
<link type="text/css" rel="stylesheet" href="%s" />
HEAD
                   , htmlspecialchars(self::default_css_url()));
        }

        $user_css = trim(get_option('wpsde_user_css'));
        if (!empty($user_css)) {
            printf(<<< STYLE
<style type="text/css">
<!--
%s
//-->
</style>
STYLE
                   , esc_html($user_css));
        }
    }

    public static function menu() {
        add_options_page('WP-Syntax Download Extension',
                         'WP-Syntax Download Extension',
                         8,
                         __FILE__,
                         array(__CLASS__, 'options'));
    }

    public static function options() {
        $option_names = array('wpsde_disable_default_css', 'wpsde_user_css');
        $options = array();

        foreach ($option_names as $name) {
            $value = get_option($name);
            $options[$name] = $value;
        }

        printf(<<< HEADER
<div class="wrap">
<h2>WP-Syntax Download Extension</h2>
HEADER
            );

        if (!is_callable('WP_Syntax', 'highlight')) {
            printf(<<< MESSAGE
<div class="updated">
<p><em style="color: red">%s</em></p>
</div>
MESSAGE
                   ,
                   htmlspecialchars(__('This plug-in does not work without WP-Syntax activated.', 'wpsde')));
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $wpsde_key = $_SESSION['wpsde_key'];
            unset($_SESSION['wpsde_key']);

            $wpsde_key_received = $_POST['wpsde_key'];

            foreach ($option_names as $name) {
                $options[$name] = $_POST[$name];
            }

            if (isset($wpsde_key) && $wpsde_key == $wpsde_key_received) {
                foreach ($options as $name => $value) {
                    update_option($name, $value);
                }

                printf(<<< MESSAGE
<div class="updated">
<p><strong>%s</strong></p>
</div>
MESSAGE
                       ,
                       htmlspecialchars(__('Options saved.', 'wpsde')));
            } else {
                printf(<<< MESSAGE
<div class="updated">
<p><em style="color: red">%s</em></p>
</div>
MESSAGE
                       ,
                       htmlspecialchars(__('Invalid form submission. (Reload or CSRF detected)', 'wpsde')));
            }
        }

        $wpsde_key = md5(SECURE_AUTH_KEY . time());
        $_SESSION['wpsde_key'] = $wpsde_key;

        printf(<<< FORM
<form method="POST">
<input type="hidden" name="wpsde_key" value="%s" />
<p>
<input type="checkbox" name="wpsde_disable_default_css" value="t" id="id_wpsde_disable_default_css"%s />
<label for="id_wpsde_disable_default_css">%s</label>
(<a href="%s" target="_blank">%s</a>)
<br />
</p>

<p>
<label for="id_wpsde_user_css">%s:</label><br />
<textarea name="wpsde_user_css" id="id_wpsde_user_css" cols="50" rows="10">%s</textarea>
</p>

<p class="submit">
<input type="submit" value="%s" class="button-primary" />
</p>
</form>
FORM
               ,
               htmlspecialchars($wpsde_key),
               ($options['wpsde_disable_default_css'] == 't' ? ' checked="checked"' : ''),
               htmlspecialchars(__('Disable default CSS', 'wpsde')),
               htmlspecialchars(self::default_css_url()),
               htmlspecialchars(__('view', 'wpsde')),
               htmlspecialchars(__('User CSS', 'wpsde')),
               esc_html($options['wpsde_user_css']),
               htmlspecialchars(__('Save Changes', 'wpsde')));

        printf(<<< FOOTER
</div>
FOOTER
            );
    }

    public static function setup() {
        add_action('wp_head', array(__CLASS__, 'head'), 99, 0);

        // If WP-Syntax is loaded earlier, rehook its filters so ours come first.

        $filters = array('the_content', 'the_excerpt', 'comment_text');

        foreach ($filters as $filter) {
            self::force_add_filter($filter, array(__CLASS__, 'beforeFilter'), 0);
            self::force_add_filter($filter, array(__CLASS__, 'afterFilter'),  99);
        }

        self::force_add_filter('tiny_mce_before_init', array(__CLASS__, 'tinyMCEConfig'), 99);

        add_action('admin_menu', array(__CLASS__, 'menu'));
    }

    public static function force_add_filter($filter, $method, $priority) {
        $super = array('WP_Syntax', $method[1]);
        $current_priority = has_filter($filter, $super);
        if ($current_priority === false) {
            add_filter($filter, $method, $priority);
        } else {
            remove_filter($filter, $super, $current_priority);
            add_filter($filter, $method, $current_priority);
            add_filter($filter, $super, $current_priority);
        }
    }
}

if (preg_match(",^/([0-9]+)/(download/)?([^/]+)$,u", $_SERVER['PATH_INFO'], $matches)) {
    // This require() cannot be put in a function because of variable scopes.
    require(dirname(__FILE__) . '/../../../wp-load.php');
    WP_Syntax_DownloadExtention::process_download_request($matches[1], urldecode($matches[3]), !empty($matches[2]));
} else {
    load_plugin_textdomain('wpsde', false, dirname(plugin_basename(__FILE__)) . '/languages');

    if (!session_id())
        session_start();

    WP_Syntax_DownloadExtention::setup();
}

// Local Variables:
// mode: php
// c-basic-offset: 4
// indent-tabs-mode: nil
// End:
