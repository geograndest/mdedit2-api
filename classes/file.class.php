<?php

namespace MdEditApi;

require_once __DIR__ . '/helpers.class.php';

/**
 * Class File permet de générer un fichiers (lecture, création, suppression, modification)
 * 
 * Exemple:
 * ```
 * require_once './file.class.php';
 * 
 * ```
 * 
 */
class File
{
    protected $root_path;
    protected $path;
    protected $basename;
    protected $ext;
    protected $file;
    protected $filename;
    protected $size;
    protected $atime;
    protected $mtime;
    protected $ctime;
    protected $isfile;
    protected $infos;

    public function __construct($file = false, $root_path = false)
    {
        $this->getFileInfo($file, $root_path = false);
    }

    private function getFileInfo($file, $root_path = false)
    {
        $this->file = $file;
        $this->root_path = $root_path;
        if (\is_file($root_path . $file)) {
            $pathinfo = \pathinfo($file);
            $fileinfo = \stat($root_path . $file);
            $this->path = $pathinfo['dirname'];
            $this->basename = $pathinfo['basename'];
            $this->ext = $pathinfo['extension'];
            $this->filename = $pathinfo['filename'];
            $this->size = $fileinfo['size'];
            $this->atime = $fileinfo['atime'];
            $this->mtime = $fileinfo['mtime'];
            $this->ctime = $fileinfo['ctime'];
            if ($this->ext == 'xml') {
                $this->infos['fileIdentifier'] = Helpers::getXmlFileInfo($file, 'fileIdentifier', $root_path)['fileIdentifier'];
                $this->infos['dataTitle'] = Helpers::getXmlFileInfo($file, 'dataTitle', $root_path)['dataTitle'];
            }
            return true;
        }
        return false;
    }

    public function getFile($file = false, $root_path = false)
    {
        if ($file) {
            $this->file = $file;
        }
        if ($root_path) {
            $this->root_path = $root_path;
        }
        $response = [
            'file' => $this->file,
            'root_path' => $this->root_path,
            'success' => false
        ];
        if ($this->getFileInfo($this->file, $this->root_path)) {
            $response['path'] = $this->path;
            $response['basename'] = $this->basename;
            $response['ext'] = $this->ext;
            $response['file'] = $this->file;
            $response['filename'] = $this->filename;
            $response['size'] = $this->size;
            $response['atime'] = $this->atime;
            $response['mtime'] = $this->mtime;
            $response['ctime'] = $this->ctime;
            // $response['content'] = $this->content;
            $response['fileIdentifier'] = $this->infos['fileIdentifier'];
            $response['dataTitle'] = $this->infos['dataTitle'];
            $response['success'] = true;
        }
        return $response;
    }

    public function saveFile($content, $file = false, $root_path = false)
    {
        if ($file) {
            $this->file = $file;
        }
        $response = [
            'file' => $this->file,
            'success' => false
        ];
        if ($content) {
            if ($this->file) {
                // Check if path exists else create it
                $pathinfo = \pathinfo($root_path . $this->file);
                $path = $pathinfo['dirname'];
                if (!is_dir($path)) {
                    if (!mkdir($path, 0777, true)) {
                        $response['message'] = "Le dossier " . $path . " ne peut pas être créé.";
                    }
                }
            }
            if (is_dir($path) and $this->file and $content) {
                chmod($path, 0777);
                file_put_contents($root_path . $this->file, $content);
                chmod($root_path . $file, 0777);
                $response['success'] = $this->getFileInfo($this->file, $root_path);
                $response['file'] = $this->getFile($this->file, $root_path);
            }
        }
        return $response;
    }

    public function copyFile($old, $new, $path = false)
    {
        $response = [
            'old' => $this->getFile($path . $old),
            'new' => $new,
            'path' => $path,
            'success' => false
        ];
        if ($this->getFileInfo($old)) {
            copy($path . $old, $path . $new);
            $response['new'] = $this->getFile($path . $new);
            $response['success'] = true;
        }
        return $response;
    }

    public function moveFile($old, $new, $path = false)
    {
        $response = [
            'old' => $this->getFile($path . $old),
            'new' => $new,
            'path' => $path,
            'success' => false
        ];
        if (rename($path . $old, $path . $new)) {
            $response['new'] = $this->getFile($path . $new);
            $response['success'] = true;
        }

        return $response;
    }

    public function deleteFile($file = false, $root_path = false)
    {
        if ($file) {
            $this->file = $file;
        }
        $response = [
            'file' => $this->file,
            'success' => false
        ];
        if ($this->getFileInfo($this->file, $root_path)) {
            unlink($root_path . $this->file);
            $response['success'] = true;
        }
        return $response;
    }
}
