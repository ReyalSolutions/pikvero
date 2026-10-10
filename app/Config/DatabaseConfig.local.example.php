<?php
/**
 * Example InfinityFree / Production Database Override
 * 
 * Instructions:
 * 1. Copy or upload this file to InfinityFree inside:
 *    /htdocs/app/Config/DatabaseConfig.local.php
 * 2. Fill in the MySQL details from your InfinityFree Control Panel.
 * 3. This file is git-ignored and will NEVER be overwritten by GitHub Actions deployments.
 */

use App\Config\DatabaseConfig;

DatabaseConfig::$host     = 'sqlXXX.infinityfree.com'; // InfinityFree MySQL Hostname
DatabaseConfig::$port     = 3306;
DatabaseConfig::$dbname   = 'epiz_XXXXXXXX_pikvero';  // InfinityFree Database Name
DatabaseConfig::$username = 'epiz_XXXXXXXX';          // InfinityFree MySQL Username
DatabaseConfig::$password = 'YOUR_INFINITYFREE_PASSWORD'; // InfinityFree MySQL Password
DatabaseConfig::$charset  = 'utf8mb4';
