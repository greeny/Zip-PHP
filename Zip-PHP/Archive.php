<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\Zip;

use ZipArchive;

class Archive {
	/** @var ZipArchive */
	protected $zip;

	public static $errors = array(
		ZipArchive::ER_EXISTS => 'File already exists.',
		ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
		ZipArchive::ER_INVAL => 'Invalid argument.',
		ZipArchive::ER_MEMORY => 'Malloc failure.',
		ZipArchive::ER_NOENT => 'No such file.',
		ZipArchive::ER_NOZIP => 'Not a zip archive.',
		ZipArchive::ER_OPEN => 'Can\'t open file.',
		ZipArchive::ER_READ => 'Read error.',
		ZipArchive::ER_SEEK => 'Seek error.',
	);

	protected function __construct($filename, $mode)
	{
		$this->zip = new ZipArchive();
		if(file_exists($filename) && $mode === 'extract') {
			$code = $this->zip->open($filename);
		} else if(!(file_exists($filename)) && $mode === 'create') {
			$code = $this->zip->open($filename, ZipArchive::CREATE);
		} else {
			$code = $this->zip->open($filename, ZipArchive::OVERWRITE);
		}

		if($code !== TRUE) {
			$message = isset(self::$errors[$code]) ? ': '.self::$errors[$code] : '';
			throw new ZipException("Cannot create zip archive '$filename'$message");
		}
	}

	/**
	 * Creates new zip file located at $filename.
	 *
	 * @param string $filename
	 * @return Archive
	 */
	public static function create($filename) {
		$zip = new self($filename, 'create');
		return $zip;
	}

	/**
	 * Creates new zip file located at $filename and adds all files from $dir (recursively).
	 *
	 * @param string $dir
	 * @param string $filename
	 * @return Archive
	 */
	public static function fromDirectory($dir, $filename)
	{
		$zip = new self($filename, 'create');
		$zip->addFiles($dir);
		return $zip;
	}

	/**
	 * Extracts $zipFile to $directory
	 *
	 * @param string $zipFile
	 * @param string $directory
	 * @throws ZipException
	 */
	public static function extract($zipFile, $directory)
	{
		$zip = new ZipArchive();
		$code = $zip->open($zipFile);
		if($code !== TRUE) {
			$message = isset(self::$errors[$code]) ? self::$errors[$code] : $zip->getStatusString();
			throw new ZipException("Cannot open zip archive '$zipFile': $message");
		}
		if($zip->extractTo($directory) !== TRUE) {
			throw new ZipException("Cannot extract zip archive '$zipFile' to '$directory': ".$zip->getStatusString());
		}
	}

	/**
	 * Adds $file to zip archive
	 *
	 * @param string $file
	 * @param string $newName
	 * @return $this
	 * @throws ZipException
	 */
	public function addFile($file, $newName = NULL)
	{
		if(file_exists($file) && is_readable($file)) {
			if(!$this->zip->addFile($file, ltrim($newName, '/'))) {
				throw new ZipException("Cannot add file '$file': ".$this->zip->getStatusString());
			}
		} else {
			throw new ZipException("Cannot add file '$file' to zip archive, file does not exist.");
		}
		return $this;
	}

	/**
	 * Adds files from directory to zip file.
	 *
	 * @param string $directory
	 * @param string $mask
	 * @param bool   $recursive
	 * @param string $prefix
	 * @return $this
	 */
	public function addFiles($directory, $mask = "*", $recursive = TRUE, $prefix = '')
	{
		$cwd = getcwd();
		chdir($directory);
		foreach(glob($mask) as $file) {
			if($file !== '.' && $file !== '..') {
				if($recursive && is_dir($file)) {
					$this->addFiles("$directory/$file", $mask, $recursive, "$prefix/$file");
				} else if(is_file($file)) {
					$this->addFile("$directory/$file", "$prefix/$file");
				}
			}
		}
		chdir($cwd);
		return $this;
	}

	/**
	 * Saves file.
	 *
	 * @return $this
	 * @throws ZipException
	 */
	public function save()
	{
		if($this->zip->close() !== TRUE) {
			throw new ZipException("Cannot save zip archive: ".$this->zip->getStatusString());
		}
		return $this;
	}
}
 