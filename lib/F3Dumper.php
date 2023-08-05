<?php

/**
 * F3Dumper - A simple dumper for the PHP FatFreeFramework
 * 
 * The contents of this file are subject to the terms of the GNU General
 * Public License Version 3.0. You may not use this file except in
 * compliance with the license. Any of the license terms and conditions
 * can be waived if you get permission from the copyright holder.
 *
 *                                     __ _____       _         _ 
 * Created by:                        / /| ____|     | |       | |
 *  _ __ ___   __ _ _ __  _ __   ___ / /_| |__ ______| |__   __| |
 * | '_ ` _ \ / _` | '_ \| '_ \ / _ \ '_ \___ \______| '_ \ / _` |
 * | | | | | | (_| | | | | | | |  __/ (_) |__) |     | | | | (_| |
 * |_| |_| |_|\__,_|_| |_|_| |_|\___|\___/____/      |_| |_|\__,_|
 * 
 *     [ASCII-Art by: https://textkool.com/en/ascii-art-generator]
 * 
 * @copyright 2023 Manfred Hoffmann
 * @author Manfred Hoffmann <oss@manne65-hd.de>
 * @license GPLv3
 * @version 0.5.1-BETA 
 * @link https://github.com/manne65-hd/F3-Dumper
 * 
 **/

 namespace manne65hd;

 class F3Dumper {
    /** @var string Contains the current version tag of the F3Dumper-class */
    const VERSION='0.5.1-BETA';

    /** @var string Default CSS-span-style for boolean:TRUE */
    const CSS_SPAN_TRUE = 'background-color: green; color: white; font-weight: bolder;';

    /** @var string Default CSS-span-style for boolean:FALSE */
    const CSS_SPAN_FALSE = 'background-color: red; color:white; font-weight: bolder;';

    /** @var string Caption for the default dumper-category */
    const DEFAULT_CATEGORY = 'Uncategorized Dumps';

    /** @var string Default title-caption for direct dumps */
    const DIRECT_DUMP_TITLE = 'Direct dump via F3Dumper';

    /** @var object The FatFreeFramework-Object required to use F3-functions inside this class */
    protected $f3;

    /** @var string Holds the RAW name of the currently dumped variable */
    protected $dumped_name__raw;

    /** @var string Holds the name of the currently dumped variable formatted for HTML-output  */
    protected $dumped_name__4html;

    /** @var string Holds the name of the currently dumped variable formatted for console-output  */
    protected $dumped_name__4console;

    /** @var mixed Holds the RAW value of the currently dumped variable */
    protected $dumped_value__raw;

    /** @var string Holds the value of the currently dumped variable formatted for HTML-output  */
    protected $dumped_value__4html;

    /** @var string Holds the value of the currently dumped variable formatted for console-output  */
    protected $dumped_value__4console;

    /**  @var string CSS-span-style for boolean:TRUE */
    protected $css_span_true;

    /**  @var string CSS-span-style for boolean:FALSE */
    protected $css_span_false;

    /** @var integer Holds the count for dumps collected 4 HTML  */
    protected $count_html_dumps = 0;

    /** @var integer Holds the count for dumps collected 4 console  */
    protected $count_console_dumps = 0;

    /** @var array An array for the active dumper-categories */
    protected $categories_active = array(self::DEFAULT_CATEGORY);

    /** @var array Holds an array of counters for each dumper-category dumped to HTML */
    protected $categories_counter__html = array();

    /** @var array Holds an array of counters for each dumper-category dumped to console */
    protected $categories_counter__console = array();

    /** @var string The title-caption for direct dumps */
    protected $direct_dump_title;

    /**
     * The Constructor will return an instance of the F3Dumper-object
     * 
     * @param array $options You can pass an associative Array to configure the following options
     *  - 'css_span_true' => CSS to display boolean: TRUE (otherwise defaults to self::CSS_SPAN_TRUE)
     *  - 'css_span_false' => CSS to display boolean: FALSE (otherwise defaults to self::CSS_SPAN_FALSE)
     *  - 'direct_dump_title' => Custom title for direct dumps (otherwise defaults to self::DIRECT_DUMP_TITLE)
     * 
     * @return object An instance of the F3Dumper-object
     */
    public function __construct($options = array()){
        // We need to use the f3-object, so let's get an instance
        $this->f3 = \Base::instance();

        // Set the "has_dumped_data"-FLAGS to false by default, because we might not have data any in the end!
        $this->f3->set('has_dumped_data__4html', false);
        $this->f3->set('has_dumped_data__4console', false);
        // As long as method:setActiveCategories() hasn't been called, we assume the categories-feature not to be used!
        $this->f3->set('dumper_uses_categories', false);
        // We still need to be able to count for the default-category, so initialize it
        $this->categories_counter__html[self::DEFAULT_CATEGORY] = 0;
        $this->categories_counter__console[self::DEFAULT_CATEGORY] = 0;


        // Initalise arrays in the Framework-HIVE to be able to collect the dumped_data 
        $this->f3->set('dumped_data__4html', array()); 
        $this->f3->set('dumped_data__4console', array()); 

        // Assign custom css_span_stylesif avaiable
        $this->css_span_true = (isset($options['css_span_true'])) ? $options['css_span_true'] : self::CSS_SPAN_TRUE;
        $this->css_span_false = (isset($options['css_span_false'])) ? $options['css_span_false'] : self::CSS_SPAN_FALSE;
        // Assign a custom_direct_dump_title if available
        $this->direct_dump_title = (isset($options['direct_dump_title'])) ? $options['direct_dump_title'] : self::DIRECT_DUMP_TITLE;
    }

    /**
     * Returns PackageInformation
     *
     * @return array An associative array with information about the package
     */
    public function getPackageInfo() {
        // Read package-info from composer.json into an array
        $pkg_composer_json = json_decode(file_get_contents('../vendor/manne65hd/f3-dumper/composer.json'), JSON_OBJECT_AS_ARRAY );

        return array(
            'pkg_fullname'      => $pkg_composer_json['name'],
            'pkg_vendor'        => explode('/', $pkg_composer_json['name'])[0],
            'pkg_name'          => explode('/', $pkg_composer_json['name'])[1],
            'pkg_description'   => $pkg_composer_json['description'],
            'pkg_version'       => self::VERSION,// version-tag not recommended in composer.json, so pulling from CONST
            'pkg_license'       => $pkg_composer_json['license'],
            'pkg_authors'       => $pkg_composer_json['authors'],
            // detect if the package is included via SYMLINK (for local development)
            'pkg_is_symlinked'  => (str_contains(dirname(__FILE__), 'aaa_local_pkgdev')) ? true : false,
        );
    }
    
    /**
     * Set the active dumper-categories
     *
     * @param array $categories_active An array of caption-strings for each active dumper-category
     * 
     * @return void
     */
    public function setActiveCategories($categories_active) {
        // We'll remove duplicates before assigning
        $this->categories_active = array_unique($categories_active );
        // setActiveCategories() has been called, so let's set according FLAG!
        $this->f3->set('dumper_uses_categories', true);

        // initialize the array of counters for each dumper-category
        foreach($this->categories_active as $dumped_category) {
            $this->categories_counter__html[$dumped_category] = 0;
            $this->categories_counter__console[$dumped_category] = 0;
        };
    }


    /**
     * Collects dump-information for later display!
     *
     * @param string $dumped_name Name of the dumped variable/object
     * @param mixed $dumped_value Value of the dumped variable/object
     * @param array $dump_options You can pass an associative Array to configure the following options
     *  - 'category' (string) => The category for the current dump
     *  - 'description' (string) => A description for the current dump
     *  - 'log2console' (bool) => Logs the current dump to the (Developer-)console if TRUE
     * 
     * @return void
     */
	public function collect(string $dumped_name, mixed $dumped_value, array $dump_options = array()): void {
        // We will only collect dumps while in DEVELOPMENT!
        if ($this->f3->DEBUG === 0) {
            return;
        }

        // Retrieve and format information about the file where the dump has been triggered
        $backtrace = self::formatBacktrace(debug_backtrace()[0]);

        // Now let's start processing the dumped data (including optinal parameters)
        $this->dumped_name__raw = $dumped_name;
        $this->dumped_value__raw = $dumped_value;
        $dumped_category = (isset($dump_options['category'])) ? $dump_options['category'] : self::DEFAULT_CATEGORY;
        $dumped_description = (isset($dump_options['description'])) ? $dump_options['description'] : $dumped_name;
        $dumped_type = gettype($dumped_value);

        if ($dumped_type == 'object') {
            // Ignore log2console for objects! 
            // HINT: Maybe I can fix this later ... but it seems to be pretty hard to json_encode an object!
            $log2console = FALSE;
        } else {
            $log2console = (isset($dump_options['log2console']) && $dump_options['log2console'] === true) ? TRUE : FALSE;
        }

        // We will only collect dumps for active categories!
        if (! in_array($dumped_category, $this->categories_active)) {
            return;
        }

        // Analyse type of passed variable and format accordingly
        if ($dumped_type == 'array') {
            self::formatDumpedArray();
        } elseif ($dumped_type == 'object') {
            self::formatDumpedObject();
        } elseif ($dumped_type == 'boolean') {
            self::formatDumpedBoolean();
        } else {
            // for any other type, we'll handle formatting here ...
            $this->dumped_name__4html = '<strong>' . $this->dumped_name__raw . '</strong> <em>( ' . $dumped_type . ' )</em>';
            $this->dumped_name__4console = $this->dumped_name__raw . ' ( ' . $dumped_type . ' )';
            $this->dumped_value__4html = $dumped_value;
            $this->dumped_value__4console = '"' . $dumped_value . '"';
        }

        // collect dumped data into the appropriate array!(dumped_data__4{console} or {html})
        if ($log2console) {
            $this->f3->set('has_dumped_data__4console', TRUE);
            // increment counters and store in F3-hive
            $this->count_console_dumps++;
            $this->f3->set('count_console_dumps', $this->count_console_dumps);
            $this->categories_counter__console[$dumped_category]++;
            $this->f3->set('categories_counter__console', $this->categories_counter__console);

            $collected_dump = array(
                'varname'       => $this->dumped_name__4console,
                'value'         => $this->dumped_value__4console,
                'description'   => $dumped_description,
                'filename'      => $backtrace['dumped_in_file__4console'],
                'line'          => $backtrace['dumped_in_line'],
            );
            $this->f3->push('dumped_data__4console', $collected_dump);
        } else {
            $this->f3->set('has_dumped_data__4html', TRUE);
            // increment counters and store in F3-hive
            $this->count_html_dumps++;
            $this->f3->set('count_html_dumps', $this->count_html_dumps);
            $this->categories_counter__html[$dumped_category]++;
            $this->f3->set('categories_counter__html', $this->categories_counter__html);

            $collected_dump = array(
                'id'            => 'Dump_' . $this->count_html_dumps,
                'varname'       => $this->dumped_name__4html,
                'value'         => $this->dumped_value__4html,
                'description'   => $dumped_description,
                'filename'      => $backtrace['dumped_in_file__4html'],
                'line'          => $backtrace['dumped_in_line'],
            );
            $this->f3->push('dumped_data__4html', $collected_dump);
        };
        
    }

    /**
     * Dump directly to Browser and continue script-execution!
     *
     * @param string $dumped_name Name of the dumped variable/object
     * @param mixed $dumped_value Value of the dumped variable/object
     * @param string $title (Optional) title for the Dump to be displayed (otherwise defaults to self::DIRECT_DUMP_TITLE)
     * 
     * @return void
     */
	public function directDump(string $dumped_name, mixed $dumped_value, $title = self::DIRECT_DUMP_TITLE) {
        // We will only collect dumps while in DEVELOPMENT!
        if ($this->f3->DEBUG === 0) {
            return;
        }

        // Retrieve and format information about the file where the dump has been triggered
        $backtrace = self::formatBacktrace(debug_backtrace()[0]);

        // Use property:direct_dump_title if available AND $title-parameter has not been passed
        $title = ($this->direct_dump_title != '' && $title === self::DIRECT_DUMP_TITLE) ? $this->direct_dump_title : $title;

        // Now let's start processing the dumped data (including optional parameters)
        $type_raw = gettype($dumped_value);
        $value_formatted = $dumped_value;

        if ($type_raw == 'array' || $type_raw == 'object') {
            $print_r = print_r($dumped_value, TRUE);
            $value_formatted = '<pre>' . substr($print_r, strpos($print_r, "\n") + 1) . '</pre>';
            $type_formatted = ($type_raw == 'array') ? ' <em>( array )</em>' : 'object / Instance of: ' . get_class($dumped_value) . ' )</em>';
        } elseif ($type_raw == 'boolean') {
            $type_formatted = ' <em>( boolean )</em>';
        } else {
            $type_formatted = ' <em>( ' . $type_raw . ' )</em>';
        }

        // Let's dump directly ...
        echo '<div style="margin:auto; width: 75%; border-radius:15px; padding: 15px; background-color:#9c170e; color:white; font-size:85%;">';
        echo '<h6>' . $title . '</h6>';
        echo '<ul>';
        echo '<li>' . $backtrace['dumped_in_file__4html'] . ' @ Line: ' . $backtrace['dumped_in_line'] . '</li>';
        echo '<li><strong>' . $dumped_name . '</strong>' .  $type_formatted . '</li>';
        echo '<li><pre>' . $value_formatted . '</pre></li>';
        echo '</ul>';
        echo '</div>';
    }

    /**
     * Renders the dumps collected for the (Developer-)console
     * 
     * You should call this method right before you render your F3-template!
     *
     * @return void
     */
    public function renderDumps2Console(){
        if ($this->f3->get('has_dumped_data__4console')) {
            echo '<!-- F3Dumper is rendering dumped data to Developer-console ... -->' . PHP_EOL;
            echo '<script>' . PHP_EOL;
            $dump_or_dumps = ($this->count_console_dumps > 1) ? ' dumps' : ' dump';
            echo '  console.log("     ********  Dumper has collected ' . $this->count_console_dumps . $dump_or_dumps . '  ********");' . PHP_EOL;
            // compose string for category-statistics
            $category_statistics = '{ Dumps per Category: ';
            foreach($this->f3->get('categories_counter__console') as $current_caption => $current_count) {
                $category_statistics.= $current_caption . '(' . $current_count . ') ';
            }
            $category_statistics.= '}';
            echo '  console.log("' . $category_statistics . '");' . PHP_EOL;
            echo '  console.log("============================================================");' . PHP_EOL;
            $current_count      = 0;
            foreach ($this->f3->get('dumped_data__4console') as $dump_value) {
                $current_count++;
                // The 'description' will be different from the 'varname' if it has actually been sent with the dump.
                // So let's check for that and decide whether to append it!
                $append_description = ($dump_value['description'] !== $dump_value['varname']) ? $dump_value['description'] : '';
                // Compose the header for the current dump
                $current_var_header = $dump_value['filename'] . ' @ Line ' . $dump_value['line'] . ' : ' . $append_description . '\n' . $dump_value['varname'];
                echo '  console.log("' . $current_var_header . '");' . PHP_EOL;
                // Now output the value of the current dump
                echo '  console.log(' . $dump_value['value'] . ');' . PHP_EOL;

                // Output divider OR finishing line ?
                if ($current_count !== $this->count_console_dumps) {
                    echo '  console.log("------------------------------------------------------------");' . PHP_EOL;
                } else {
                    echo '  console.log("============================================================");' . PHP_EOL;
                }
            }
            echo '</script>' . PHP_EOL;
        }
    }

    /**
     * Formats a dumped array for prettier output by manipulating the according class-properties
     *
     * @return void
     */
    protected function formatDumpedArray() {
        $this->dumped_name__4html = '<strong>' . $this->dumped_name__raw . '</strong> <em>( array )</em>';
        $this->dumped_name__4console = $this->dumped_name__raw . ' ( array )';
        $print_r = print_r($this->dumped_value__raw, TRUE);
        $this->dumped_value__4html = '<pre>' . substr($print_r, strpos($print_r, "\n") + 1) . '</pre>';
        $this->dumped_value__4console = json_encode($this->dumped_value__raw, JSON_PRETTY_PRINT);
    }

    /**
     * Formats a dumped object for prettier output by manipulating the according class-properties
     *
     * @return void
     */
    protected function formatDumpedObject() {
        $this->dumped_name__4html = '<strong>' . $this->dumped_name__raw . '</strong> <em>( object / Instance of: <u>' . get_class($this->dumped_value__raw) . '</u> )</em>';
        $this->dumped_name__4console = $this->dumped_name__raw . ' ( object / Instance of: ' . get_class($this->dumped_value__raw) . ' )';
        $print_r = print_r($this->dumped_value__raw, TRUE);
        $this->dumped_value__4html = '<pre>' . substr($print_r, strpos($print_r, "\n") + 1) . '</pre>';
        $this->dumped_value__4console = json_encode($this->dumped_value__raw, JSON_PRETTY_PRINT);
    }

    /**
     * Formats a dumped boolean for prettier output by manipulating the according class-properties
     *
     * @return void
     */
    protected function formatDumpedBoolean() {
        $this->dumped_name__4html = '<strong>' . $this->dumped_name__raw . '</strong> <em>( boolean )</em>';
        $this->dumped_name__4console = $this->dumped_name__raw . ' ( boolean )';
        $html_true = '<span style="' . $this->css_span_true . '">&nbsp;TRUE&nbsp;</span>';
        $html_false = '<span style="' . $this->css_span_false . '">&nbsp;FALSE&nbsp;</span>';
        $this->dumped_value__4html = ($this->dumped_value__raw) ? $html_true : $html_false;
        $console_true = '"+++ TRUE +++"';
        $console_false = '"--- FALSE ---"';
        $this->dumped_value__4console = ($this->dumped_value__raw) ? $console_true : $console_false;
    }

    /**
     * Formats information from a debug_backtrace-element
     *
     * @param array $backtrace A **single** element from debug_backtrace 
     * 
     * @return array An associative array with raw and formatted information about the file and line where a dump was triggered
     */
    protected function formatBacktrace($backtrace) {
        return array(
            'dumped_in_line'            => $backtrace['line'],
            'dumped_in_file__raw'       => $backtrace['file'],
            'dumped_in_file__4html'      => str_replace($this->f3->get('dumper.strip_path'), '', $backtrace['file']),
            'dumped_in_file__4console'   => str_replace('\\', '\\\\', str_replace($this->f3->get('dumper.strip_path'), '', $backtrace['file'])),
        );
    }

}