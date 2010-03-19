=== WP-Syntax Download Extension ===
Contributors: akinori-musha
Donate link: http://akinori.org/
Tags: Formatting, code, highlight, syntax, syntax highlighting
Requires at least: 2.0.2
Tested up to: 2.9.2
Stable tag: 1.0.1

This plug-in makes WP-Syntax highlighted code snippets downloadable from nice captions.

== Description ==

This plug-in adds download facility to
[WP-Syntax](http://wordpress.org/extend/plugins/wp-syntax/), putting a
pretty caption to each syntax highlighted code snippet, which contains
a permalink anchor and a couple of action buttons: "raw" and
"download".

The usage is easy.  You just put an attibute `filename="..."` to
a `<pre>` element and the snippet will have a caption.

I strongly recommend that you use the [TinyMCE Valid
Elements](http://wordpress.org/extend/plugins/tinymce-valid-elements/)
plug-in to make the non-standard attributes of the `<pre>` element
treated as valid and get preserved in the visual editor.  Add the
`pre` element, and its attributes `class`, `dir`, `escaped`,
`filename`, `id`, `lang`, `line`, `style`, `title`, and `wrap`.

You can alter the visual style of captions with CSS via admins menu. 

This plug-in was tested with PHP 5.2.12 and won't work with PHP 4.

== Installation ==

1. Upload the `wp-syntax-download-extension` directory to the
   `/wp-content/plugins/` directory.

2. Activate the plugin through the 'Plugins' menu in WordPress.  Of
   cource you have to have WP-Syntax activated as well.

3. Add an attribute `filename="filename.ext"` to the `<pre
   lang="...">` snippet that you want to make downloadable.

4. Adjust the design of captions with CSS via admins menu.

== Frequently Asked Questions ==

Q.  This plug-in does not work with WP-Syntax _&lt;some old
    version&gt;_!

A.  No surprise.  This plug-in depends on WP-Syntax's internals.  Fix
    it yourself or upgrade WP-Syntax.

Q.  This plug-in does not work with WP-Syntax _&lt;the latest
    version&gt;_!

A.  Bad surprise to me.  This plug-in depends on WP-Syntax's internals.
    Fix it yourself or wait until I fix it.

Q.  How do you customize the styles of the code?

A.  Amongst the options I am using the [WP-Syntax
    Hacktify](http://wordpress.org/extend/plugins/wp-syntax-hacktify/)
    plug-in.

== Screenshots ==

1. Output sample in the default style.

2. Output sample in a user customized style.

3. Admin options screen.

== Changelog ==

= 1.0.1 =
Do not fail when WP-Syntax is not installed or activated.
Improve this document.

= 1.0 =
First release.

== License ==

Copyright (c) 2010 Akinori MUSHA

All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:

1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
SUCH DAMAGE.
