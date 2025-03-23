<?php

// Função para verificar se o Composer está instalado
function checkComposer()
{
    $composerVersion = shell_exec('composer --version');
    if (!$composerVersion) {
        echo "Erro: Composer não encontrado. Certifique-se de que o Composer esteja instalado e disponível no PATH.\n";
        exit(1);
    }
    echo "Composer encontrado: $composerVersion\n";
}

// Função para executar um comando no shell
function runCommand($command)
{
    $output = shell_exec($command);
    echo $output;
}

// Função para criar diretórios
function createDirectory($path)
{
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
        echo "Diretório criado: $path\n";
    } else {
        echo "Diretório já existe: $path\n";
    }
}

// Função para baixar arquivos (simulação)
function downloadAllFiles($path)
{
    // Simulação de download de arquivos
    echo "Baixando arquivos para a pasta: $path\n";
}

// Função para substituir arquivo (simulação)
function replaceFile($filePath)
{
    echo "Substituindo arquivo: $filePath\n";
}

// Função para modificar o arquivo bootstrap/app.php (simulação)
function modifyAppPHP()
{
    echo "Modificando bootstrap/app.php\n";
}

// Função para instalar Bugsnag (simulação)
function installBugsnag($baseDir)
{
    echo "Instalando Bugsnag em: $baseDir\n";
}

// Função para adicionar arquivos ao composer.json (simulação)
function addFilesToComposerJson($baseDir)
{
    echo "Adicionando arquivos ao composer.json em: $baseDir\n";
}

// Função principal do script
function main()
{
    checkComposer();

    // Perguntar ao usuário se ele quer criar um novo projeto ou usar a pasta atual
    echo "Você deseja (1) Criar um novo projeto Laravel ou (2) Aplicar o template na pasta atual? (Digite 1 ou 2): ";
    $option = trim(fgets(STDIN));

    $rootDir = getcwd(); // Diretório atual

    if ($option == "1") {
        // Solicitar o nome do projeto
        echo "Digite o nome do projeto Laravel a ser instalado: ";
        $projectName = trim(fgets(STDIN));

        // Criar o projeto Laravel usando Composer
        echo "Criando o projeto Laravel: $projectName\n";
        runCommand("composer create-project laravel/laravel $projectName");

        // Verificar se o diretório do projeto foi criado
        $projectPath = $rootDir . DIRECTORY_SEPARATOR . $projectName;
        if (!is_dir($projectPath)) {
            echo "Erro: O diretório do projeto $projectName não foi criado corretamente.\n";
            exit(1);
        }

        // Alterar o diretório para o projeto criado
        chdir($projectPath);
        echo "Agora no diretório do projeto: $projectPath\n";
        $rootDir = $projectPath;
    } elseif ($option == "2") {
        // Caso o usuário queira usar a pasta atual
        echo "Aplicando o template na pasta atual...\n";

        // Verificar se o diretório Laravel já existe na pasta atual
        $currentPath = getcwd();
        if (!is_dir("$currentPath/vendor")) {
            echo "Erro: Esta não parece ser uma instalação Laravel válida. Certifique-se de estar no diretório correto.\n";
            exit(1);
        }

        echo "Aplicando o template na pasta: $currentPath\n";
        $rootDir = $currentPath;
    } else {
        echo "Opção inválida. O script será encerrado.\n";
        exit(1);
    }

    // Criar diretórios necessários
    createDirectory("$rootDir/app/Models/Support");
    createDirectory("$rootDir/app/Services/Support");
    createDirectory("$rootDir/app/Http/Middleware");
    createDirectory("$rootDir/app/ApiClients");
    createDirectory("$rootDir/app/Console/Commands");
    createDirectory("$rootDir/app/Helpers");
    createDirectory("$rootDir/database/migrations");

    // Baixar todos os arquivos das pastas especificadas
    downloadAllFiles("$rootDir/app/Models/Support");
    downloadAllFiles("$rootDir/app/Services/Support");
    downloadAllFiles("$rootDir/app/Http/Middleware");
    downloadAllFiles("$rootDir/app/ApiClients");
    downloadAllFiles("$rootDir/app/Console/Commands");
    downloadAllFiles("$rootDir/database/migrations");

    // Baixar o arquivo global.php dentro de app/Helpers
    replaceFile("$rootDir/app/Helpers/global.php");

    // Rodar comandos do Laravel e Composer (sem as migrations)
    runCommand("php artisan install:api");
    runCommand("composer require spatie/laravel-medialibrary");

    // Substituir routes/api.php pelo do repositório **após** a execução do comando
    replaceFile("$rootDir/routes/api.php");

    // Modificar bootstrap/app.php para adicionar os middlewares
    modifyAppPHP();

    // Instalar Bugsnag e realizar as configurações
    installBugsnag($rootDir);

    // Adicionar arquivos ao composer.json
    addFilesToComposerJson($rootDir);

    echo "==============================\n";
    echo "Configuração concluída!\n";
    echo "==============================\n";
}

// Executar a função principal
main();
