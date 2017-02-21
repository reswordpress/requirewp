<?php
/*
Plugin Name: RequireWP
Plugin URI:  https://developer.wordpress.org/plugins/requirewp/
Description: Uses Require.js to load scripts asynchronously to increase site load speed. Designed to work with HTTP/2.
Version:     1.0.2
Author:      Jim Robinson
Author URI:  https://techie-jim.net/
License:     GPL2+
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: requirewp
*/

if( !class_exists( 'RequireWP') ) {
    return;
}

/**
 * Class used to manage Require.js scripts.
 *
 * @package RequireWP
 * @since 1.0
 */
class RequireWP extends WP_Scripts {

    const
        /**
         * Array of handles and the shim export variable name.
         *
         * @var array
         */
        SHIM_EXPORTS_DEFAULTS = [
            'utils' => 'wpCookies', 'common' => 'adminMenu', 'wp-a11y' => 'wp.a11y',
            'sack' => 'sack', 'quicktags' => 'QTags',
            'colorpicker' => 'ColorPicker', 'editor' => 'switchEditors',
            'wp-fullscreen-stub' => 'wp.editor.fullscreen',
            'wp-ajax-response' => 'jQuery.parseAjaxResponse',
            'wp-pointer' => 'jQuery.pointer', 'autosave' => 'wp.autosave',
            'heartbeat' => 'wp.heartbeat', 'wp-lists' => 'wpList',
            'prototype' => 'Prototype', 'scriptaculous-root' => 'Scriptaculous',
            'scriptaculous-builder' => 'Builder',
            'scriptaculous-dragdrop' => 'Droppables',
            'scriptaculous-effects' => 'Effect',
            'scriptaculous-slider' => 'Control.Slider',
            'scriptaculous-sound' => 'Sound',
            'scriptaculous-controls' => 'Autocompleter',
            'cropper' => 'Cropper', 'jquery-form' => 'jQuery.fn.ajaxForm',
            'jquery-color' => 'jQuery.Color', 'schedule' => 'jQuery.scheduler',
            'jquery-query' => 'jQuery.query', 'jquery-hotkeys' => 'jQuery.hotkeys',
            'jquery-table-hotkeys' => 'jQuery.table_hotkeys',
            'jquery-touch-punch' => 'jQuery.ui.mouse.prototype.mouseInit',
            'jquery-migrate' => 'jQuery.migrateVersion',
            'jquery-serialize-object' => 'jQuery.fn.serializeObject',
            'suggest' => 'jQuery.suggest', 'imagesloaded' => 'imagesLoaded',
            'jquery-masonry' => 'jQuery.Masonry', 'thickbox' => 'tb_init',
            'jcrop' => 'jQuery.Jcrop', 'swfobject' => 'swfobject',
            'plupload' => 'wp.Uploader', 'plupload-handlers' => 'fileQueued',
            'wp-plupload' => 'wp.Uploader',
            'swfupload' => 'SWFUpload', 'comment-reply' => 'addComment',
            'json2' => 'JSON', 'underscore' => '_', 'backbone' => 'Backbone',
            'wp-util' => 'wp.ajax', 'wp-backbone' => 'wp.Backbone',
            'revisions' => 'wp.revisions',
            'imgareaselect' => 'jQuery.imgAreaSelect', 'mediaelement' => 'mejs',
            'wp-mediaelement' => 'wp.mediaelement', 'froogaloop' => 'Froogaloop',
            'wp-playlist' => 'WPPlaylistView',
            'password-strength-meter' => 'wp.passwordStrength',
            'user-profile' => 'generatePassword', 'wplink' => 'wpLink',
            'wpdialogs' => 'wp.wpdialog', 'word-count' => 'wp.utils.WordCounter',
            'media-upload' => 'send_to_editor',
            'hoverIntent' => 'jQuery.fn.hoverIntent',
            'customize-base' => 'wp.customize',
            'customize-loader' => 'wp.customize.Events.open',
            'customize-preview' => 'wp.customize.Preview',
            'customize-models' => 'wp.customize.HeaderTool.ImageModel',
            'customize-views' => 'wp.customize.HeaderTool.CurrentView',
            'customize-controls' => 'wp.customize.Setting',
            'customize-selective-refresh' => 'wp.customize.Class.initialize',
            'customize-widgets' => 'wp.customize.Widgets',
            'customize-preview-widgets' => 'wp.customize.selectiveRefresh.partialConstructor.widget',
            'customize-nav-menus' => 'wp.customize.Menus',
            'customize-preview-nav-menus' => 'wp.customize.selectiveRefresh.partialConstructor.nav_menu_instance',
            'wp-custom-header' => 'wp.customHeader',
            'shortcode' => 'wp.shortcode', 'media-models' => 'wp.media',
            'wp-embed' => 'wp.receiveEmbedMessage',
            'media-views' => 'CollectionAdd', 'media-editor' => 'wp.media.editor',
            'media-audiovideo' => 'wp.media.mixin', 'mce-view' => 'wp.mce',
            'wp-api' => 'wp.api'
        ],
        /**
         * Regular expression to match the schema (protocol) of a URL/URI.
         *
         * @var string
         */
        REGEXP_SCHEMA = '/^([[:alpha:]][[:alnum:]\+\-\.]*\:)(?=\/\/)/',
        /**
         * Regular expression to match ".js" at the end of a URL/URI.
         *
         * @var string
         */
        REGEXP_JS = '/\.js\/?$/';

    public static
        /**
         * Require.js configuration array.
         *
         * @var array
         */
        $rwp_config = [
            'paths' => [], 'shim' => [], 'map' => [ '*' => [] ],
            'bundles' => [], 'waitSeconds' => 200
        ],
        /**
         * Array storage for Require.js-formatted require scripts.
         *
         * @var array
         */
        $rwp_script_output = [],
        /**
         * Array storage for Require.js-formatted define scripts.
         *
         * @var array
         */
        $rwp_define_output = [],
        /**
         * If true, will apply the domFirst! plugin before all requires that
         * do not specifically forbid it via a similarly-named script property.
         *
         * @var bool
         */
        $rwpDomFirst = true;

    /**
     * Adds base require.js configuration & required WordPress filters &
     * actions. Calls parent constructor.
     */
    public function __construct() {
        if( !is_admin() ) {
            static::add_rwp_config('baseUrl', home_url('/'));
            static::add_rwp_config('paths', [
                'wp-includes' => $this->_rwp_relativeUrl(includes_url()),
                'wp-admin' => $this->_rwp_relativeUrl(admin_url()),
                'plugins' => $this->_rwp_relativeUrl(WP_PLUGIN_URL),
                'theme' => $this->_rwp_relativeUrl(get_template_directory_uri()),
                'themes' => $this->_rwp_relativeUrl(get_theme_root_uri( false )),
                'RequireWP' => $this->_rwp_relativeUrl(
                    plugins_url( 'js', __FILE__ )
                )
            ]);
            add_filter( 'script_loader_tag',
                [ $this, '_rwp_modify_script' ], 10, 3
            );
            add_filter( 'rwp_type_config-shim',
                [ get_called_class(), '_rwp_type_config_shim' ], 10, 3);
            add_filter( 'rwp_script_type',
                [ get_called_class(), '_rwp_script_type' ], 10, 3);
            $this->_rwp_correct_defaults();
            add_action( 'wp_enqueue_scripts', [$this, '_rwp_add_scripts'] );
        }
        parent::__construct();
    }

    /**
     * Adds a RequireWP property value to a given script handle.
     *
     * @param string $handle - script handle to add property
     * @param string $key - property key to add value
     * @param {*} $value - property value to add
     */
    public function rwp_add_property( $handle, $key, $value ) {
        $wp_scripts = wp_scripts();
        if( !isset($wp_scripts->registered[$handle]) ) return;
        if( !isset($wp_scripts->registered[$handle]->rwp) )
            $wp_scripts->registered[$handle]->rwp = (object) [];
        $wp_scripts->registered[$handle]->rwp->$key = $value;
    }

    /**
     * Get the property represented by key in the script defined by handle.
     *
     * @param string $handle - script handle to get property for
     * @param string $key - key of property to return
     * @return {*}
     */
    public function rwp_get_property( $handle, $key ) {
        $wp_scripts = wp_scripts();
        if( !isset($wp_scripts->registered[$handle]->rwp->$key) )
            return null;
        return $wp_scripts->registered[$handle]->rwp->$key;
    }

    /**
     * rwp_add_path - Adds a Require.js relative path to the given handle and script object.
     *
     * @param string $handle
     * @param _WP_Dependency $script
     */
    public function rwp_add_path($handle, _WP_Dependency $script) {
        $wp_scripts = wp_scripts();
        static::rwp_add_version($handle, $script);
        static::rwp_add_type($handle, $script);
        $src = static::_rwp_relativeUrl($script->src, $handle);
        if( $src != '' ) {
            static::add_rwp_config('paths', [ $handle => $src ]);
        } else {
            $this->rwp_generate_define($handle, $script);
        }
    }

    /**
     * Generate a require.js define function for the given handle and script object. Append to the $rwp_define_output array property with the given $handle.
     *
     * @param script $handle
     * @param _WP_Dependency $script
     */
    public function rwp_generate_define($handle, $script) {
        $returnVar = (string)
            $this->rwp_get_property($handle, 'rwp_return_var');
        static::$rwp_define_output[$handle] = 'define(\'' . esc_js($handle) . '\',' .
            json_encode( [ $script->deps[0]] ) . ',function(){var r=[];' .
                implode(';', array_map(function($v){
                    return 'r.push(require([\'' . esc_js($v) . '\']))';
                }, array_splice($script->deps, 1))) . ';' .
                'return ' . ($returnVar != '' ? $returnVar : 'r[0]') . ';' .
            '});';
    }

    /**
     * Processes the items and dependencies.
     *
     * Processes the items passed to it or the queue, and their dependencies.
     *
     * @param mixed $handles Optional. Items to be processed: Process queue (false), process item (string), process items (array of strings).
     * @param mixed $group   Group level: level (int), no groups (false).
     * @return array Handles of items that have been processed.
     */
    public function do_items($handles = false, $group = false) {
        $parent = parent::do_items($handles, $group);
        if( is_admin() ) return $parent;
        if( $group < 1 ) {
            static::print_rwp_config();
            static::print_requirejs();
        }
        $this->print_rwp_defines($group);
        $this->print_rwp_scripts($group);
        return $parent;
    }

    /**
     * Processes a script dependency.
     *
     * @see WP_Dependencies::do_item()
     * @param string $handle    The script's registered handle.
     * @param int|false $group  Optional. Group level: (int) level, (false) no groups. Default false.
     * @return bool True on success, false on failure.
     */
    public function do_item($handle, $group = false) {
        if( !is_admin() ) {
            $wp_scripts = wp_scripts();
            $this->rwp_add_path($handle, $wp_scripts->registered[$handle]);
        }
        return parent::do_item($handle, $group);
    }

    /**
    * Register an item.
    *
    * Registers the item if no item of that name already exists.
    *
    * @param string           $handle Name of the item. Should be unique.
    * @param string           $src    Full URL of the item, or path of the item relative to the WordPress root directory.
    * @param array            $deps   Optional. An array of registered item handles this item depends on. Default empty array.
    * @param string|bool|null $ver    Optional. String specifying item version number, if it has one, which is added to the URL
    *                                 as a query string for cache busting purposes. If version is set to false, a version
    *                                 number is automatically added equal to current installed WordPress version.
    *                                 If set to null, no version is added.
    * @param mixed            $args   Optional. Custom property of the item. NOT the class property $args. Examples: $media, $in_footer.
    * @return bool Whether the item has been registered. True on success, false on failure.
    */
    public function add($handle, $src, $deps = array(), $ver = false,
        $args = null
    ) {
        list($handle, $src, $deps, $ver, $args) = apply_filters(
            'rwp_add', [ $handle, $src, $deps, $ver, $args ]
        );
        list($handle, $src, $deps, $ver, $args) = apply_filters(
            'rwp_add-' . $handle, [ $handle, $src, $deps, $ver, $args ]
        );
        return parent::add($handle, $src, $deps, $ver, $args);
    }

    /**
     * Get the configuration value with the specified key.
     *
     * @param string $key Configuration key to retrieve.
     * @return mixed
     */
    public function get_rwp_config($key) {
        return isset(static::$rwp_config[$key]) ? static::$rwp_config[$key] :
            null;
    }

    /**
     * Print RequireWP-generated scripts for the given group
     * (header=0/footer=1).
     *
     * @param mixed $group
     */
    public function print_rwp_scripts($group) {
        foreach( static::$rwp_script_output as $handle => $script ) {
            if( !$this->groups[$handle] == $group ) {
                continue;
            }
            echo $script, "\n";
        }
    }

    /**
     * Print RequireWP-generated define scripts for the given group
     * (header=0/footer=1).
     *
     * @param mixed $group
     */
    public function print_rwp_defines($group) {
        foreach( static::$rwp_define_output as $handle => $script ) {
            if( !$this->groups[$handle] == $group ) {
                continue;
            }
            echo '<script type="text/javascript">', $script, '</script>',
                "\n";
        }
    }

    /**
     * WordPress filters that alter default scripts to correct for differences
     * between WordPress's default setup and Require.js.
     */
    public function _rwp_correct_defaults() {
        $wp_scripts =& $this;

        // $ary = [ 0 => $handle, 1 => $src, 2 => $deps, 3 => $ver,
        //  4 => $args
        // ]

        add_filter( 'rwp_add-jquery',
            function($ary) use ($wp_scripts) {
                $wp_scripts->rwp_add_property(
                    $ary[0], 'rwp_return_var', 'jQuery'
                );
                return $ary;
            }, 10, 5
        );

        add_filter( 'rwp_add-jquery-migrate',
            function($ary) {
                $ary[2][] = 'jquery-core';
                $ary[3] = $ary[3] | '1.2';
                return $ary;
            }, 10, 5
        );

        add_filter( 'rwp_script_type-jquery-core', function($type){
            return '';
        });

    }

    /**
     * Filter handler that replaces the primary script tag generated by
     * WordPress with one specific to RequireWP/Require.js.
     *
     * Returns an empty string to prevent WordPress from echoing the default
     * script tag.
     *
     * @param string $tag
     * @param string $handle
     * @param string $src
     * @return string
     */
    public function _rwp_modify_script($tag, $handle, $src) {
        if( $this->rwp_is_path_only($handle) ) {
            return '';
        }
        $wp_scripts = wp_scripts();
        $search = "<script type='text/javascript' src='$src'></script>";
        $require = $this->rwp_generate_require($handle);
        $domFirst = $this->rwp_is_dom_first($handle);
        $output = '<script type=\'text/javascript\'>' .
            sprintf( static::rwp_generate_script_domFirst($domFirst), $require
            ) . '</script>';
        static::$rwp_script_output[$handle] = str_replace(
            $search, $output, $tag
        );
        return '';
    }

    /**
     * Created the require([]) string for a given script.
     *
     * @param string $handle The string handle (key) of a script
     * @return string
     */
    public function rwp_generate_require($handle) {
        $afterRequired = $this->rwp_get_property($handle, 'afterRequired');
        return 'require([\'' . esc_js($handle) . '\']' .
            (!empty($afterRequired) ? ',function(){' . $afterRequired . '}' :
                '') . ');';
    }

    /**
     * Returns true if the global rwpDomFirst variable is true and the
     * script domFirst variable is null (not defined) or true; or the
     * global variable is false and the script variable is set and true.
     *
     * @param mixed $handle null or a string handle of a script
     * @return bool
     */
    public function rwp_is_dom_first($handle = null) {
        $scriptDomFirst = $this->rwp_get_property($handle, 'domFirst');
        $scriptDomIsNull = $scriptDomFirst === null;
        if( static::$rwpDomFirst ) {
            return $scriptDomIsNull || $scriptDomFirst;
        }
        return !$scriptDomIsNull && $scriptDomFirst;
    }

    /**
     * Check if the script with the given handle has the pathOnly property set.
     * When true, it will not generate a require([]) but the path will still be
     * added to the Require.js configuration for use in a custom script (as
     * long as the script is queued -- registered scripts will not be added).
     *
     * @param string $handle Handle of script to check if pathOnly property is
     * set.
     * @return bool
     */
    public function rwp_is_path_only($handle) {
        return $this->rwp_get_property($handle, 'pathOnly') == true;
    }

    /**
     * Adds the base Require.js scripts for use when needed.
     */
    public function _rwp_add_scripts() {

        // domReady plugin
        wp_enqueue_script( 'domReady',
            plugins_url( 'js/domReady.js', __FILE__), null, '2.0.1'
        );
        $this->rwp_add_property( 'domReady', 'domFirst', false );
        $this->rwp_add_property( 'domReady', 'pathOnly', true );

        // CoffeeScript plugin
        wp_register_script( 'cs', plugins_url( 'js/cs.js', __FILE__ ),
            null, '0.5.0'
        );
        $this->rwp_add_property( 'cs', 'pathOnly', true );

        // i18n (internationalization) plugin
        wp_register_script( 'i18n', plugins_url( 'js/i18n.js', __FILE__ ),
            null, '2.0.6'
        );
        $this->rwp_add_property( 'i18n', 'pathOnly', true );

        // text plugin
        wp_register_script( 'text', plugins_url( 'js/text.js', __FILE__ ),
            null, '2.0.15'
        );
        $this->rwp_add_property( 'text', 'pathOnly', true );

    }

    public static function rwp_generate_script_domFirst($domFirst) {
        return $domFirst ? 'require([\'domReady!\'],function(){%s});' : '%s';
    }

    /**
     * Add a Require.js configuration variable with an optional subkey.
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $subkey
     */
    public static function add_rwp_config($key, $value, $subkey = null) {
        if( $key == '' ) return;
        if( $subkey !== null ) {
            static::add_rwp_config_subkey($key, $value, $subkey);
        } else {
            if( isset(static::$rwp_config[$key]) &&
                is_array(static::$rwp_config[$key])
            ) {
                $value = array_merge( (array) $value,
                    (array) static::$rwp_config[$key]
                );
            }
            static::$rwp_config[$key] = apply_filters(
                'rjs_config_value_' . $key, $value
            );
        }
    }

    /**
     * Add a Require.js configuration variable with a subkey.
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $subkey
     */
    public static function add_rwp_config_subkey($key, $value, $subkey) {
        if( isset(static::$rwp_config[$key][$subkey]) &&
            is_array(static::$rwp_config[$key][$subkey])
        ) {
            $value = array_merge( (array) $value,
                (array) static::$rwp_config[$key][$subkey]
            );
        }
        if( !isset(static::$rwp_config[$key]) ) {
            static::$rwp_config[$key] = [];
        }
        static::$rwp_config[$key][$subkey] = apply_filters('rjs_config_value_' . $key,
            $value
        );
    }

    /**
     * Print the Require.js loader script tag.
     */
    public static function print_requirejs() {
        ?><script type="text/javascript" src="<?=
            plugins_url( 'js/require.js', __FILE__) ?>"></script><?php
        echo "\n";
    }

    /**
     * Print the Require.js configuration script variable.
     */
    public static function print_rwp_config() {
        ?><script type="text/javascript">(function(){var rConfig=<?=
            json_encode((object)static::$rwp_config)
            ?>,urlArgsFnc=function(id, url){<?php
                ?>var newUrl='',idx='',hasArgs=url.indexOf('?')!==-1;<?php
                ?>if(!('urlVars' in this)||!(id in this['urlVars']))<?php
                    ?>return newUrl;<?php
                ?>for(idx in this['urlVars'][id]){<?php
                    ?>newUrl=newUrl+(hasArgs?'&':'?')+<?php
                        ?>encodeURIComponent(idx)+'='+<?php
                        ?>encodeURIComponent(this['urlVars'][id][idx]);<?php
                    ?>if(!hasArgs)hasArgs=true;<?php
                ?>}<?php
                ?>return newUrl;<?php
            ?>};<?php
            ?>rConfig.urlArgs=urlArgsFnc;<?php
            ?>if(typeof require!=typeof undefined&&'config' in require){<?php
                ?>require.config(rConfig);<?php
            ?>}else{<?php
                ?>require=rConfig;<?php
            ?>}<?php
        ?>}());</script><?php echo "\n";
    }

    /**
     * Filters out the base domain to return just the URL relative to the
     * base/root for Require.js.
     *
     * @staticvar mixed $homeLen Length of the root home URL.
     * @param string    $url Full URL to the script.
     * @param string    $handle Script handle.
     * @return string
     */
    public static function _rwp_relativeUrl($url, $handle = false) {
        if( substr( $url, 0, 2 ) == '//' ) {
            $altUrl = preg_replace( static::REGEXP_JS, '', $url );
        } else {
            $altUrl = preg_replace( static::REGEXP_JS, '', ltrim( $url, '/' ) );
        }
        if( false === ($len = static::_rwp_url_lenRelative($altUrl)) ) {
            return $altUrl;
        }
        return substr( $altUrl, $len );
    }

    /**
     * Checks if the input URL matches the home URL/URI with or without the
     * schema (protocol). If it matches the beginning of the string, the length
     * of the root path is returned.
     *.
     * @param string    $url Full URL to the script.
     * @return mixed
     */
    public static function _rwp_url_lenRelative($url) {
        static $homeUrls = [];
        if( !isset($homeUrls['url']) ) {
            $homeUrls['url']['rel'] = preg_replace( static::REGEXP_SCHEMA, '',
                ($homeUrls['url']['base'] = trailingslashit( home_url('/') ))
            );
            $homeUrls['len'] = array_map( 'strlen', $homeUrls['url'] );
        }
        if( substr($url, 2) == '//' &&
            strpos( $url, $homeUrls['url']['rel'] ) === 0
        ) {
            return $homeUrls['len']['rel'];
        } elseif( strpos( $url, $homeUrls['url']['base'] ) === 0 ) {
            return $homeUrls['len']['base'];
        }
        return false;
    }

    /**
     * Add a version URL variable (for Require.js) for the given script.
     *
     * @param string $handle
     * @param _WP_Dependency $script
     */
    public static function rwp_add_version($handle, _WP_Dependency $script) {
        $wp_scripts = wp_scripts();
        if( $script->ver == '' || $script->src == '' ) return;
        static::add_rwp_config_subkey( 'urlVars', [
            'ver' => $script->ver
        ], $handle);
    }

    /**
     * Adds a specific type (bundle, module, shim, etc.) to
     * the Require.js configuration for the given script.
     *
     * @param string $handle
     * @param _WP_Dependency $script
     */
    public static function rwp_add_type($handle, _WP_Dependency $script) {
        $wp_scripts = wp_scripts();
        if( empty($script->deps) ) {
            return;
        }
        if( $script->src == '' ) {
            foreach( $script->deps as $dep ) {
                if( in_array( $dep, $wp_scripts->to_do ) ) continue;
                static::rwp_add_type($dep, $wp_scripts->registered[$dep]);
            }
            return;
        }
        $type = apply_filters('rwp_script_type-' . $handle,
            apply_filters('rwp_script_type', 'shim', $handle, $script),
            $handle, $script
        );
        $configVar = apply_filters( 'rwp_type_config-' . $type, [],
            $handle, $script
        );
        static::add_rwp_config( $type, [ $handle => $configVar ] );
    }

    /**
     * Enable or disable the global domFirst variable. When enabled, wraps
     * all require scripts in a require(['domReady!']) to require the dom
     * be loaded first (will not affect manually added require([]) scripts).
     *
     * @param bool $tf True to enable or false to disable.
     * @return bool
     */
    public static function rwp_set_domFirst($tf) {
        return (static::$rwpDomFirst = $bool == true);
    }

    /**
     * Default filter handler for shim configuration.
     *
     * @param array $configAry Array of Require.js properties for the given script type.
     * @param string $handle Script key string.
     * @param _WP_Dependency $script Script object.
     * @return array
     */
    public static function _rwp_type_config_shim($configAry, $handle, $script) {
        $configAry['deps'] = $script->deps;
        if( isset(static::SHIM_EXPORTS_DEFAULTS[$handle]) ) {
            $configAry['exports'] = static::SHIM_EXPORTS_DEFAULTS[$handle];
        }
        return $configAry;
    }

    /**
     * Default filter handler for script type. Will replace with the value
     * of the "rwp_script_type" key if it is set as extra data in the script
     * object.
     *
     * @param string $type
     * @param string $handle
     * @param _WP_Dependency $script
     * @return string
     */
    public static function _rwp_script_type($type, $handle, $script) {
        $wp_scripts = wp_scripts();
        $newType = $wp_scripts->get_data($handle, 'rwp_script_type');
        if( $newType !== false ) {
            return $newType;
        }
        return $type;
    }

}

$GLOBALS['wp_scripts'] = new RequireWP();
