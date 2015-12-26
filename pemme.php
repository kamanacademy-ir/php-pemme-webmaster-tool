<?php


class utilities {
    
    public static $module_general_validation = array(
	"title" => "required|string",
	"desc" => "required|string",
	"group" => "required|string",
	"programmer" => "required|string",
	"created_at" => "required|datetime:Y-m-d",
	"type" => "required|numeric",
	"callback" => "required",
    );
    public static $command_validation = array(
	"path" => "required|starts_with:/",
	"on_end" => "required|select:up,mainmenu,subgroup",
    );
    public static $module_validation = array(
	"route" => "required|starts_with:/",
	"methods" => "required|array",
	"input_validation" => "required|array",
	"response_type" => "required|select:object,array,file",
	"access" => "required",
    );

    public static function starts_with($haystack, $needle) {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }
    public static function ends_with($haystack, $needle) {
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }
    
    public static function module_valid($module) {
	return true;
    }
    public static function module_path_valid($filepath) {
	if (utilities::ends_with($filepath, '.php') && 
		strpos($filepath, 'action') === false  &&  
		strpos($filepath, 'unlinked') === false) {
	    return true;
	} else {
	    return false;
	}
    }
    
    public static function glob_recursive($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $files = array_merge($files, utilities::glob_recursive($dir.'/'.basename($pattern), $flags));
        }
        return $files;
    }
    
    public static function response_object($code, $object, $data) {
	return array("code" => $code, "object" => $object, "data" => $data);
    }
}

class jsonui_console {

    private $foreground_colors = array();
    private $background_colors = array();
    private $term;

    const InputKeyUp = 1;
    const InputKeyDown = 2;
    const InputReturn = 3;
    const InputSpace = 4;

    public function __construct() {
	$this->foreground_colors['black'] = '0;30';
	$this->foreground_colors['dark_gray'] = '1;30';
	$this->foreground_colors['blue'] = '0;34';
	$this->foreground_colors['light_blue'] = '1;34';
	$this->foreground_colors['green'] = '0;32';
	$this->foreground_colors['light_green'] = '1;32';
	$this->foreground_colors['cyan'] = '0;36';
	$this->foreground_colors['light_cyan'] = '1;36';
	$this->foreground_colors['red'] = '0;31';
	$this->foreground_colors['light_red'] = '1;31';
	$this->foreground_colors['purple'] = '0;35';
	$this->foreground_colors['light_purple'] = '1;35';
	$this->foreground_colors['brown'] = '0;33';
	$this->foreground_colors['yellow'] = '1;33';
	$this->foreground_colors['light_gray'] = '0;37';
	$this->foreground_colors['white'] = '1;37';

	$this->background_colors['black'] = '40';
	$this->background_colors['red'] = '41';
	$this->background_colors['green'] = '42';
	$this->background_colors['yellow'] = '43';
	$this->background_colors['blue'] = '44';
	$this->background_colors['magenta'] = '45';
	$this->background_colors['cyan'] = '46';
	$this->background_colors['light_gray'] = '47';

	$this->term = `stty -g`;
	system("stty -icanon");
    }

    public function __destruct() {
	system("stty '" . $this->term . "'");
    }

    public function color($string, $foreground_color = null, $background_color = null) {
	return $this->getColoredString($string, $foreground_color, $background_color);
    }

    protected function getColoredString($string, $foreground_color = null, $background_color = null) {
	$colored_string = "";
	if (isset($this->foreground_colors[$foreground_color])) {
	    $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
	}
	if (isset($this->background_colors[$background_color])) {
	    $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
	}
	$colored_string .= $string . "\033[0m";
	return $colored_string;
    }

    public function getForegroundColors() {
	return array_keys($this->foreground_colors);
    }

    public function getBackgroundColors() {
	return array_keys($this->background_colors);
    }

    public function writeln($string, $foreground_color = null, $background_color = null) {
	echo $this->getColoredString($string, $foreground_color, $background_color) . PHP_EOL;
    }

    public function write($string, $foreground_color = null, $background_color = null) {
	echo $this->getColoredString($string, $foreground_color, $background_color);
    }

    public function confirm($string) {
	$value = $this->ask($string, "(yes) or no");
	return $value === "y" || $value === "(yes) or no";
    }
    
    public function ask($string, $default = NULL, $nullable = false) {
	$answer_buffer = $default;
	do {
	    echo "\t" . $this->getColoredString($string, "brown") . (!is_null($default) ? " " . $this->getColoredString("(" . $default . ") ") : "");
	    while ($c = fread(STDIN, 1)) {
		// echo ord($c);
		if ($c == chr(10)) {
		    break;
		} else if ($c == chr(8)) {
		    echo "\010\010\010   \010\010\010";
		} else {
		    $answer_buffer = $answer_buffer . $c;
		}
	    }

	    if (empty($answer_buffer)) {
		$answer_buffer = NULL;
		echo $this->getColoredString("\tEmpty string is not acceptable for this.", "red") . PHP_EOL;
	    }
	} while (!$nullable && empty($answer_buffer));
	return $answer_buffer;
    }

    protected function render_options($options, $selected_index) {
	$indexer = 0;
	foreach ($options as $option) {
	    echo (($selected_index == $indexer) ? "\t> " : "\t  ") . $this->getColoredString($option["title"], "brown") . PHP_EOL;
	    $indexer++;
	}
    }

    public function options($string, $options, $selected_index = 0) {
	echo $this->writeln($string, "green");
	$indexer = 0;
	$options_count = count($options);
	$this->render_options($options, $selected_index);

	while ($c = fread(STDIN, 1)) {
	    $indexer = 0;
	    $update = false;

	    if ($c == chr(66)) {
		if ($selected_index + 1 < $options_count) {
		    $selected_index ++;
		    $update = true;
		} else {
		    $selected_index = 0;
		    $update = true;
		}
	    } else if ($c == chr(65)) {
		if ($selected_index - 1 >= 0) {
		    $selected_index --;
		    $update = true;
		} else {
		    $selected_index = $options_count - 1;
		    $update = true;
		}
	    } else if ($c == chr(10)) {
		break;
	    }

	    echo chr(27) . "[0G" . "     ";

	    if ($update) {
		echo chr(27) . "[" . $options_count . "A";
		$this->render_options($options, $selected_index);
	    }
	}

	if ($selected_index >= 0 && $selected_index < $options_count) {
	    $option = $options[$selected_index];
	    if (isset($option["callback"])) {
		$command = $option["callback"];
		if (!is_null($command)) {
		    $command($option, $this);
		}
		return $option;
	    } else if (isset($option["value"])) {
		return $option["value"];
	    }
	}
    }

    protected function render_flags($selected_index, $options, $flags) {
	$indexer = 0;
	foreach ($options as $option) {
	    $value = $option['value'];
	    echo
	    (($selected_index == $indexer) ? "\t> " : "\t  ") .
	    ((($value & $flags) != 0) ? "+ " : "  ") .
	    $this->getColoredString($option["title"], "brown") . PHP_EOL;
	    $indexer++;
	}
    }

    public function flags($string, $options, $flags) {
	echo $this->writeln($string, "green");
	$selected_index = 0;
	$options_count = count($options);
	$this->render_flags($selected_index, $options, $flags);

	while ($c = fread(STDIN, 1)) {
	    $update = false;

	    if ($c == chr(66)) {
		if ($selected_index + 1 < $options_count) {
		    $selected_index ++;
		    $update = true;
		} else {
		    $selected_index = 0;
		    $update = true;
		}
	    } else if ($c == chr(65)) {
		if ($selected_index - 1 >= 0) {
		    $selected_index --;
		    $update = true;
		} else {
		    $selected_index = $options_count - 1;
		    $update = true;
		}
	    } else if ($c == chr(10)) {
		break;
	    } else if ($c == chr(32)) {
		$option = $options[$selected_index];
		$value = $option['value'];
		if (($value & $flags) != 0) {
		    $flags = $flags & ~$value;
		} else {
		    $flags = $flags | $value;
		}
		$update = true;
	    } else if ($c == chr(84)) {
		echo "\010";
	    }
	    echo chr(27) . "[0G" . "     ";

	    if ($update) {
		echo chr(27) . "[" . $options_count . "A";
		$this->render_flags($selected_index, $options, $flags);
	    }
	}

	return $flags;
    }

    protected function movecursor($line, $column) {
	echo "\033[{$line};{$column}H";
    }

}

class module_loader {

    protected $root = __DIR__;
    public $modules;
    public $invalid_modules;

    public function __construct($root_dir) {
	$this->root = $root_dir;
	if (!utilities::ends_with($root_dir, "/")) {
	    $this->root .= '/';
	}
    }

    public function load_modules() {
	$modules = array();
	$module_files = utilities::glob_recursive($this->root . "*.php", GLOB_NOSORT);
	foreach ($module_files as $mfile) {
	    if (utilities::module_path_valid($mfile)) {
		try {
		    $modules[] = include($mfile);	    
		} catch (Exception $ex) {
		    
		}
	    } else {
		
	    }
	}
	$valid_modules = array();
	$invalid_modules = array();
	foreach ($modules as $m) {
	    if (utilities::module_valid($m)) {
		$valid_modules[] = $m;
	    } else {
		$invalid_modules[] = $m;
	    }
	}
	$this->modules = $valid_modules;
	$this->invalid_modules = $invalid_modules;
	
	return $this->modules;
    }
    
    
}

$modules_dir_name = 'pemme_modules';
$console = new jsonui_console();

$console->writeln("pemme here B)", "green");
$console->writeln("pemme auto bootstrap near field php files :)", "blue");
$console->writeln("searching for ".__DIR__.'/pemme_modules :)', "blue");
$search_path = __DIR__.'/'.$modules_dir_name;
if (file_exists($search_path)) {
    $loader = new module_loader(__DIR__.'/'.$modules_dir_name);
    $loader->load_modules();
    $modules_count = count($loader->modules);
    $console->writeln("found ".$modules_count." modules.", "blue");
    if ($modules_count > 0) {
        $console->options("how can i help u :? ", $loader->modules);	
    } else {
	$console->writeln("i have no modules to do anything :/ thats embaressing", "brown");
    }

    $console->writeln("bye bye now :|", "green");
} else {
    $console->writeln("there was no pemme modules folder! :/", "brown");
    $console->writeln("bye bye now :|", "green");
}

