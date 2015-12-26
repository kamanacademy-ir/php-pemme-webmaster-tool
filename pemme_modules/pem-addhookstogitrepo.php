<?php
return array(
    "title" => "add hooks to an existing git repo",
    "callback" => function($self, $console) {
	$git_folder = null;
	do {
	    $path = $console->ask("please enter repo path: ");
	    if (file_exists($path) && is_dir($path)) {
		if (!utilities::ends_with($path, '/')) { $path .= '/'; }
		$git_folder = $path.".git";
		if (!file_exists($git_folder) || !is_dir($git_folder)) {
		    $console->writeln("this path is not a git repo :/", "red");
		    $path = null;
		    $git_folder = null;
		}
	    } else {
		$console->writeln("this path does not exist :/", "red");
		$path = null;
		$git_folder = null;
	    }
	} while (is_null($git_folder));
	
	$post_receive_file_path = $git_folder.'/hooks/post-receive';
	$post_receive_file = null;
	if (!file_exists($post_receive_file_path)) {
	    $console->writeln("post receive file does not exist, i will create one!");
	}
	
	
    },
);