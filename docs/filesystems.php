<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],

        'emr' => [
            'driver' => 'local',
            'root' => '\\\10.0.0.12\d$\Backend\FSS1',
        ],

        'xml4210' => [
            'driver' => 'local',
            'root' => 'D:\XML\4210',
        ],

        'xml4210tt' => [
            'driver' => 'local',
            'root' => 'D:\XML\4210TT',
        ],

        'CatalogBHXH' => [
            'driver' => 'local',
            'root' => 'D:\CatalogBHXH',
        ],

        'xml130tt' => [
            'driver' => 'local',
            'root' => 'D:\XML\4750TT',
        ],

        'xml130' => [
            'driver' => 'local',
            'root' => 'D:\XML\4750',
        ],

        'xml130GoogleDrive' => [
            'driver' => 'local',
            'root' => 'D:\XML\ImportXml4750',
        ],

        'exportXml130' => [
            'driver' => 'local',
            'root' => 'D:\XML\ExportXml4750',
        ],

        'trucDuLieuYTe' => [
            'driver' => 'local',
            'root' => 'D:\XML\TrucDuLieuYTe',
        ],

        'xml3176' => [
            'driver' => 'local',
            'root' => 'D:\XML\3176',
        ],

        'xml3176tt' => [
            'driver' => 'local',
            'root' => 'D:\XML\3176TT',
        ],

        'xml3176GoogleDrive' => [
            'driver' => 'local',
            'root' => 'D:\XML\ImportXml3176',
        ],

        'exportXml3176' => [
            'driver' => 'local',
            'root' => 'D:\XML\ExportXml3176',
        ],

        // Hai thu muc cua module chung tu dien tu (PL02). Dat canh nhau o day chu khong o
        // organization.chung_tu_dien_tu: duong dan he tep la duong dan he tep, va de chung
        // canh nhau thi nguoi trien khai cho don vi moi chi phai nhin MOT cho.
        //
        // Ca hai doc duoc tu env, nen don vi khong co o D: chi can dat hai bien trong .env
        // chu khong phai sua tep nay.
        //
        // PHAI CO CA HAI o day: tep nay la ban mau ma don vi moi chep sang config/. Thieu
        // mot disk thi Storage::disk() nem InvalidArgumentException - va no nem luc nguoi ta
        // bam nut, khong phai luc trien khai.
        'exportCtdt' => [
            'driver' => 'local',
            'root' => 'D:\XML\ChungTuDienTu',
        ],

        'importCtdt' => [
            'driver' => 'local',
            'root' => 'D:\XML\ChungTuDienTu\inbox',
        ],

        // Noi ghi XML danh muc TT12 da ky. Chia theo thang o trong (SignTt12Job) nen thu muc
        // goc khong phinh. Thieu dia nay thi Storage::disk('exportTt12') nem luc nguoi ta bam
        // nut ky, khong phai luc trien khai.
        'exportTt12' => [
            'driver' => 'local',
            'root' => 'D:\XML\tt12',
        ],

        'congDuLieuYTeDienBien' => [
            'driver' => 'local',
            'root' => 'D:\XML\CongDuLieuYTeDienBien',
        ],
    ],
];
