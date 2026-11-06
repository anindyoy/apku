<?php

$konfigurasi = require base_path('vendor/livewire/livewire/config/livewire.php');
$konfigurasi['temporary_file_upload']['rules'] = ['required', 'file', 'max:10240'];

return $konfigurasi;
