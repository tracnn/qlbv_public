<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddConfigOrganizationKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'config:add-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new key-value pair to the organization config file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Đọc file config hiện tại
        $config = config('organization');

        // Mảng chứa các key-value mới muốn thêm vào
        $newKeys = [
            'base_url' => '',
        ];

        // Kiểm tra nếu mảng `newKeys` trống
        if (empty($newKeys)) {
            $this->info("No keys were provided to add.");
            return;
        }
        
        $addedKeys = [];
        $existingKeys = [];

        // Lặp qua các key-value và thêm vào cấu hình nếu chưa tồn tại
        foreach ($newKeys as $key => $value) {
            if (!array_key_exists($key, $config)) {
                $config[$key] = $value;
                $addedKeys[] = $key;
            } else {
                $existingKeys[] = $key;
            }
        }

        // Ghi lại config mới vào file organization.php
        $configFile = config_path('organization.php');
        $configContent = var_export($config, true);
        file_put_contents($configFile, "<?php\n\nreturn " . $configContent . ";\n");

        // Thông báo những key đã được thêm thành công
        if (!empty($addedKeys)) {
            $this->info("The following keys have been added successfully: " . implode(', ', $addedKeys));
        }

        // Thông báo những key đã tồn tại
        if (!empty($existingKeys)) {
            $this->info("The following keys already exist and were not added: " . implode(', ', $existingKeys));
        }
    }
}
