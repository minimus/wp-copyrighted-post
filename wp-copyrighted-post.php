<?php
/*
Plugin Name: Copyrighted Post
Plugin URI: http://www.simplelib.com/?p=166
Description: Adds copyright notice in the end of each post of your blog. Visit <a href="http://www.simplelib.com/">SimpleLib blog</a> for more details.
Version: 1.2.22
Author: minimus
Author URI: http://blogcoding.ru
*/

/*  Copyright 2009 - 2010, minimus  (email : minimus@simplelib.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!class_exists('CopyrightedPost')) {
    class CopyrightedPost
    {
        public array $cpInitOptions = array(
            'owner' => 'author',
            'customName' => '',
            'singlePost' => 'true',
            'crString' => 'All rights reserved.',
            'crStringEx' => '',
            'excludeID' => '',
            'excludeCat' => ''
        );

        function __construct()
        {
            define('WCP_OPTIONS', 'CopyrightedPostAdminOptions');

            $plugin_dir = basename(dirname(__FILE__));
            if (function_exists('load_plugin_textdomain'))
                load_plugin_textdomain('wp-copyrighted-post', false, $plugin_dir);

            add_action('admin_init', array(&$this, 'initSettings'));
            add_action('activate_wp-copyrighted-post/wp-copyrighted-post.php', array(&$this, 'onActivate'));
            add_action('deactivate_wp-copyrighted-post/wp-copyrighted-post.php', array(&$this, 'onDeactivate'));
            add_filter('the_content', array(&$this, 'addCopyright'), 7);
        }

        function onActivate()
        {
            $cpAdminOptions = $this->getAdminOptions();
            update_option(WCP_OPTIONS, $cpAdminOptions);
        }

        function onDeactivate()
        {
            delete_option(WCP_OPTIONS);
        }

        //Returns an array of admin options
        function getAdminOptions(): array
        {
            $cpAdminOptions = $this->cpInitOptions;
            $cpAdminOptions['crString'] = __("All rights reserved.", 'wp-copyrighted-post');
            $cpOptions = get_option(WCP_OPTIONS);
            if (!empty($cpOptions)) {
                foreach ($cpOptions as $key => $option) {
                    $cpAdminOptions[$key] = $option;
                }
            }
            return $cpAdminOptions;
        }

        function addCopyright($content): string
        {
            $cpOptions = $this->getAdminOptions();
            $xID = explode(',', $cpOptions['excludeID']);
            $xCat = explode(',', $cpOptions['excludeCat']);
            if (is_single() || ('false' === $cpOptions['singlePost']) || is_feed()) {
                $postId = get_the_ID();
                $postData = get_post($postId, ARRAY_A);
                $postDate = explode('-', $postData['post_date']);
                $postModifed = explode('-', $postData['post_modified']);
                switch ($cpOptions['owner']) {
                    case 'author':
                        $owner = "<a href='" . get_bloginfo('url') . "'>" . get_the_author() . "</a>";
                        break;
                    case 'blog':
                        $owner = "<a href='" . get_bloginfo('url') . "'>" . get_bloginfo('name') . "</a>";
                        break;
                    case 'site':
                        $url = explode('/', str_replace('http://', '', get_bloginfo('url')));
                        $owner = "<a href='" . get_bloginfo('url') . "'>" . $url[0] . "</a>";
                        break;
                    case 'custom':
                        $owner = htmlspecialchars_decode($cpOptions['customName']);
                        break;
                    default:
                        $owner = "<a href='" . get_bloginfo('url') . "'>" . get_the_author() . "</a>";
                }

                if (!empty($xID)) $outIt = !(in_array((string)$postId, $xID));
                else $outIt = true;
                if (!empty($xCat)) {
                    $categories = get_the_category();
                    foreach ($categories as $category) $outIt = ($outIt && !(in_array($category->cat_name, $xCat)));
                } else $outIt = ($outIt && true);

                $cpDate = ($postDate[0] === $postModifed[0]) ? $postDate[0] : $postDate[0] . " - " . $postModifed[0];
                $srString = htmlspecialchars_decode($cpOptions['crString']);
                $srStringEx = htmlspecialchars_decode($cpOptions['crStringEx']);

                if ($outIt) $content .= "\n<p style='text-align:left'>&copy; $cpDate, $owner. $srString $srStringEx</p>";
            }
            return $content;
        }

        function initSettings()
        {
            add_settings_section("wcp_section", __("Copyright Settings", 'wp-copyrighted-post'), array(&$this, "drawSection"), "reading");
            add_settings_field('owner', __("Copyright owner", 'wp-copyrighted-post'), array(&$this, 'drawRadioOption'), 'reading', 'wcp_section', array('optionName' => 'owner', 'description' => __('Select a variant of display the legal owner in line of the copyrights. For a blog of several authors it is better to select "Author Name".', 'wp-copyrighted-post'), 'options' => array('blog' => __('Blog Name', 'wp-copyrighted-post'), 'author' => __('Author Name', 'wp-copyrighted-post'), 'site' => __('Site Name (URL based)', 'wp-copyrighted-post'), 'custom' => __('Custom Name', 'wp-copyrighted-post'))));
            add_settings_field('customName', __("Custom Name of Copyrights owner", 'wp-copyrighted-post'), array(&$this, 'drawTextOption'), 'reading', 'wcp_section', array('optionName' => 'customName', 'description' => __('HTML tags allowed.', 'wp-copyrighted-post'), 'width' => 95));
            add_settings_field('crString', __("Define Copyright Notice String", 'wp-copyrighted-post'), array(&$this, 'drawTextOption'), 'reading', 'wcp_section', array('optionName' => 'crString', 'description' => __('This is a phrase that originated in copyright law as part of copyright notices. English: "All rights reserved."', 'wp-copyrighted-post') . ' ' . __('HTML tags allowed.', 'wp-copyrighted-post'), 'width' => 95));
            add_settings_field('crStringEx', __("Define Extended Copyright Notice String", 'wp-copyrighted-post'), array(&$this, 'drawTextOption'), 'reading', 'wcp_section', array('optionName' => 'crStringEx', 'description' => __('This is extended copyright notice string. You can place additional information here.', 'wp-copyrighted-post') . ' ' . __('HTML tags allowed.', 'wp-copyrighted-post'), 'width' => 95));
            add_settings_field('singlePost', __("Display in Single Post Only", 'wp-copyrighted-post'), array(&$this, 'drawRadioOption'), 'reading', 'wcp_section', array('optionName' => 'singlePost', 'description' => __('Select "Yes", if you want display copyright notice only at the end of post in single post viewing mode.', 'wp-copyrighted-post'), 'options' => array('true' => __('Yes', 'wp-copyrighted-post'), 'false' => __('No', 'wp-copyrighted-post'))));
            add_settings_field('excludeID', __("Exclude Posts", 'wp-copyrighted-post'), array(&$this, 'drawTextOption'), 'reading', 'wcp_section', array('optionName' => 'excludeID', 'description' => __('ID(s) of post(s) (comma separated without spaces). These posts are not subjects of copyrights.', 'wp-copyrighted-post'), 'width' => 95));
            add_settings_field('excludeCat', __("Exclude Categories", 'wp-copyrighted-post'), array(&$this, 'drawTextOption'), 'reading', 'wcp_section', array('optionName' => 'excludeCat', 'description' => __('Name(s) of category(ies) (comma separated without spaces). The posts in these categories are not subjects of copyrights. For example: Posts in "News" Category.', 'wp-copyrighted-post'), 'width' => 95));

            register_setting('reading', WCP_OPTIONS, array(&$this, 'sanitizeSettings'));
        }

        function drawSection()
        {
            echo __('Parameters of copyright notice in the end of each post of your blog (Copyrighted Post plugin).', 'wp-copyrighted-post');
        }

        function drawRadioOption($args)
        {
            $optionName = $args['optionName'];
            $options = $args['options'];
            $settings = $this->getAdminOptions();

            foreach ($options as $key => $option) {
                ?>
                <label for="<?php echo esc_attr($optionName) . '_' . esc_attr($key); ?>">
                    <input type="radio"
                           id="<?php echo esc_attr($optionName) . '_' . esc_attr($key); ?>"
                           name="<?php echo WCP_OPTIONS . '[' . esc_attr($optionName) . ']'; ?>"
                           value="<?php echo esc_attr($key); ?>" <?php checked($key, $settings[$optionName]); ?> />
                    <?php echo esc_attr($option); ?>
                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                <?php
            }
            echo "<p><em>" . esc_attr($args['description']) . "</em></p>";
        }

        function drawTextOption($args)
        {
            $optionName = $args['optionName'];
            $settings = $this->getAdminOptions();
            $width = $args['width'];
            ?>
            <input id="<?php echo esc_attr($optionName); ?>"
                   name="<?php echo WCP_OPTIONS . '[' . esc_attr($optionName) . ']'; ?>"
                   type="text"
                   value="<?php echo esc_attr($settings[$optionName]); ?>"
                   style="height: 22px; font-size: 11px; <?php if (!empty($width)) echo 'width: ' . esc_attr($width) . '%;' ?>"/>
            <?php
            echo "<p><em>" . esc_attr($args['description']) . "</em></p>";
        }

        function sanitizeSettings($input)
        {
            $output = $input;
            $output['customName'] = htmlspecialchars(stripslashes($input['customName']));
            $output['crString'] = htmlspecialchars(stripslashes($input['crString']));
            $output['crStringEx'] = htmlspecialchars(stripslashes($input['crStringEx']));
            $output['excludeID'] = htmlspecialchars(stripslashes($input['excludeID']));
            $output['excludeCat'] = htmlspecialchars(stripslashes($input['excludeCat']));
            $output['owner'] = preg_match("/^(blog|author|site|custom)$/i", $input['owner']) ? $input['owner'] : 'author';
            $output['singlePost'] = preg_match("/^(true|false)$/i", $input['singlePost']) ? $input['singlePost'] : 'true';
            return $output;
        }
    } // end class
} // end if

if (class_exists("CopyrightedPost")) {
    $minimus_copyrighted_post = new CopyrightedPost;
}
?>