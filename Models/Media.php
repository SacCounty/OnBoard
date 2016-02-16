<?php
/**
 * Files will be stored as /data/media/YYYY/MM/DD/$media_id.ext
 * User provided filenames will be stored in the database
 *
 * @copyright 2014-2016 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 */
namespace Application\Models;

use Blossom\Classes\ActiveRecord;
use Blossom\Classes\Database;

class Media extends ActiveRecord
{
	const REGEX_FILENAME_EXT = '/(^.*)\.([^\.]+)$/';

	protected $tablename = 'media';

	protected $applicant;

	/**
	 * Whitelist of accepted file types
	 */
	public static $extensions = [
		'jpg' =>['mime_type'=>'image/jpeg'],
		'gif' =>['mime_type'=>'image/gif' ],
		'png' =>['mime_type'=>'image/png' ],
		'tiff'=>['mime_type'=>'image/tiff']
		#'pdf' =>array('mime_type'=>'application/pdf','media_type'=>'attachment'),
		#'rtf' =>array('mime_type'=>'application/rtf','media_type'=>'attachment'),
		#'doc' =>array('mime_type'=>'application/msword','media_type'=>'attachment'),
		#'xls' =>array('mime_type'=>'application/msexcel','media_type'=>'attachment'),
		#'gz'  =>array('mime_type'=>'application/x-gzip','media_type'=>'attachment'),
		#'zip' =>array('mime_type'=>'application/zip','media_type'=>'attachment'),
		#'txt' =>array('mime_type'=>'text/plain','media_type'=>'attachment'),
		#'wmv' =>array('mime_type'=>'video/x-ms-wmv','media_type'=>'video'),
		#'mov' =>array('mime_type'=>'video/quicktime','media_type'=>'video'),
		#'rm'  =>array('mime_type'=>'application/vnd.rn-realmedia','media_type'=>'video'),
		#'ram' =>array('mime_type'=>'audio/vnd.rn-realaudio','media_type'=>'audio'),
		#'mp3' =>array('mime_type'=>'audio/mpeg','media_type'=>'audio'),
		#'mp4' =>array('mime_type'=>'video/mp4','media_type'=>'video'),
		#'flv' =>array('mime_type'=>'video/x-flv','media_type'=>'video'),
		#'wma' =>array('mime_type'=>'audio/x-ms-wma','media_type'=>'audio'),
		#'kml' =>array('mime_type'=>'application/vnd.google-earth.kml+xml','media_type'=>'attachment'),
		#'swf' =>array('mime_type'=>'application/x-shockwave-flash','media_type'=>'attachment'),
		#'eps' =>array('mime_type'=>'application/postscript','media_type'=>'attachment')
	];

	/**
	 * Populates the object with data
	 *
	 * Passing in an associative array of data will populate this object without
	 * hitting the database.
	 *
	 * Passing in a scalar will load the data from the database.
	 * This will load all fields in the table as properties of this class.
	 * You may want to replace this with, or add your own extra, custom loading
	 *
	 * @param int|array $id
	 */
	public function __construct($id=null)
	{
		if ($id) {
			if (is_array($id)) {
				$this->exchangeArray($id);
			}
			else {
				$zend_db = Database::getConnection();
				if (ActiveRecord::isId($id)) {
					$sql = 'select * from media where id=?';
				}
				// Internal filename without extension
				elseif (ctype_xdigit($id)) {
					$sql = 'select * from media where internalFilename like ?';
					$id.= '%';
				}
				$result = $zend_db->createStatement($sql)->execute([$id]);
				if (count($result)) {
					$this->exchangeArray($result->current());
				}
				else {
					throw new \Exception('media/unknownMedia');
				}
			}
		}
		else {
			// This is where the code goes to generate a new, empty instance.
			// Set any default values for properties that need it here
			$this->setUploaded('now');
		}
	}

	/**
	 * Throws an exception if anything's wrong
	 * @throws Exception $e
	 */
	public function validate()
	{
		// Check for required fields here.  Throw an exception if anything is missing.
		if (!$this->data['filename'])   { throw new \Exception('media/missingFilename');  }
		if (!$this->data['mime_type'])  { throw new \Exception('media/missingMimeType');  }
		if (!$this->getApplicant_id())  { throw new \Exception('media/missingApplicant'); }
	}

	public function save() { parent::save(); }

	/**
	 * Deletes the file from the hard drive
	 */
	public function delete()
	{
		if ($this->getId()) {
			$zend_db = Database::getConnection();

			unlink(SITE_HOME."/media/{$this->getDirectory()}/{$this->getInternalFilename()}");
			parent::delete();
		}
	}

	//----------------------------------------------------------------
	// Generic Getters & Setters
	//----------------------------------------------------------------
	public function getId()           { return parent::get('id');          }
	public function getFilename()     { return parent::get('filename');    }
	public function getMime_type()    { return parent::get('mime_type');   }
	public function getApplicant_id() { return parent::get('applicant_id');   }
	public function getApplicant()    { return parent::getForeignKeyObject(__namespace__.'\Applicant', 'applicant_id'); }
	public function getUploaded($f=null, DateTimeZone $tz=null) { return parent::getDateData('uploaded', $f, $tz); }

	public function setApplicant_id($i) { parent::setForeignKeyField (__namespace__.'\Applicant', 'applicant_id', $i); }
	public function setApplicant   ($o) { parent::setForeignKeyObject(__namespace__.'\Applicant', 'applicant_id', $o);  }
	public function setUploaded    ($d) { parent::setDateData('uploaded', $d); }

	/**
	 * @param array $post
	 */
	public function handleUpdate($post)
	{
        $this->setApplicant_id($post['applicant_id']);
	}

	//----------------------------------------------------------------
	// Custom Functions
	//----------------------------------------------------------------
	/**
	 * Populates this object by reading information on a file
	 *
	 * This function does the bulk of the work for setting all the required information.
	 * It tries to read as much meta-data about the file as possible
	 *
	 * @param array|string Either a $_FILES array or a path to a file
	 */
	public function setFile($file)
	{
		// Handle passing in either a $_FILES array or just a path to a file
		$tempFile = is_array($file) ? $file['tmp_name'] : $file;
		$filename = is_array($file) ? basename($file['name']) : basename($file);
		if (!$tempFile) {
			throw new \Exception('media/uploadFailed');
		}

		// Clean all bad characters from the filename
		$filename = $this->createValidFilename($filename);
		$this->data['filename'] = $filename;
		$extension = self::getExtension($filename);

		// Find out the mime type for this file
		if (array_key_exists(strtolower($extension), Media::$extensions)) {
			$this->data['mime_type']  = Media::$extensions[$extension]['mime_type'];
		}
		else {
			throw new \Exception('media/unknownFileType');
		}

		// Move the file where it's supposed to go
		$newFile   = $this->getFullPath();
		$directory = dirname($newFile);
		if (!is_dir($directory)) {
			mkdir  ($directory, 0777, true);
		}
		rename($tempFile, $newFile);
		chmod($newFile, 0666);

		// Check and make sure the file was saved
		if (!is_file($newFile)) {
			throw new \Exception('media/badServerPermissions');
		}
	}

	/**
	 * Returns the partial path of the file, relative to /data/media
	 *
	 * Media is stored in the data directory, outside of the web directory
	 * This variable only contains the partial path.
	 * This partial path can be concat with APPLICATION_HOME or BASE_URL
	 *
	 * @return string
	 */
	public function getDirectory()
	{
		return $this->getUploaded('Y/n/j');
	}

	/**
	 * Returns the file name used on the server
	 *
	 * We do not use the filename the user chose when saving the files.
	 * We generate a unique filename the first time the filename is needed.
	 * This filename will be saved in the database whenever this media is
	 * finally saved.
	 *
	 * @return string
	 */
	public function getInternalFilename()
	{
		$filename = parent::get('internalFilename');
		if (!$filename) {
			$filename = uniqid();
			parent::set('internalFilename', $filename);
		}
		return $filename;
	}

	/**
	 * Returns the full path to the file or derivative
	 *
	 * @return string
	 */
	public function getFullPath()
	{
        return SITE_HOME."/media/{$this->getDirectory()}/{$this->getInternalFilename()}";
	}

	/**
	 * @return string
	 */
	public static function getExtension($filename)
	{
		if (preg_match("/[^.]+$/", $filename, $matches)) {
			return strtolower($matches[0]);
		}
		else {
			echo "$filename has no extension\n";
			throw new \Exception('media/missingExtension');
		}
	}

	/**
	 * @return int
	 */
	public function getFilesize()
	{
		return filesize(SITE_HOME."/media/{$this->getDirectory()}/{$this->getInternalFilename()}");
	}

	/**
	 * Cleans a filename of any characters that might cause problems on filesystems
	 *
	 * @return string
	 */
	public static function createValidFilename($string)
	{
		// No bad characters
		$string = preg_replace('/[^A-Za-z0-9_\.\s]/','',$string);

		// Convert spaces to underscores
		$string = preg_replace('/\s+/','_',$string);

		// Lower case any file extension
		if (preg_match(self::REGEX_FILENAME_EXT,$string,$matches)) {
			$string = $matches[1].'.'.strtolower($matches[2]);
		}

		return $string;
	}

	/**
	 * @return boolean
	 */
	public static function isValidFiletype($filename)
	{
		$ext = self::getExtension($filename);
		return array_key_exists(strtolower($ext),self::$extensions);
	}
}