<?php

/**
 * Minecraft Utils: Minecraft World Backupper

 * @link https://raw.githubusercontent.com/ZionDevelopers/Minecraft-PHP-Utils/master/Minecraft.Worlds.Backupper.php Source code on github
 * @copyright 2017, Júlio César de Oliveira
 * @author Júlio César de Oliveira <talk@juliocesar.me>
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ Creative Common Attribution-NonCommercial-ShareAlike 4.0 International
 */

// Define Directory Separator folder delimiter by O.S
define('DS', DIRECTORY_SEPARATOR);

// Define timezone
date_default_timezone_set('America/Sao_Paulo');

// Define Timer, change to false if you don't want a backup every X minutes
define('TIMER', true);

// Define new line constant determined by the O.S
define('NL', PHP_EOL);

define('DEBUG', true);
define('USER', get_current_user());
define('BACKUP_WORLDS_EVERY_X_MINUTES', 5); // 5 Minutes
define('BACKUP_FOREVER', true); // Do you want to keep backing up forever (while the script is running)

// Define dump function
function dump($string, $newLine = true) {
	// Check for debug
	if (DEBUG) {
		echo $string, $newLine ? NL : '';
	}
}

/*
 * Check if minecraft is running
 * @return boolean
*/
function isRunning() {
	$running = false;
	// Check if O.S is Windows
	if(stripos(PHP_OS, 'win') === 0) {
		$found = exec('tasklist /FI "IMAGENAME eq javaw.exe" /FI "WINDOWTITLE eq Minecraft*"');
		$running = stristr($found, 'javaw.exe') !== false;
	// Check if O.S is Linux
	} elseif (stripos(PHP_OS, 'linux') === 0) {
	// Check if O.S is mac
	} elseif (stripos(PHP_OS, 'darwin') === 0) {
	}
	
	return $running;
}

/** 
 * Scan completelly folder
 * @param string $dir
 * @param array $result
*/
function scan($dir, array &$result = []) {
	// Scandir folder
	$files = scandir($dir);
	// Loop by files
	foreach($files as $file) {
		// Check if file is not current or parent
		if ($file != '.' && $file != '..') {
			// Check if file is really a file
			if (is_file($dir . DS . $file)) {
				// Add file to result
				$result[$dir][] = $dir . DS . $file;
			} else {
				$result[$dir][] = $dir . DS . $file . DS;
				scan($dir . DS . $file . DS, $result);
			}
		}
	}
}

/**
 * Compress folder
 * @param string $archive
 * @param string $folder
 * @return null
*/
function compress($archive, $folder) {
	// Check if Minecraft is running
	$MCIsRunning = isRunning();
	
	// Check for 7zip and perm for exec
	if (file_exists('7z.exe') && function_exists('exec') && !$MCIsRunning) {
		// Run 7z
		exec('7z.exe a "' . $archive . '.7z" "' . $folder . DS . '"');
	// Check for winrar x64
	} elseif (file_exists('"%programfiles%\WinRAR\Rar.exe"') && function_exists('exec') && !$MCIsRunning) {
		exec('"%programfiles%\WinRAR\Rar.exe" a -r "' . $archive . '.rar" "'. $folder . DS . '"');
	// Check for winrar x86
	} elseif (file_exists('"%programfiles(x86)%\WinRAR\Rar.exe"') && function_exists('exec') && !$MCIsRunning) {
		exec('"%programfiles(x86)%\WinRAR\Rar.exe" a -r "' . $archive . '.rar" "'. $folder . DS . '"');
	// Check for PharData
	}  elseif (class_exists('\PharData')){
		// Create new Archive
		$pd = new \PharData($archive . '.tar.gz');
		
		// Add world folder
		$pd->buildFromDirectory($folder . DS);				
		
		// Check if archive is writeable
		if (is_writable($archive.'tar.gz')) {
			// Error handle
			try {
				// Compress files
				$pd->compress(\Phar::GZ, 'tar.gz');
			} catch(BadMethodCallException $e) {
			} catch(Exception $e) {
			}
		}
	// If everything fails go with regular zip
	} else if(class_exists('ZipArchive')) {
		// Scan folder
		$files = scan($folder);
		// Create Zip Archive
		$zip = new ZipArchive();

		// Check for
		if ($zip->open($archive . '.zip', ZipArchive::CREATE) === true) {
			// Loop by files
			foreach($files as $file) {
				// Check if file is really a file
				if (is_file($folder . DS . $file)) {
					// Add file
					$zip->addFile($file, dir_name($folder) . DS . $file);
				} elseif (is_dir($folder . DS . $file)) {
					// Add folder
					$zip->addEmptyDir($file . DS);
				}
			}
			
			$zip->close();
		}
	}
		
}

/**
 * Backup minecraft worlds
 * @return null
*/
function backupWorlds() {

	// Get windows roaming folder
	$roamingFolder = 'C:\Users\\' . USER . '\AppData\Roaming';
	// Define Minecraft Folder
	$minecraftFolder = $roamingFolder . DS . '.minecraft' . DS;
	// Define Worlds Folder
	$worldsFolder = $minecraftFolder . 'saves' . DS;

	// Check if folder exists
	if (is_dir($worldsFolder)){

		// Dump info
		dump('Scanning worlds...');

		// Scan worlds
		$worlds = scandir($worldsFolder);
		$worldsAmount = 0;
		
		// Loop by worlds
		foreach ($worlds as $world) {
			// Avoid .. . = Local and parent folders and avoid files
			if ($world != '..' && $world != '.' &&  is_dir($worldsFolder . $world)) {
				// Increment worlds amount
				$worldsAmount++;
			}
		}

		// Dump info
		dump('Found '. $worldsAmount . ' Worlds.');

		// Loop by worlds
		foreach($worlds as $world) {
			// Avoid .. . = Local and parent folders and avoid files
			if ($world != '..' && $world != '.' &&  is_dir($worldsFolder . $world)) {
				// Dump info
				dump('Backupping world "' . $world . '" ...');
				
				// Define archive
				$archive = $worldsFolder . $world . '-backup-' . date('y-m-d_H-i');
				// Compress World 
				compress($archive, $worldsFolder . $world);
				
				// Check if archive already exists
				if (!file_exists($archive)) {

				} else {
					// Dump info
					dump('Skipping world "' . $world . '" because, Archive already exists:' . $archive . '!');
				}
			}
		}

		// Dump info
		dump('Backups completed!');
	} else {
		dump('Error: Can\'t access Worlds folder:' . $worldsFolder);
	}
	// Dump new line
	dump('');
}

// Backup minecraft worlds
backupWorlds();

// Run timer
while (TIMER) {	
	// Check if minecraft is running
	if (BACKUP_FOREVER) {
		// Dump sleep info
		dump('Waiting '. BACKUP_WORLDS_EVERY_X_MINUTES . ' minutes for the next backup.');
		dump('');
		// Wait
		sleep(BACKUP_WORLDS_EVERY_X_MINUTES * 60);
		// Backup worlds
		backupWorlds();
	} else {
		// Exit
		exit('Bye!');
	}
	// Sleep 1 sec
	sleep(1);
}
