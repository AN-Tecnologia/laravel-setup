@echo off
setlocal enabledelayedexpansion

:: Obtém o diretório onde o script está sendo executado
set "ROOT_DIR=%CD%"
set "SUPPORT_DIR=%ROOT_DIR%\app\Models\Support"
set "GITHUB_URL=https://raw.githubusercontent.com/AN-Tecnologia/laravel-setup/main/app/models/Support"

:: Criando a pasta Support se não existir
if not exist "%SUPPORT_DIR%" (
    echo Criando diretório Support em %SUPPORT_DIR%...
    mkdir "%SUPPORT_DIR%"
)

:: Baixar os modelos
set "FILES=ClientRequest.php ServerResponse.php ApiRequest.php ApiResponse.php"

for %%F in (%FILES%) do (
    echo Baixando %%F...
    powershell -Command "(New-Object Net.WebClient).DownloadFile('%GITHUB_URL%/%%F', '%SUPPORT_DIR%\%%F')"
)

echo Todos os arquivos foram baixados com sucesso!
