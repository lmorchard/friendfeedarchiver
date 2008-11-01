<?php defined('SYSPATH') or die('No direct access allowed.');

$lang = array
(
	'userfile_not_set'   => 'Unable to find a post variable called %s.',
	'file_exceeds_limit' => 'The uploaded file exceeds the maximum allowed size in your PHP configuration file',
	'file_partial'       => 'The file was only partially uploaded',
	'no_file_selected'   => 'You did not select a file to upload',
	'invalid_filetype'   => 'The file type you are attempting to upload is not allowed.',
	'invalid_filesize'   => 'The file you are attempting to upload is larger than the permitted size (%s)',
	'invalid_dimensions' => 'The image you are attempting to upload exceedes the maximum height or width (%s)',
	'destination_error'  => 'A problem was encountered while attempting to move the uploaded file to the final destination.',
	'no_filepath'        => 'The upload path does not appear to be valid.',
	'no_file_types'      => 'You have not specified any allowed file types.',
	'bad_filename'       => 'The file name you submitted already exists on the server.',
	'not_writable'       => 'The upload destination folder, %s, does not appear to be writable.',
	'error_on_file'      => 'Error uploading %s:',
	// Error code responses
	'set_allowed'        => 'For security, you must set the types of files that are allowed to be uploaded.',
	'max_file_size'      => 'For security, please do not use MAX_FILE_SIZE to control the maximum upload size.',
	'no_tmp_dir'         => 'Could not find a temporary directory to write to.',
	'tmp_unwritable'     => 'Could not write to the configured upload directory, %s.'
);