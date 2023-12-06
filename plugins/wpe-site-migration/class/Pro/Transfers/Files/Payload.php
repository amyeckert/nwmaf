<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files;

use DeliciousBrains\WPMDB\Common\Exceptions\FileOperationException;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Chunker;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Pro\Transfers\Sender;
use Exception;
use WP_Error;

/**
 * Class Payload
 *
 * @package WPMDB\Transfers\Files
 */
class Payload
{

    /**
     * @var Util
     */
    public $util;
    /**
     * @var Chunker
     */
    public $chunker;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var Http
     */
    private $http;

    const PART_SUFFIX = '.part';
    /**
     * @var \DeliciousBrains\WPMDB\Common\Util\Util
     */
    private $common_util;

    public function __construct(
        Util $util,
        Chunker $chunker,
        Filesystem $filesystem,
        Http $http,
        \DeliciousBrains\WPMDB\Common\Util\Util $common_util
    ) {
        $this->util        = $util;
        $this->chunker     = $chunker;
        $this->filesystem  = $filesystem;
        $this->http        = $http;
        $this->common_util = $common_util;
    }

    /**
     *
     * Create a payload string based on an array of file data
     *
     * Write string to $stream resource
     *
     * @param array    $file
     * @param array    $meta_data
     * @param resource $stream
     * @param string   $file_path
     *
     * @return null
     * @throws Exception
     */
    public function assemble_payload($file, $meta_data, &$stream, $file_path)
    {
        if (!file_exists($file['absolute_path'])) {
            throw new Exception(sprintf(__('File does not exist - %s', 'wp-migrate-db'), $file['absolute_path']));
        }

        $file_name          = $file['name'];
        $file['type']       = 'file';
        $file['md5']        = md5_file($file['absolute_path']);
        $file['chunk_size'] = isset($file['chunk_path']) ? filesize($file['chunk_path']) : null;
        $file['encoded']    = true;

        if (!isset($file['size'])) {
            $file['size'] = filesize($file['absolute_path']);
        }

        $meta_data['file'] = $file + $meta_data['file'];

        $content = Sender::$start_meta . $file_name . "\n";
        $content .= serialize($meta_data) . "\n";
        $content .= Sender::$end_meta . $file_name . "\n";
        $content .= Sender::$start_payload . $file_name . "\n";

        // Write first chunk of content to the payload
        if (false === fwrite($stream, $content)) {
            throw new FileOperationException(__('Unable to write to payload file.', 'wp-migrate-db'));
        }

        $file_stream = fopen($file_path, 'rb');

        if (false === $file_stream) {
            throw new FileOperationException(sprintf(__('Unable to open file %s', 'wp-migrate-db'), $file_path));
        }

        // Skirts memory limits by copying stream to stream - writes directly to stream
        stream_copy_to_stream($file_stream, $stream);

        $content = "\n" . Sender::$end_payload . $file_name;
        $content .= "\n" . Sender::$end_sig . "\n";

        if (false === fwrite($stream, $content)) {
            throw new FileOperationException(__('Unable to write to payload file.', 'wp-migrate-db'));
        }

        fclose($file_stream);

        return null;
    }

    /**
     * @param array  $file_list
     * @param array  $state_data
     * @param int    $bottleneck
     *
     * @return resource|array|WP_Error
     */
    public function create_payload($file_list, $state_data, $bottleneck)
    {
        /*
         * Other options to use if large files aren't downloading are:
         * 	$membuffer = 54 * 1024 * 1024; // 54MB
         * 	$handle = apply_filters( 'wpmdb_transfers_payload_handle', fopen( 'php://temp/maxmemory:'. $membuffer ) );
         *
         * OR
         *
         * $handle = apply_filters( 'wpmdb_transfers_payload_handle', fopen( WP_CONTENT_DIR . '/.payload', 'wb+' ) );
         */
        $handle = Util::tmpfile();

        if(empty($handle)) {
            throw new FileOperationException(__('Unable to create payload file.', 'wp-migrate-db'));
        }

        $count                = 0;
        $sent                 = [];
        $chunked              = [];
        $chunking             = false;
        $chunks               = 0;
        $size_modified_count  = 0;
        $files_modified       = [];

        foreach ($file_list as $key => $file) {
            // Info on fopen() stream
            $fstat = fstat($handle);
            //get current size to see if it has changed since init
            $current_size = $this->filesystem->filesize($file['absolute_path']);
            if ($current_size !== $file['size'] ) {
                $file['size']     = $current_size;
                $files_modified[] = $file['absolute_path'];
                $size_modified_count++;
            }
            $added_size = $fstat['size'] + $file['size'];

            // If the filesize is less than the bottleneck and adding the file to the payload would push it over the $bottleneck
            // OR the payload already has stuff in it and the next file is a file larger than the bottleneck
            if (($file['size'] < $bottleneck && $added_size >= $bottleneck)
                || (0 !== $fstat['size'] && $file['size'] >= $bottleneck)) {
                break;
            }

            $data = [
                'file'  => $file,
                'stage' => $state_data['stage'],
            ];

            $file_path = $file['absolute_path'];
            $file_size = filesize($file_path);

            //Push and file is too large
            if ($file_size >= $bottleneck && 'push' === $state_data['intent']) {
                $chunks   = ceil($file_size / $bottleneck);
                $chunking = true;
            }

            list($chunked, $file, $file_path, $chunk_data) = $this->maybe_get_chunk_data($state_data, $bottleneck,
                $chunking, $file_path, $file, $chunks);

            $this->assemble_payload($file, $data, $handle, $file_path);

            $sent[] = $file;
            $count++;
        }

        //track number of modified file sizes
        if ($size_modified_count > 0) {
            $this->track_migration_stats($size_modified_count, $files_modified);
        }

        if ('pull' === $state_data['intent']) {
            $handle = $this->assemble_payload_metadata($count, $sent, $handle);
        }

        if (false === fwrite($handle, "\n" . Sender::$end_bucket)) {
            throw new FileOperationException(__('Unable to write to payload file.', 'wp-migrate-db'));
        }

        if ('push' === $state_data['intent']) {
            return array($count, $sent, $handle, $chunked, $file, $chunk_data);
        }

        return $handle;
    }

    /**
     * Add stats to tracking
     *
     * @param int $size_modified_count
     * @param array $files_modified
     * @return bool
     **/
    private function track_migration_stats($size_modified_count, $files_modified) {
        $migration_stats                     = Persistence::getMigrationStats();
        $existing_count                      = is_array($migration_stats) && array_key_exists('files_size_modified', $migration_stats) ? $migration_stats['file_size_modified'] : 0;
        $migration_stats['files_size_modified'] = $existing_count + $size_modified_count;
        $existing_files                      = is_array($migration_stats) && array_key_exists('files_modified', $migration_stats) ? $migration_stats['files_modified'] : [];
        $migration_stats['files_modified']    = array_slice(array_merge($existing_files, $files_modified),0, 10);
        return Persistence::storeMigrationStats($migration_stats);
    }

    /**
     * @param array  $state_data
     * @param int    $bottleneck
     * @param bool   $chunking
     * @param string $file_path
     * @param array  $file
     * @param int    $chunks
     *
     * @return array
     */
    public function maybe_get_chunk_data($state_data, $bottleneck, $chunking, $file_path, $file, $chunks)
    {
        if (!$chunking) {
            return array(false, $file, $file_path, []);
        }
        // Checks if current migration is a 'push' and if the file is too large to transfer
        list($chunked, $chunk_data) = $this->chunker->chunk_it($state_data, $bottleneck, $file_path, $file, $chunks);

        if ($chunked && false !== $chunked['chunked']) {
            $file      = $chunked['file'];
            $file_path = $chunked['file_path'];
        }

        return array($chunked, $file, $file_path, $chunk_data);
    }

    /**
     *
     * Read payload line by line and parse out contents
     *
     * @param array    $state_data
     * @param resource $stream
     * @param bool     $return Not used
     *
     * @return bool|array
     * @throws Exception
     */
    public function process_payload($state_data, $stream, $return = false)
    {
        $is_meta        = false;
        $is_payload     = false;
        $end_payload    = false;
        $is_skipped     = false;
        $is_bucket_meta = false;
        $bucket_meta    = false;
        $handle         = null;
        $meta           = [];

        while (($line = fgets($stream)) !== false) {
            if (false !== strpos($line, Sender::$start_meta)) {
                $is_meta = true;
                continue;
            }

            if ($is_meta) {
                $meta    = unserialize($line);
                $is_meta = false;
                continue;
            }

            if (false !== strpos($line, Sender::$start_payload)) {
                $is_payload = true;

                //For pulls, we're not chunking so use the full filesize, for push check if a chunk size exists, otherwise use the full filesize.
                $filesize = ! empty($meta['file']['chunk_size']) ? $meta['file']['chunk_size'] : $meta['file']['size'];

                $destination_filepath = $this->assemble_filepath_from_payload($state_data, $meta);

                // If handling part of chunked file, use final destination file name for skip test.
                if ( ! empty($meta['file']['chunked']) && $this->has_part_suffix($destination_filepath)) {
                    $destination_filepath = $this->trim_part_suffix($destination_filepath);
                }

                //If the final destination file already exists, skip it.
                if (
                    is_numeric($filesize) &&
                    is_file($destination_filepath) &&
                    $this->verify_file_from_payload($destination_filepath, $meta, false)
                ) {
                    $is_skipped = true;
                    fseek($stream, $filesize, SEEK_CUR);
                    continue;
                }

                list($dest, $handle) = $this->get_handle_from_metadata($state_data, $meta);

                // maybe we can stream the file without buffering
                if (is_numeric($filesize)) {
                    // set up stream copy here
                    $streamed_bytes = stream_copy_to_stream($stream, $handle, $filesize);
                    if (false === $streamed_bytes) {
                        error_log('Could not copy stream data to file. ' . print_r($dest, true));
                        throw new FileOperationException(
                            sprintf(__('Could not copy stream data to file. File name: %s', 'wp-migrate-db'), $dest)
                        );
                    }
                    // yay! we did it. Next loop gets the end of payload
                    continue;
                }

                //We couldn't determine the filesize so let's bail
                error_log('Could not determine payload filesize: ' . print_r($dest, true));
                throw new FileOperationException(
                    sprintf(__('Could not determine payload filesize. File name: %s', 'wp-migrate-db'), $dest)
                );
            }

            // If we're skipping a file, jump to the end of the payload
            if ($is_payload && $is_skipped) {
                $is_payload  = false;
                $end_payload = true;
                continue;
            }

            if ($is_payload) {
                /**
                 * Since we're in a large while loop we need to check if a file's payload
                 * has been read entirely. Files are added to the payload line by line so they
                 * need to read line by line. Sender::$end_payload is the deliminator to say that
                 * a file's contents ends _within_ the payload.
                 */
                if (false !== strpos($line, Sender::$end_payload)) {
                    // Trim trailing newline from end of the created file, thanks fgets()...
                    $stat = fstat($handle);
                    ftruncate($handle, $stat['size'] - 1);

                    fclose($handle);

                    $is_payload  = false;
                    $end_payload = true;
                    continue;
                }

                $this->create_file_by_line($line, $handle, $meta['file']);
            }

            if ($end_payload) {
                if ( ! $is_skipped) {
                    if (isset($meta['file']['chunked']) && false !== $meta['file']['chunked']) {
                        if (isset($meta['file']['bytes_offset'], $meta['file']['size']) && ((int)$meta['file']['bytes_offset'] === (int)$meta['file']['size'])) {
                            //Chunking complete
                            try {
                                $renamed = $this->rename_part_file($dest, $meta);
                            } catch (Exception $e) {
                                throw new FileOperationException(
                                    sprintf(
                                        __('Unable to rename part file %1$s<br>%2$s', 'wp-migrate-db'),
                                        $dest,
                                        $e->getMessage()
                                    )
                                );
                            }

                            if (is_wp_error($renamed)) {
                                throw new FileOperationException($renamed->get_error_message());
                            }

                            if ( ! $renamed) {
                                throw new FileOperationException(
                                    sprintf(__('Unable to rename part file %s', 'wp-migrate-db'), $dest)
                                );
                            }
                        }
                    } else {
                        try {
                            $this->verify_file_from_payload($dest, $meta);
                        } catch (Exception $e) {
                            throw new FileOperationException(
                                sprintf(
                                    __(
                                        'Could not verify file is from payload. File name: %1$s<br>%2$s',
                                        'wp-migrate-db'
                                    ),
                                    $dest,
                                    $e->getMessage()
                                )
                            );
                        }
                    }
                }

                $is_skipped  = false;
                $end_payload = false;
                continue;
            }

            /**
             * Bucket meta is information about what's in the payload.
             *
             * Presently this includes a count of how many files it contains and
             * file information from Filesystem::get_file_info() about each file within
             *
             */
            if (false !== strpos($line, Sender::$start_bucket_meta)) {
                $is_bucket_meta = true;
                continue;
            }

            if ($is_bucket_meta) {
                $bucket_meta    = unserialize($line);
                $is_bucket_meta = false;
                continue;
            }

            if (false !== strpos($line, Sender::$end_bucket)) {
                return $bucket_meta;
            }
        }

        return false;
    }

    /**
     * Verify that a file is from the payload.
     *
     * @param string $dest
     * @param array  $meta
     * @param bool   $throw
     *
     * @return bool
     * @throws Exception
     */
    public function verify_file_from_payload($dest, $meta, $throw = true)
    {
        // Verify size of file matches what it's supposed to be
        if ( ! $this->util->verify_file($dest, (int)$meta['file']['size'])) {
            $msg = sprintf(
                __(
                    'File size of source and destination do not match: <br>%1$s<br>Destination size: %2$s, Local size: %3$s',
                    'wp-migrate-db'
                ),
                $dest,
                filesize($dest),
                $meta['file']['size']
            );
            if ($throw) {
                throw new Exception($msg);
            }

            return false;
        }

        $md5 = md5_file($dest);

        if ($meta['file']['md5'] !== $md5) {
            $msg = sprintf(
                __('File MD5\'s do not match for file: %1$s<br>Local MD5: %2$s Remote MD5: %3$s', 'wp-migrate-db'),
                dirname($dest),
                $md5,
                $meta['file']['md5']
            );

            if ($throw) {
                throw new Exception($msg);
            }

            return false;
        }

        return true;
    }

    /**
     *
     * Give a line of data from fgets() write to a previously created resource(stream).
     *
     * @param string   $line
     * @param resource $handle
     * @param array    $file_data
     *
     * @return bool
     * @throws Exception
     */
    public function create_file_by_line($line, $handle, $file_data)
    {
        if ( ! $handle || false === fwrite($handle, $line)) {
            error_log('Could not write line to file. ' . print_r($file_data, true));
            throw new Exception(sprintf(__('Could not write line to file. File name: %s', 'wp-migrate-db'),
                $file_data['name']));
        }

        return false;
    }

    /**
     * @param array $state_data
     * @param $meta
     *
     * @return string
     */
    public function assemble_filepath_from_payload($state_data, $meta)
    {
        $stage = $state_data['stage'];

        $file = $this->filesystem->slash_one_direction($meta['file']['relative_path']);

        if (isset($meta['file']['chunked']) && $meta['file']['chunked'] === true) {
            $file .= self::PART_SUFFIX;
        }

        $dest = Util::get_temp_dir($stage) . $file;
        if ($stage === 'media_files') {
            // Filtered by MST
            $uploads = apply_filters('wpmdb_mf_destination_uploads', Util::get_wp_uploads_dir(), $state_data);
            $file    = apply_filters('wpmdb_mf_destination_file', $file, $state_data);
            $dest    = $uploads . $file;
        }

        return $dest;
    }


    /**
     * @param $stage
     * @param $meta
     *
     * @return array
     * @throws Exception
     */
    public function get_handle_from_metadata($state_data, $meta)
    {
        $dest = $this->assemble_filepath_from_payload($state_data, $meta);

        $dirname = \dirname($dest);

        if (!is_dir($dirname)) {
            if (!$this->filesystem->mkdir($dirname)) {
                $msg = sprintf(__('Could not create directory: %s', 'wp-migrate-db'), dirname($dest));
                throw new Exception($msg);
            }
        }

        $mode = isset($meta['file']['chunked']) ? 'a+b' : 'w+b';

        // Files need to be deleted before hand when running media stage because they are copied in place
        // 'w' fopen() mode truncates the file before opening to write
        if (
            isset($meta['file']['chunked']) && $meta['file']['chunk_number'] === 1 ||
            !isset($meta['file']['chunked'])
        ) {
            $mode = 'w+b';
        }

        if (file_exists($dest) && !is_writable($dest)) {
            $msg = sprintf(__('The `%s` file is not writable, please check the file permissions of the parent folder and ensure the web server can read from and write to this file.', 'wp-migrate-db'), $dest);
            throw new Exception($msg);
        }

        $handle = fopen($dest, $mode);

        if (false === $handle) {
            throw new FileOperationException(sprintf(__('Unable to open file: %s', 'wp-migrate-db'), $dest));
        }

        return array($dest, $handle);
    }

    /**
     * @param $count
     * @param $sent
     * @param $handle
     *
     * @return resource
     * @throws FileOperationException
     */
    public function assemble_payload_metadata($count, $sent, $handle)
    {
        // Information about what's in the payload, number of files and an array of file data about the files included
        $bucket_meta = serialize(compact('count', 'sent'));

        $bucket_meta_content = Sender::$start_bucket_meta . "\n";
        $bucket_meta_content .= $bucket_meta . "\n";
        $bucket_meta_content .= Sender::$end_bucket_meta;

        if (false === fwrite($handle, $bucket_meta_content)) {
            throw new FileOperationException(__('Unable to write to payload file.', 'wp-migrate-db'));
        }

        return $handle;
    }

    /**
     * Rename a file part.
     *
     * @param string $dest
     * @param array  $meta
     *
     * @return bool|WP_Error
     * @throws Exception
     */
    public function rename_part_file($dest, $meta)
    {
        // Make sure part file has expected suffix.
        if ( ! $this->has_part_suffix($dest)) {
            return new WP_Error(
                'wpmdb_chunk_file_rename_failed',
                sprintf(__('Part file missing expected suffix %s', 'wp-migrate-db'), $dest)
            );
        }

        // Strip part suffix off end of file.
        $original_filename = $this->trim_part_suffix($dest);

        if ($this->filesystem->file_exists($original_filename)) {
            $this->filesystem->unlink($original_filename);
        }

        $renamed = rename($dest, $original_filename);

        if ( ! $renamed) {
            return new WP_Error(
                'wpmdb_chunk_file_rename_failed',
                sprintf(__('Unable to rename part file %s', 'wp-migrate-db'), $dest)
            );
        }

        return $this->verify_file_from_payload($original_filename, $meta);
    }

    /**
     * Does the destination file have a part suffix?
     *
     * @param string $dest
     *
     * @return bool
     */
    private function has_part_suffix($dest)
    {
        return false !== strrpos($dest, self::PART_SUFFIX, strlen($dest) - strlen(self::PART_SUFFIX));
    }

    /**
     * Strip the part suffix from the destination file.
     *
     * @param string $dest
     *
     * @return string
     */
    private function trim_part_suffix($dest)
    {
        if ( ! $this->has_part_suffix($dest)) {
            return $dest;
        }

        return substr($dest, 0, -strlen(self::PART_SUFFIX));
    }
}
