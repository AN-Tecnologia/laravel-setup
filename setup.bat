@echo off
setlocal enabledelayedexpansion

:: Obtém o diretório onde o script está sendo executado
set "ROOT_DIR=%CD%"
set "GITHUB_BASE_URL=https://raw.githubusercontent.com/AN-Tecnologia/laravel-setup/main"

:: Criar diretórios necessários
call :create_directory "app\Models\Support"
call :create_directory "app\Services\Support"   :: Adicionado para a nova pasta
call :create_directory "app\Http\Middleware"
call :create_directory "app\ApiClients"
call :create_directory "app\Console\Commands"
call :create_directory "app\Helpers"
call :create_directory "database\migrations"

:: Baixar todos os arquivos das pastas especificadas
call :download_all_files "app\Models\Support"
call :download_all_files "app\Services\Support"  :: Adicionado para baixar arquivos dessa pasta
call :download_all_files "app\Http\Middleware"
call :download_all_files "app\ApiClients"
call :download_all_files "app\Console\Commands"
call :download_all_files "database\migrations"

:: Baixar o arquivo global.php dentro de app\Helpers
call :replace_file "app\Helpers\global.php"

:: Modificar bootstrap\app.php para adicionar os middlewares
call :modify_app_php

:: Instalar Bugsnag e realizar as configurações
call :install_bugsnag

:: Rodar comandos do Laravel e Composer (sem as migrations)
call :run_command "php artisan install:api"
call :run_command "composer require spatie/laravel-medialibrary"

:: Substituir routes/api.php pelo do repositório **após** a execução do comando
call :replace_file "routes/api.php"

echo.
echo ==============================
echo  Configuração concluída!
echo ==============================
exit /b

:: Função para criar diretório
:create_directory
set "DIR_PATH=%ROOT_DIR%\%~1"
if not exist "%DIR_PATH%" (
    echo Criando diretório %DIR_PATH%...
    mkdir "%DIR_PATH%"
)
exit /b

:: Função para baixar todos os arquivos de uma pasta
:download_all_files
set "SUB_DIR=%~1"
set "DEST_DIR=%ROOT_DIR%\%SUB_DIR%"
set "GITHUB_URL=%GITHUB_BASE_URL%/%SUB_DIR%"

echo Baixando arquivos da pasta %SUB_DIR%...

powershell -Command "& {
    $url = '%GITHUB_URL%/';
    $dest = '%DEST_DIR%';
    mkdir -Force $dest | Out-Null;
    $files = Invoke-RestMethod -Uri $url | Select-Object -ExpandProperty name;
    foreach ($file in $files) {
        $fileUrl = $url + $file;
        $filePath = Join-Path $dest $file;
        Invoke-WebRequest -Uri $fileUrl -OutFile $filePath;
    }
}"
exit /b

:: Função para baixar e substituir um arquivo específico
:replace_file
set "FILE_PATH=%ROOT_DIR%\%~1"
set "GITHUB_FILE_URL=%GITHUB_BASE_URL%/%~1"

echo Substituindo %~1...
powershell -Command "(New-Object Net.WebClient).DownloadFile('%GITHUB_FILE_URL%', '%FILE_PATH%')"
exit /b

:: Função para modificar bootstrap\app.php
:modify_app_php
set "FILE_PATH=%ROOT_DIR%\bootstrap\app.php"
echo Modificando %FILE_PATH%...

powershell -Command "& {
    $filePath = '%FILE_PATH%';
    $content = Get-Content -Path $filePath -Raw;

    if ($content -match '\$middleware->append\\(LogRequest::class\\)' -and $content -match '\$middleware->append\\(LogResponse::class\\)') {
        echo 'Middleware já configurado. Nenhuma alteração necessária.';
        exit 0;
    }

    $pattern = '(?s)(->withMiddleware\\s*\\(\\s*function\\s*\\(\\s*Middleware\\s*\\$middleware\\s*\\)\\s*\\{)';
    if ($content -match $pattern) {
        $replacement = '$1`n        $middleware->append(LogRequest::class);`n        $middleware->append(LogResponse::class);';
        $content = $content -replace $pattern, $replacement;
        Set-Content -Path $filePath -Value $content;
        echo 'Middleware adicionado com sucesso.';
    } else {
        echo '[ERRO] Não foi possível localizar o bloco withMiddleware no arquivo.';
        exit 1;
    }
}"
exit /b

:: Função para instalar e configurar Bugsnag
:install_bugsnag
echo Instalando Bugsnag...
call :run_command "composer require bugsnag/bugsnag-laravel"

:: Adicionando Bugsnag no .env
echo Adicionando chave de API do Bugsnag ao .env e .env.example...
echo BUGSNAG_API_KEY= >> "%ROOT_DIR%\.env"
echo BUGSNAG_API_KEY= >> "%ROOT_DIR%\.env.example"

:: Modificando configuração de log
call :replace_file "config/logging.php"

:: Adicionando Bugsnag ao providers em bootstrap/providers.php
call :add_bugsnag_provider

:: Adicionando Bugsnag no app.php
call :modify_app_php_bugsnag

exit /b
