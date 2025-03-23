<?php
class Setup
{
    public ?string $rootDir;
    public ?string $githubBaseUrl;

    public function __construct()
    {
        $this->setUrls();
    }

    function checkComposer(): void
    {
        $composerPath = shell_exec('which composer');

        if (empty($composerPath)) {
            echo "Erro: O Composer não está instalado ou não está disponível no PATH.\n";
            exit(1);
        }

        echo "Composer encontrado em: $composerPath\n";
    }

    function createDirectory(string $dirPath): void
    {
        $fullPath = $this->rootDir . DIRECTORY_SEPARATOR . $dirPath;

        if (!is_dir($fullPath)) {
            echo "Criando diretório $fullPath...\n";
            mkdir($fullPath, 0777, true);
        }
    }

    function downloadAllFiles(string $subDir): void
    {
        $destDir = $this->getDestinationDirectory($subDir);

        $this->createDirectoryIfNotExists($destDir);

        $files = $this->fetchFilesFromGitHub($subDir);

        if (!$files) {
            echo "Nenhum arquivo encontrado ou erro ao acessar a API.\n";
            return;
        }

        $this->downloadGithubFiles($files, $destDir);
    }

    private function getDestinationDirectory(string $subDir): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . $subDir;
    }

    private function createDirectoryIfNotExists(string $dirPath): void
    {
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
            echo "Diretório $dirPath criado com sucesso.\n";
        }
    }

    private function downloadGithubFiles(array $files, string $destDir): void
    {
        foreach ($files as $file) {
            if ($this->isValidFile($file)) {
                $fileUrl = $file['download_url'];
                $filePath = $destDir . DIRECTORY_SEPARATOR . $file['name'];
                $this->downloadFile($fileUrl, $filePath);
            } else {
                echo "O item {$file['name']} não é um arquivo válido.\n";
            }
        }
    }

    private function isValidFile(array $file): bool
    {
        return isset($file['type']) && $file['type'] === 'file' && isset($file['download_url'], $file['name']);
    }

    function fetchFilesFromGitHub(string $subDir): ?array
    {
        $apiUrl = $this->buildGitHubApiUrl($subDir);
        $headers = $this->getGitHubHeaders();

        $response = $this->executeCurlRequest($apiUrl, $headers);

        if (!$response) {
            return null;
        }

        return $this->decodeJsonResponse($response);
    }

    private function buildGitHubApiUrl(string $subDir): string
    {
        return "https://api.github.com/repos/AN-Tecnologia/laravel-setup/contents/$subDir";
    }

    private function getGitHubHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'PHP Script',
        ];
    }

    private function executeCurlRequest(string $url, array $headers): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if ($response === false) {
            echo "Erro ao acessar a API do GitHub: " . curl_error($ch) . "\n";
            curl_close($ch);
            return null;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            echo "Erro na resposta da API. Código de status HTTP: $statusCode\n";
            return null;
        }

        return $response;
    }

    private function decodeJsonResponse(string $response): ?array
    {
        $files = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Erro ao decodificar a resposta JSON: " . json_last_error_msg() . "\n";
            return null;
        }

        return $files;
    }

    function downloadFile(string $fileUrl, string $filePath): void
    {
        echo "Baixando $fileUrl...\n";

        $fileContent = file_get_contents($fileUrl);

        if ($fileContent === false) {
            echo "Erro ao baixar o arquivo $fileUrl\n";
            return;
        }

        file_put_contents($filePath, $fileContent);
        echo "Arquivo salvo em: $filePath\n";
    }

    function testDirectory(string $directoryPath): bool
    {
        if (!is_dir($directoryPath)) {
            echo "Diretório $directoryPath não encontrado.\n";
            return false;
        }
        return true;
    }

    function createDirectoryAndFile(string $filePath): string
    {
        $fullPath = $this->rootDir . DIRECTORY_SEPARATOR . $filePath;
        $directoryPath = dirname($fullPath);

        if (!$this->testDirectory($directoryPath)) {
            echo "Criando diretório $directoryPath...\n";
            $this->createDirectory($directoryPath);
        }

        if (!file_exists($fullPath)) {
            echo "Arquivo $filePath não encontrado. Criando arquivo...\n";
            $this->createFile($fullPath);
        }

        return $fullPath;
    }

    function createFile(string $filePath): void
    {
        file_put_contents($filePath, '');
    }

    function replaceFile(string $filePath): void
    {
        if (empty($this->rootDir)) {
            echo "[ERRO] O parâmetro baseDir não foi passado corretamente.\n";
            exit(1);
        }

        $githubFileUrl = $this->githubBaseUrl . '/' . $filePath;

        $fullPath = $this->createDirectoryAndFile($filePath, $this->rootDir);

        echo "Substituindo $filePath...\n";
        $this->downloadFile($githubFileUrl, $fullPath);
    }

    function checkMiddlewareImports(string $content): bool
    {
        return (strpos($content, 'use App\\Http\\Middleware\\LogRequest;') !== false) &&
            (strpos($content, 'use App\\Http\\Middleware\\LogResponse;') !== false);
    }

    function addMiddlewareImports(string $filePath, string $content): void
    {
        echo 'Importações de middleware não encontradas. Adicionando...' . PHP_EOL;

        $importLines = PHP_EOL . 'use App\\Http\\Middleware\\LogRequest;' . PHP_EOL . 'use App\\Http\\Middleware\\LogResponse;' . PHP_EOL;

        $content = preg_replace('/<\?php/', "<?php" . $importLines, $content, 1);

        file_put_contents($filePath, $content);

        echo 'Importações de middleware adicionadas com sucesso.' . PHP_EOL;
    }

    function addMiddlewareInWithMiddleware(string $filePath): void
    {
        echo "Lendo conteúdo do arquivo $filePath..." . PHP_EOL;

        $content = file_get_contents($filePath);

        echo "Verificando se '->withMiddleware' existe no conteúdo..." . PHP_EOL;
        if (preg_match('/->withMiddleware\s*\(\s*function\s*\(Middleware\s*\$middleware\s*\)\s*\{/', $content)) {
            echo "'->withMiddleware' encontrado, adicionando middleware..." . PHP_EOL;

            $lines = explode(PHP_EOL, $content);

            echo "Primeiras 5 linhas do conteúdo do arquivo:" . PHP_EOL;
            for ($i = 0; $i < 5; $i++) {
                echo $lines[$i] . PHP_EOL;
            }

            echo "Procurando o índice da linha onde ocorre '->withMiddleware'..." . PHP_EOL;
            $middlewareIndex = null;
            foreach ($lines as $i => $line) {
                if (preg_match('/->withMiddleware/', $line)) {
                    $middlewareIndex = $i;
                    break;
                }
            }

            echo "Índice do 'withMiddleware': $middlewareIndex" . PHP_EOL;

            if ($middlewareIndex !== null) {
                echo "Adicionando os middlewares após a linha do 'withMiddleware'..." . PHP_EOL;

                $middlewareString = PHP_EOL . "        \$middleware->append(LogRequest::class);" . PHP_EOL . "        \$middleware->append(LogResponse::class);";
                $lines[$middlewareIndex + 1] .= $middlewareString;

                echo "Middleware adicionado com sucesso." . PHP_EOL;
            } else {
                echo "[ERRO] Não foi possível encontrar a linha do withMiddleware." . PHP_EOL;
                exit(1);
            }

            echo "Salvando alterações no arquivo $filePath..." . PHP_EOL;
            file_put_contents($filePath, implode(PHP_EOL, $lines));

            echo 'Alterações no arquivo feitas com sucesso.' . PHP_EOL;
        } else {
            echo "[ERRO] Não foi possível localizar o bloco withMiddleware no arquivo." . PHP_EOL;
            exit(1);
        }
    }

    function modifyAppPHP(): void
    {
        $filePath = $this->rootDir . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
        echo "Modificando $filePath..." . PHP_EOL;

        $content = file_get_contents($filePath);

        $this->addMiddlewareInWithMiddleware($filePath);

        if ($this->checkMiddlewareImports($content)) {
            echo 'Importações de middleware já configuradas. Nenhuma alteração necessária.' . PHP_EOL;
        } else {
            $this->addMiddlewareImports($filePath, $content);
        }
    }

    function runCommand(string $command): void
    {
        echo "Executando comando: $command" . PHP_EOL;
        exec($command);
    }


    function installBugsnag(): void
    {
        echo "Instalando Bugsnag..." . PHP_EOL;
        $this->runCommand("composer require bugsnag/bugsnag-laravel");
        $this->addBugsnagApiKeyToEnv($this->rootDir);
        $loggingConfigPath = $this->rootDir . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "logging.php";
        $this->addBugsnagLoggingChannel($loggingConfigPath);
        $this->modifyLogStackVariable($this->rootDir);
        $this->addBugsnagBootstrapper($this->rootDir);
        $this->addBugsnagServiceProvider($this->rootDir);
    }

    function addBugsnagApiKeyToEnv(): void
    {
        echo "Adicionando chave de API do Bugsnag ao .env e .env.example..." . PHP_EOL;

        $envFilePath = $this->getEnvFilePath();
        $envExampleFilePath = $this->getEnvExampleFilePath();

        if (!$this->validateEnvFiles($envFilePath, $envExampleFilePath)) {
            return;
        }

        $this->appendApiKeyIfMissing($envFilePath);
        $this->appendApiKeyIfMissing($envExampleFilePath);
    }

    private function getEnvFilePath(): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . ".env";
    }

    private function getEnvExampleFilePath(): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . ".env.example";
    }

    private function validateEnvFiles(string $envFilePath, string $envExampleFilePath): bool
    {
        if (!file_exists($envFilePath)) {
            echo "Erro: O arquivo .env não foi encontrado no diretório $this->rootDir." . PHP_EOL;
            return false;
        }

        if (!file_exists($envExampleFilePath)) {
            echo "Erro: O arquivo .env.example não foi encontrado no diretório $this->rootDir." . PHP_EOL;
            return false;
        }

        return true;
    }

    private function appendApiKeyIfMissing(string $filePath): void
    {
        $content = file_get_contents($filePath);

        if (strpos($content, "BUGSNAG_API_KEY=") === false) {
            file_put_contents($filePath, PHP_EOL . "BUGSNAG_API_KEY=" . PHP_EOL, FILE_APPEND);
            echo "Chave de API do Bugsnag adicionada ao $filePath." . PHP_EOL;
        } else {
            echo "A chave de API já existe no $filePath." . PHP_EOL;
        }
    }

    function addBugsnagBootstrapper(): void
    {
        echo "Adicionando Bugsnag OomBootstrapper no arquivo bootstrap/app.php..." . PHP_EOL;

        $appFilePath = $this->getBootstrapFilePath();

        if (!$this->validateBootstrapFile($appFilePath)) {
            return;
        }

        $this->insertOomBootstrapper($appFilePath);
    }

    private function getBootstrapFilePath(): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . "bootstrap" . DIRECTORY_SEPARATOR . "app.php";
    }

    private function validateBootstrapFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            echo "Erro: O arquivo $filePath não foi encontrado." . PHP_EOL;
            return false;
        }
        return true;
    }

    private function insertOomBootstrapper(string $filePath): void
    {
        $content = file_get_contents($filePath);

        if ($this->isOomBootstrapperPresent($content)) {
            echo 'OomBootstrapper já foi adicionado no arquivo bootstrap/app.php.' . PHP_EOL;
            return;
        }

        $updatedContent = $this->addOomBootstrapperToContent($content);

        file_put_contents($filePath, $updatedContent);
        echo 'OomBootstrapper adicionado com sucesso no arquivo bootstrap/app.php.' . PHP_EOL;
    }

    private function isOomBootstrapperPresent(string $content): bool
    {
        return strpos($content, 'OomBootstrapper') !== false;
    }

    private function addOomBootstrapperToContent(string $content): string
    {
        $pattern = '/(?<=return\s+\$app;)/';
        $replacement = PHP_EOL . '(new \Bugsnag\BugsnagLaravel\OomBootstrapper())->bootstrap();' . PHP_EOL;
        return preg_replace($pattern, $replacement, $content);
    }


    function addBugsnagServiceProvider(): void
    {
        echo "Adicionando BugsnagServiceProvider no arquivo bootstrap/providers.php..." . PHP_EOL;

        $providersFilePath = $this->getProvidersFilePath();

        if (!$this->validateProvidersFile($providersFilePath)) {
            return;
        }

        $this->insertBugsnagServiceProvider($providersFilePath);
    }

    private function getProvidersFilePath(): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . "bootstrap" . DIRECTORY_SEPARATOR . "providers.php";
    }

    private function validateProvidersFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            echo "Erro: O arquivo $filePath não foi encontrado." . PHP_EOL;
            return false;
        }
        return true;
    }

    private function insertBugsnagServiceProvider(string $filePath): void
    {
        $content = file_get_contents($filePath);

        if ($this->isBugsnagServiceProviderPresent($content)) {
            echo 'BugsnagServiceProvider já está presente no arquivo bootstrap/providers.php.' . PHP_EOL;
            return;
        }

        $updatedContent = $this->addBugsnagServiceProviderToContent($content);

        file_put_contents($filePath, $updatedContent);
        echo 'BugsnagServiceProvider adicionado com sucesso no arquivo bootstrap/providers.php.' . PHP_EOL;
    }

    private function isBugsnagServiceProviderPresent(string $content): bool
    {
        return strpos($content, 'Bugsnag\BugsnagLaravel\BugsnagServiceProvider::class') !== false;
    }

    private function addBugsnagServiceProviderToContent(string $content): string
    {
        $providerLine = "    Bugsnag\BugsnagLaravel\BugsnagServiceProvider::class,";
        return preg_replace('/(\[)/', PHP_EOL . '$1' . PHP_EOL . $providerLine, $content, 1);
    }


    function modifyLogStackVariable(): void
    {
        echo "Alterando a variável LOG_STACK para 'single,bugsnag' nos arquivos .env e .env.example..." . PHP_EOL;

        $envPath = $this->getEnvFilePath();
        $envExamplePath = $this->getEnvExampleFilePath();

        if (!$this->validateEnvFile($envPath) || !$this->validateEnvFile($envExamplePath)) {
            return;
        }

        $this->updateLogStackVariable($envPath);
        $this->updateLogStackVariable($envExamplePath);

        echo "A variável LOG_STACK foi alterada com sucesso para 'single,bugsnag'." . PHP_EOL;
    }

    private function validateEnvFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            echo "Erro: O arquivo $filePath não foi encontrado." . PHP_EOL;
            return false;
        }
        return true;
    }

    private function updateLogStackVariable(string $filePath): void
    {
        $content = file_get_contents($filePath);
        $updatedContent = preg_replace('/^LOG_STACK=.*$/m', 'LOG_STACK="single,bugsnag"', $content);
        file_put_contents($filePath, $updatedContent);
    }


    function addBugsnagLoggingChannel(string $configFilePath): void
    {
        echo "Adicionando canal Bugsnag ao arquivo de configuração de logging..." . PHP_EOL;

        if (!$this->fileExists($configFilePath)) {
            return;
        }

        if ($this->hasBugsnagChannel($configFilePath)) {
            echo "Canal Bugsnag já está presente no arquivo de configuração de logging." . PHP_EOL;
            return;
        }

        $this->appendBugsnagChannel($configFilePath);
        echo "Canal Bugsnag adicionado ao arquivo de configuração de logging com sucesso." . PHP_EOL;
    }

    private function fileExists(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            echo "Erro: O arquivo $filePath não foi encontrado." . PHP_EOL;
            return false;
        }
        return true;
    }

    private function hasBugsnagChannel(string $filePath): bool
    {
        return strpos(file_get_contents($filePath), "'bugsnag' => [") !== false;
    }

    private function appendBugsnagChannel(string $filePath): void
    {
        $bugsnagChannel = "\r\n        'bugsnag' => [\r\n" .
            "            'driver' => 'bugsnag',\r\n" .
            "        ],";

        $content = file_get_contents($filePath);
        $updatedContent = preg_replace('/\]$/', $bugsnagChannel, $content);
        file_put_contents($filePath, $updatedContent);
    }


    function addFilesToComposerJson(): void
    {
        echo "Adicionando a chave 'files' no 'composer.json'..." . PHP_EOL;

        $composerPath = $this->rootDir . '/composer.json';

        if (!$this->fileExists($composerPath)) {
            exit(1);
        }

        $composerContent = $this->readComposerJson($composerPath);

        if (!$this->hasAutoloadDevKey($composerContent)) {
            exit(1);
        }

        if ($this->addFilesKey($composerContent)) {
            $this->writeComposerJson($composerPath, $composerContent);
            echo "Alterações no 'composer.json' feitas com sucesso." . PHP_EOL;
        }
    }

    private function readComposerJson(string $filePath): array
    {
        return json_decode(file_get_contents($filePath), true) ?? [];
    }

    private function hasAutoloadDevKey(array $composerContent): bool
    {
        if (!isset($composerContent['autoload-dev'])) {
            echo "[ERRO] A chave 'autoload-dev' não existe no composer.json." . PHP_EOL;
            return false;
        }
        return true;
    }

    private function addFilesKey(array &$composerContent): bool
    {
        if (!isset($composerContent['autoload-dev']['files'])) {
            echo "A chave 'files' não encontrada. Adicionando..." . PHP_EOL;
            $composerContent['autoload-dev']['files'] = ['app/Helpers/global.php'];
            return true;
        }

        if (!in_array('app/Helpers/global.php', $composerContent['autoload-dev']['files'])) {
            $composerContent['autoload-dev']['files'][] = 'app/Helpers/global.php';
            echo "A chave 'files' foi atualizada com sucesso." . PHP_EOL;
            return true;
        }

        echo "A chave 'files' já contém 'app/Helpers/global.php'. Nenhuma modificação necessária." . PHP_EOL;
        return false;
    }

    private function writeComposerJson(string $filePath, array $composerContent): void
    {
        file_put_contents($filePath, json_encode($composerContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    function handleFirstOption(): void
    {
        echo "Digite o nome do projeto Laravel a ser instalado: ";
        $projectName = trim(fgets(STDIN));

        echo "Criando o projeto Laravel: $projectName\n";
        $this->runCommand("composer create-project laravel/laravel $projectName");

        $projectPath = $this->rootDir . DIRECTORY_SEPARATOR . $projectName;
        if (!is_dir($projectPath)) {
            echo "Erro: O diretório do projeto $projectName não foi criado corretamente.\n";
            exit(1);
        }

        chdir($projectPath);
        echo "Agora no diretório do projeto: $projectPath\n";
        $this->rootDir = $projectPath;
    }

    function handleSecondOption(): void
    {
        echo "Aplicando o template na pasta atual...\n";

        $currentPath = getcwd();
        if (!is_dir("$currentPath/vendor")) {
            echo "Erro: Esta não parece ser uma instalação Laravel válida. Certifique-se de estar no diretório correto.\n";
            exit(1);
        }

        echo "Aplicando o template na pasta: $currentPath\n";
        $this->rootDir = $currentPath;
    }

    function createDirectories(): void
    {
        $this->createDirectory("app/Models/Support");
        $this->createDirectory("app/Services/Support");
        $this->createDirectory("app/Http/Middleware");
        $this->createDirectory("app/ApiClients");
        $this->createDirectory("app/Console/Commands");
        $this->createDirectory("app/Helpers");
        $this->createDirectory("database/migrations");
    }

    function downloadFiles(): void
    {
        $this->downloadAllFiles("app/Models/Support");
        $this->downloadAllFiles("app/Services/Support");
        $this->downloadAllFiles("app/Http/Middleware");
        $this->downloadAllFiles("app/ApiClients");
        $this->downloadAllFiles("app/Console/Commands");
        $this->downloadAllFiles("database/migrations");
    }

    function printConclusionMessage(): void
    {
        echo "==============================\n";
        echo "Configuração concluída!\n";
        echo "==============================\n";
    }

    function setUrls(): void
    {
        $this->rootDir = getcwd();
        $this->githubBaseUrl = "https://raw.githubusercontent.com/AN-Tecnologia/laravel-setup/main";
    }

    function getOption(): int|string
    {
        echo "Você deseja (1) Criar um novo projeto Laravel ou (2) Aplicar o template na pasta atual? (Digite 1 ou 2): ";
        return trim(fgets(STDIN));
    }

    function setBaseProject(): void
    {
        $option = $this->getOption();

        if ($option == "1") {
            $this->handleFirstOption();
        } elseif ($option == "2") {
            $this->handleSecondOption();
        } else {
            echo "Opção inválida. O script será encerrado.\n";
            exit(1);
        }
    }

    function main(): void
    {
        $this->checkComposer();
        $this->setBaseProject();
        $this->createDirectories();
        /* $this->downloadFiles();
        $this->replaceFile("app/Helpers/global.php");
        $this->runCommand("php artisan install:api");
        $this->runCommand("composer require spatie/laravel-medialibrary");
        $this->replaceFile("routes/api.php");
        $this->modifyAppPHP();
        $this->installBugsnag();
        $this->addFilesToComposerJson();
        $this->printConclusionMessage(); */
    }
}

$setup = new Setup;
$setup->main();
