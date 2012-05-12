<?php defined('SYSPATH') or die('No direct script access.');

abstract class UploadStorage_File extends Kohana_File {
	
	/**
	 * Error code to signal that the file is missing
	 * This is always a positive value because we can
	 * consider files which didn't exist already as 'deleted'.
	 */
	const MISSING = NULL;

	/**
	 * Deletes file(s) while gracefully handling "file doesn't exist" errors
	 *
	 * @param   mixed   $file path to file or array of paths
	 * @return  mixed   status or array of statuses
	 * @see     http://php.net/unlink
	 */
	public static function delete($file)
	{
		// Remove arrays of files recursively
		if (is_array($file))
		{
			$result = array();
			
			foreach ($file as $key => $path)
			{
				$result[$path] = File::delete($path);
			}
			
			return $result;
		}
		
		try
		{
			if ( ! file_exists($file))
				return file::MISSING;
				
			return unlink($file);
		}
		catch (Exception $e)
		{
			return FALSE;
		}
	}
	
	/**
	 * Creates a temporary file with passed data as content
	 * 
	 * @param  string $data (binary contents to save)
	 * @param  int    $chmod (bitmask to CHMOD the temp file to, optional)
	 * @param  string $tempdir (directory to save contents to, sys tmp dir by default)
	 * @return string Path to the newly created temp file
	 */
	public static function temp($data, $chmod = NULL, $tempdir = NULL)
	{
		// If temp. directory isn't specified, use system default
		$tempdir = ($tempdir === NULL) ? sys_get_temp_dir() : $tempdir;
		
		// Create a temporary path (including filename)
		$path = tempnam($tempdir, 'ftmp_');
		
		// Put the temp. contents
		file_put_contents($path, $data);
		
		// If CHMOD is specified, apply it
		if ($chmod !== NULL)
		{
			chmod($path, $chmod);
		}
		
		return $path;
	}
	
}

