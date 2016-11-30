<?php

namespace Ojs\ImportBundle\Helper;

class FileHelper {
    public static $mimeToExtMap = [
        'application/pdf'    => 'pdf',
        'image/jpeg'         => 'jpg',
        'image/png'          => 'png',
        'application/msword' => 'doc',
        'application/zip'    => 'zip',
        'application/xml'    => 'xml',
        'text/plain'         => 'txt',
        'application/rar '   => 'rar',
        'application/x-rar'  => 'rar',
        'application/octet-stream' => 'bin',
        'application/text-plain:formatted' => 'txt',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
    ];
}
