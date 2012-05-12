<?php defined('SYSPATH') OR die('No direct script access');
/**
 * Upload helper extension to provide storage capabilities. 
 * Example usage:
 * 
 *     $data = array('file' => Upload::get('file')) + $this->request->post();
 *     
 *     try
 *     {
 *         // ... do whatever needs to be done and ...
 *     
 *         // ... delete the storage file, if any
 *         Upload::delete('file');
 *     }
 *     catch (ORM_Validation_Exception $e)
 *     {
 *         // store the file so that user doesn't have to upload it again
 *         Arr::get($post->errors(), 'file') OR Upload::store('file');
 *     }
 * 
 * and in the view, you can use Upload::stored('file') to know if the file 
 * has already been uploaded
 * 
 *     echo Upload::stored('file') ? "Already uploaded." : Form::file('file');
 * 
 * @author Kemal Delalic
 * @todo   reconsider protecting the methods to keep initialisation DRY (__callStatic()) 
 *         or moving everything to another (singleton?) class
 */
abstract class UploadStorage_Upload extends Kohana_Upload {

	const STORAGE_SESSION_KEY = 'upload_storage';

	/**
	 * @var boolean Has the storage been initialized?
	 */
	protected static $_storage_initialized = FALSE;

	/**
	 * @var array   Stored data
	 */
	protected static $_storage_data;
	
	/**
	 * @var array   _files already stored during the current 'session'
	 */
	protected static $_stored = array();
	
	/**
	 * Initializes the upload storage 
	 
	 * @return void
	 */
	protected static function _initialize_storage()
	{
		// Prevent double initialization
		if (Upload::$_storage_initialized)
			return;
			
		if (Upload::$_storage_data === NULL)
		{
			Upload::$_storage_data = Session::instance()->get(Upload::STORAGE_SESSION_KEY, array());
			
			// Bind the storage data array to the session key 
			// so we can forget about setting it manually
			Session::instance()->bind(Upload::STORAGE_SESSION_KEY, Upload::$_storage_data);
		}
		
		Upload::$_storage_initialized = TRUE;
	}

	/**
	 * Checks if a file with specified tmp_name has been stored
	 * 
	 * This was introduced as an alternative to is_uploaded_file() when validating
	 * because there can be files from the previous execution which weren't 
	 * uploaded via the current HTTP POST.
	 * 
	 * @see    http://php.net/is_uploaded_file
	 * @param  string  $tmp_name to check
	 * @return boolean
	 */
	protected static function _is_stored($tmp_name)
	{
		Upload::_initialize_storage();
		
		$stored_tmp_names = Arr::pluck(Upload::$_storage_data, 'tmp_name');
		
		return in_array($tmp_name, $stored_tmp_names, TRUE);
	}
	
	/**
	 * Deletes passed stored keys. Example:
	 * 
	 *     Upload::delete('address_proof','id_proof');
	 *
	 * @param string $key 
	 */
	public static function delete($key)
	{
		Upload::_initialize_storage();
		
		$keys = func_get_args();
		
		foreach ($keys as $key)
		{
			if (isset(Upload::$_storage_data[$key]))
			{
				File::delete($key['tmp_name']);
				
				unset(Upload::$_storage_data[$key], Upload::$_stored[$key]);	
			}
		}
	}

	/**
	 * Retrieves newly uploaded or stored contents (if any)
	 * If nothing found for the key specified, FALSE will be returned.
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 * @return array|boolean
	 */
	public static function get($key = NULL)
	{
		Upload::_initialize_storage();
		
		// If no key specified, return the whole "uploads" array
		if ($key === NULL)
			return array_merge(Upload::$_storage_data, $_FILES);
		
		// First check if the key requested has been reuploaded ...
		$value = Arr::get($_FILES, $key, FALSE);
		
		if ($value !== FALSE)
			return $value;
		
		// then just try returning it from storage
		if ($value = Arr::get(Upload::$_storage_data, $key, FALSE))
		{
			$_FILES[$key] = $value;
		}
		
		return $value;
	}
	
	/**
	 * Stores a key from $_FILES array into the session-based storage
	 * Temp. file will be stored into location other than tmp_name
	 * 
	 * @param  string $key
	 * @return array|boolean FALSE
	 */
	public static function store($key)
	{
		Upload::_initialize_storage();
		
		$data = Arr::get($_FILES, $key, FALSE);
		
		// If already stored during the current execution cycle, do nothing
		if (Upload::stored($key, TRUE))
			return $data;
		
		if ($data !== FALSE)
		{
			// Store the temporary contents in other location first
			$data['tmp_name'] = File::temp(file_get_contents($data['tmp_name']));
			 
			Upload::$_storage_data[$key] = Upload::$_stored[$key] = $data;
		}
		
		return $data;
	}
	
	/**
	 * Checks if a file has been stored
	 * If the current_session param is set to true,
	 * only the vars stored during the current execution will be checked
	 *
	 * @param  string  $key
	 * @param  boolean $current_session (defaults to FALSE)
	 * @return boolean
	 */
	public static function stored($key, $current_session = FALSE)
	{
		Upload::_initialize_storage();
		
		$storage = $current_session ? Upload::$_stored : Upload::$_storage_data;
		
		return array_key_exists($key, $storage);
	}

	/**
	 * Tests if a successful upload has been made.
	 *
	 *     $array->rule('file', 'Upload::not_empty');
	 *
	 * @param   array   $file   $_FILES item
	 * @return  bool
	 */
	public static function not_empty(array $file)
	{
		return (isset($file['error'])
			AND isset($file['tmp_name'])
			AND $file['error'] === UPLOAD_ERR_OK
			AND (is_uploaded_file($file['tmp_name']) OR Upload::_is_stored($file['tmp_name'])));
	}
	
}

