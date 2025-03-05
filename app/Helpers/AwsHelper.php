<?php

namespace App\Helpers;

use Aws\S3\S3Client;

class AwsHelper
{
    public static function cred()
    {
        return new S3Client([
            'version'     => 'latest',
            'region'      => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }
}
