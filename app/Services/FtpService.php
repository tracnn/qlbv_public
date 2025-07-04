<?php

namespace App\Services;

class FtpService
{
    protected $connection;

    public function connect()
    {
        $ftp_config = config('organization.ftp_emr_config');
        $host = $ftp_config['host'];
        $port = $ftp_config['port'];
        $username = $ftp_config['username'];
        $password = $ftp_config['password'];
        $ssl = $ftp_config['ssl'];
        $pasv = $ftp_config['ftp_pasv'];

        if ($ssl) {
            $this->connection = ftp_ssl_connect($host, $port);
        } else {
            $this->connection = ftp_connect($host, $port);
        }

        if (!$this->connection) {
            throw new \Exception("Không thể kết nối đến FTP server");
        }

        if (!ftp_login($this->connection, $username, $password)) {
            ftp_close($this->connection);
            throw new \Exception("Đăng nhập FTP thất bại");
        }

        if ($pasv) {
            ftp_pasv($this->connection, true);
        }

        return true;
    }

    public function upload($localPath, $remotePath)
    {
        return ftp_put($this->connection, $remotePath, $localPath, FTP_BINARY);
    }

    public function list($directory = '.')
    {
        return ftp_nlist($this->connection, $directory);
    }

    public function download($remotePath, $localPath)
    {
        return ftp_get($this->connection, $localPath, $remotePath, FTP_BINARY);
    }

    public function delete($remotePath)
    {
        return ftp_delete($this->connection, $remotePath);
    }

    public function close()
    {
        if ($this->connection) {
            ftp_close($this->connection);
        }
    }

    public function getContent($remotePath)
    {
        $tempHandle = fopen('php://temp', 'r+');

        if (ftp_fget($this->connection, $tempHandle, $remotePath, FTP_BINARY, 0)) {
            rewind($tempHandle);
            $contents = stream_get_contents($tempHandle);
            fclose($tempHandle);
            return $contents;
        } else {
            fclose($tempHandle);
            throw new \Exception("Không đọc được nội dung file: $remotePath");
        }
    }
}