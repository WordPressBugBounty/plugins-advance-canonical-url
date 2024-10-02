<?php
/**
 * Intruders aren't allowed.
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('advance_canonical_url')) {

    class advance_canonical_url
    {

        private $options;

        public function __construct()
        {
            $this->acu_init();
        }

        /**
         * Initialization
         */
        public function acu_init()
        {

            register_activation_hook(__FILE__, array($this, 'acu_activation'));
            register_deactivation_hook(__FILE__, array($this, 'acu_deactivation'));

            if (is_admin()) {
                add_action('admin_menu', array($this, 'acu_plugin_page'));
                add_action('admin_init', array($this, 'acu_settings_page_init'));
            }

            remove_action('wp_head', 'rel_canonical');
            add_action('wp_head', array($this, 'acu_the_real_deal'));
        }

        /**
         * Plugin Activation
         */
        public function acu_activation()
        {
            $this->options = get_option('acu_options');
            $canonical_method = ($this->options['canonical_method'] ? $this->options['canonical_method'] : 'basic');
            $query_strings = ($this->options['query_strings'] ? $this->options['query_strings'] : 'yes');

            if (!isset($this->options['canonical_method'], $this->options['query_strings'])) {
                $defaults = array(
                    'canonical_method' => $canonical_method,
                    'query_strings' => $query_strings
                );
                update_option('acu_options', $defaults);
            }
        }

        /**
         * Plugin Deactivation
         */
        public function acu_deactivation()
        {
            delete_option('acu_options');
        }

        /**
         * Plugin Page
         */
        public function acu_plugin_page()
        {
            add_options_page(
                'Advance Canonical Settings',
                'Advance Canonical Settings',
                'manage_options',
                'advance_canonical_settings',
                array($this, 'acu_settings_form')
            );
        }

        /**
         * Settings Form
         */
        public function acu_settings_form()
        {
            ?>

            <div class="wrap">

                <form id="acu_form" class="acu_form" method="post" action="options.php">

                    <?php
                    settings_fields('acu_option_group');
                    do_settings_sections('acu-setting-admin');
                    submit_button();
                    ?>

                </form>

            </div>

            <?php
        }

        /**
         * Settings, Section & Fields
         */
        public function acu_settings_page_init()
        {
            register_setting(
                'acu_option_group',
                'acu_options',
                array($this, 'acu_sanitize_and_validate')
            );
            add_settings_section(
                'settings_advance_canonical',
                __('Advance Canonical Settings', 'acu'),
                array($this, 'acu_section_information'),
                'acu-setting-admin'
            );
            add_settings_field(
                'canonical_method',
                __('Canonical Method', 'acu'),
                array($this, 'select_canonical_method'),
                'acu-setting-admin',
                'settings_advance_canonical'
            );
            add_settings_field(
                'query_strings',
                __('Query Strings', 'acu'),
                array($this, 'select_query_strings'),
                'acu-setting-admin',
                'settings_advance_canonical'
            );
        }

        /**
         * Section Heading
         */
        public function acu_section_information()
        {
            ?>

            <h4><?php esc_html_e('Select your desired settings', 'acu'); ?></h4>

            <?php
        }

        /**
         * Options to Select
         */
        public function select_canonical_method()
        {
            $this->options = get_option('acu_options');
            ?>

            <select id="canonical_method" name="acu_options[canonical_method]">

                <option
                    value="basic" <?php echo isset($this->options['canonical_method']) ? (selected($this->options['canonical_method'], 'basic', false)) : (''); ?>>

                    <?php esc_html_e('Basic', 'acu'); ?>

                </option>

                <option
                    value="advance" <?php echo isset($this->options['canonical_method']) ? (selected($this->options['canonical_method'], 'advance', false)) : (''); ?>>

                    <?php esc_html_e('Advance', 'acu'); ?>

                </option>

            </select>

            <p class="acu-description"><?php esc_html_e('Choose the method to display canonical url throughout your website. If you choose Advance then each post, page and custom post will have its own canonical url setting.', 'acu'); ?></p>

            <?php
        }

        /**
         * Options to Select
         */
        public function select_query_strings()
        {
            $this->options = get_option('acu_options');
            ?>

            <select id="query_strings" name="acu_options[query_strings]">

                <option
                    value="yes" <?php echo isset($this->options['query_strings']) ? (selected($this->options['query_strings'], 'yes', false)) : (''); ?>>

                    <?php esc_html_e('Yes', 'acu'); ?>

                </option>

                <option
                    value="no" <?php echo isset($this->options['query_strings']) ? (selected($this->options['query_strings'], 'no', false)) : (''); ?>>

                    <?php esc_html_e('No', 'acu'); ?>

                </option>

            </select>

            <p class="acu-description"><?php esc_html_e('Do you want to remove query strings (the query sting displays right from the question mark: http://www.website.com/example.php?query=string)', 'acu'); ?></p>

            <?php
        }

        /**
         * Sanitization & Validation of the option
         * @param $acu_input
         * @return array
         */
        public function acu_sanitize_and_validate($acu_input)
        {
            $acu_new_input = array();

            if (isset($acu_input['canonical_method'])) {
                $acu_method_valid_values = array(
                    'basic',
                    'advance',
                );
                if (in_array($acu_input['canonical_method'], $acu_method_valid_values)) {
                    $acu_new_input['canonical_method'] = sanitize_text_field($acu_input['canonical_method']);
                } else {
                    wp_die("Invalid selection for Canonical Method, please go back and try again.");
                }
            }
            if (isset($acu_input['query_strings'])) {
                $acu_method_valid_values = array(
                    'yes',
                    'no',
                );
                if (in_array($acu_input['query_strings'], $acu_method_valid_values)) {
                    $acu_new_input['query_strings'] = sanitize_text_field($acu_input['query_strings']);
                } else {
                    wp_die("Invalid selection for Query Strings, please go back and try again.");
                }
            }
            return $acu_new_input;
        }

        /**
         * The Real Deal
         */
        public function acu_the_real_deal()
        {
            $this->options = get_option('acu_options');

            $acu_can_url_value = get_post_meta(get_the_ID(), '_acu_can_url_value', true);

            $value = esc_url($acu_can_url_value);

            /**
             * Basic Canonical URL
             */
            $basic = '<!-- Advance Canonical URL (Basic) -->';
	        if ( ! empty( $this->options['query_strings'] ) && 'no' === $this->options['query_strings'] ) {
                $basic .= '<link rel="canonical" content="' . esc_url( get_bloginfo('url') . '' . $_SERVER['REQUEST_URI'] ). '">';
            } else {
                $basic .= '<link rel="canonical" content="' . esc_url( get_bloginfo('url') . '' . strtok($_SERVER['REQUEST_URI'], '?') ) . '">';
            }
            $basic .= '<!-- Advance Canonical URL -->';

            /**
             * Advance Canonical URL based on the Canonical Meta Box Option
             */
            $advance = '<!-- Advance Canonical URL (Advance) -->';
            $advance .= '<link rel="canonical" content="' . $value . '">';
            $advance .= '<!-- Advance Canonical URL -->';

            switch (true) {
                case (is_front_page()):
                    echo $basic;
                    break;

                case (is_home()):
                    echo $basic;
                    break;

                case (is_single()):
                    $this->acu_render_canonical_url($basic, $advance, $value);
                    break;

                case (is_page()):
                    $this->acu_render_canonical_url($basic, $advance, $value);
                    break;

                default:
                    $this->acu_render_canonical_url($basic, $advance, $value);
            }
        }

        /**
         * Rendering the Canonical URL on frontend based on the basic and advance settings
         * @param $basic
         * @param $advance
         * @param $value
         */
        public function acu_render_canonical_url($basic, $advance, $value)
        {
            if ('basic' === $this->options['canonical_method']) {
                echo $basic;
            } else {
                echo (!empty($value)) ? $advance : $basic;
            }
        }
    }

}

$ACU = new advance_canonical_url();
