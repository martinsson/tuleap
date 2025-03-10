<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use GuzzleHttp\Psr7\ServerRequest;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

/**
 * This class is used to maniplulate files through WebDAV
 *
 * It's an implementation of the abstract class Sabre_DAV_File methods
 *
 */
class WebDAVFRSFile extends Sabre_DAV_File
{

    private $user;
    private $project;
    private $package;
    private $release;
    private $file;

    /**
     * Constuctor of the class
     *
     * @param PFUser $user
     * @param Project $project
     * @param FRSPackage $package
     * @param FRSRelease $release
     * @param FRSFile $file
     *
     * @return void
     */
    function __construct($user, $project, $package, $release, $file)
    {

        $this->user = $user;
        $this->project = $project;
        $this->package = $package;
        $this->release = $release;
        $this->file = $file;
    }

    /**
     * This method is used to download the file
     *
     */
    function get()
    {
        // Log the download in the Log system
        $this->logDownload($this->getUser());

        // Start download
        $response_builder = new BinaryFileResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());
        $response         = $response_builder->fromFilePath(
            ServerRequest::fromGlobals(),
            $this->getFileLocation(),
            $this->getName(),
            $this->getContentType()
        )
            ->withHeader('ETag', $this->getETag())
            ->withHeader('Last-Modified', $this->getLastModified());
        (new SapiStreamEmitter())->emit($response);
        exit();
    }

    public function put($data)
    {
        if (! file_put_contents($this->getFileLocation(), $data)) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to change data');
        }

        $frs_file_factory = new FRSFileFactory();
        $frs_file_factory->update(array(
            'file_id'      => $this->file->getFileId(),
            'file_size'    => filesize($this->getFileLocation()),
            'computed_md5' => $this->getUtils()->getIncomingFileMd5Sum($this->getFileLocation())
        ));
    }

    /**
     * Returns the name of the file
     *
     * @return String
     */
    function getName()
    {

        /* The file name is preceded by its id to keep
         *  the client able to request the file from its id
         */
        $basename = basename($this->getFile()->getFileName());
        return $basename;
    }

    /**
     * Returns the last modification date
     *
     * @return date
     */
    function getLastModified()
    {

        return $this->getFile()->getPostDate();
    }

    /**
     * Returns the file size
     *
     * @return int
     */
    function getSize()
    {

        return $this->getFile()->getFileSize();
    }

    /**
     * Returns a unique identifier of the file
     *
     * @return String
     */
    function getETag()
    {
        return '"'.$this->getUtils()->getIncomingFileMd5Sum($this->getFileLocation()).'"';
    }

    /**
     * Returns mime-type of the file
     *
     * @return String
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_File#getContentType()
     */
    function getContentType()
    {
        if (file_exists($this->getFileLocation()) && filesize($this->getFileLocation())) {
            $mime = MIME::instance();
            return $mime->type($this->getFileLocation());
        }
    }

    /**
     * Returns the file location
     *
     * @return String
     */
    function getFileLocation()
    {

        return $this->getFile()->getFileLocation();
    }

    /**
     * Returns the file as an object instance of FRSFile
     *
     * @return FRSFile
     */
    function getFile()
    {

        return $this->file;
    }

    /**
     * Returns the file Id
     *
     * @return int
     */
    function getFileId()
    {

        return $this->getFile()->getFileID();
    }

    /**
     * Returns the Id of the release that file belongs to
     *
     * @return int
     */
    function getReleaseId()
    {

        return $this->getFile()->getReleaseID();
    }

    /**
     * Returns the Id of the package that file belongs to
     *
     * @return int
     */
    function getPackageId()
    {

        return $this->getFile()->getPackageID();
    }

    /**
     * Returns the project
     *
     * @return Project
     */
    function getProject()
    {

        return $this->project;
    }

    /**
     * Returns the user
     *
     * @return PFUser
     */
    function getUser()
    {

        return $this->user;
    }

    /**
     * Returns an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    function getUtils()
    {

        return WebDAVUtils::getInstance();
    }

    /**
     * Tests whether the file is active or not
     *
     * @return bool
     */
    function isActive()
    {

        return $this->getFile()->isActive();
    }

    /**
     * Checks whether the user can download the file or not
     *
     * @param int $user
     *
     * @return bool
     */
    function userCanDownload($user)
    {

        return $this->getFile()->userCanDownload($user->getId());
    }

    /**
     * Tests whether the file exists in the file system or not
     *
     * @return bool
     */

    function fileExists()
    {

        return $this->getFile()->fileExists();
    }

    /**
     * Logs the download in the Log system
     *
     * @param int $userId
     *
     * @return bool
     */
    function logDownload($user)
    {

        return $this->getFile()->LogDownload($user->getId());
    }

    /**
     * Returns if the user is superuser or File release admin
     *
     * @return bool
     */
    function userCanWrite()
    {
        $utils = $this->getUtils();
        return $utils->userCanWrite($this->getUser(), $this->getProject()->getGroupId());
    }

    /**
     * Deletes the file
     *
     * @return void
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#delete()
     */
    function delete()
    {

        if ($this->userCanWrite()) {
            $utils = $this->getUtils();
            $result = $utils->getFileFactory()->delete_file($this->getProject()->getGroupId(), $this->getFileId());
            if ($result == 0) {
                throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_download', 'file_not_available'));
            }
        } else {
            throw new Sabre_DAV_Exception_Forbidden($GLOBALS['Language']->getText('plugin_webdav_common', 'file_denied_delete'));
        }
    }

    /**
     * A wrapper to copy
     *
     * @param String $source
     * @param String $destination
     *
     * @return bool
     */
    function copyFile($source, $destination)
    {

        return copy($source, $destination);
    }
}
