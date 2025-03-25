<?php
class Setup
{
    public ?string $rootDir;
    public ?string $githubBaseUrl;
    public ?string $githubApiUrl;

    public function __construct()
    {
        $this->setUrls();
    }

    private function checkComposer(): void
    {
        $composerPath = null;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $composerPath = shell_exec('where composer');
        } else {
            $composerPath = shell_exec('which composer');
        }

        if (empty(trim($composerPath))) {
            echo "Erro: O Composer não está instalado ou não está disponível no PATH.\n";
            exit(1);
        }

        echo "Composer encontrado em: $composerPath\n";
    }

    private  function createDirectory(string $dirPath): void
    {
        $fullPath = $this->rootDir . DIRECTORY_SEPARATOR . $dirPath;

        if (!is_dir($fullPath)) {
            echo "Criando diretório $fullPath...\n";
            mkdir($fullPath, 0777, true);
        }
    }

    private function downloadAllFiles(string $subDir): void
    {
        $destDir = $this->getDestinationDirectory($subDir);
        $this->createDirectoryIfNotExists($destDir);
        $this->processDirectory($subDir, $destDir);
    }

    private function makeCurlRequest(string $url, ?string $token = null): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-cURL');

        $headers = [];
        if ($token) {
            $headers[] = "Authorization: token $token";
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            die("Erro ao acessar a API: HTTP $httpCode");
        }

        return json_decode($response, true);
    }

    private function listGithubDirectoryContents(string $subDir, ?string $token = null): array
    {
        $githubApiUrl = $this->githubApiUrl . '/' . $subDir;
        return $this->makeCurlRequest($githubApiUrl, $token);
    }

    private function getFilesUrls(array $directoryContents): array
    {
        $urls = [];
        foreach ($directoryContents as $content) {
            $urls[$content['name']] = $content['download_url'];
        }
        return $urls;
    }

    private function processDirectory(string $subDir, string $destDir): void
    {
        $directoryContents = $this->listGithubDirectoryContents($subDir);
        $files = $this->getFilesUrls($directoryContents);
        foreach ($files as $fileName => $fileUrl) {
            $this->downloadFile($fileName, $fileUrl, $destDir);
        }
    }

    private function downloadFile(string $fileName, string $fileUrl, string $destDir): void
    {
        echo "Baixando $fileUrl...\n";

        $fileContent = file_get_contents($fileUrl);
        $destinationPath = $destDir . '/' . $fileName;

        file_put_contents($destinationPath, $fileContent);
        echo "Arquivo salvo em:" .  $destinationPath . "\n";
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

    private function testDirectory(string $directoryPath): bool
    {
        if (!is_dir($directoryPath)) {
            echo "Diretório $directoryPath não encontrado.\n";
            return false;
        }
        return true;
    }

    private function createDirectoryAndFile(string $filePath, string $downloadUrl, bool $shouldForce = false): void
    {
        $fullPath = $this->rootDir . DIRECTORY_SEPARATOR . $filePath;
        $directoryPath = dirname($fullPath);
        $fileName = basename($fullPath);
        $directoryName = str_replace([$fileName, '/' . $fileName, '\\' . $fileName], '', $filePath);

        if (!$this->testDirectory($directoryPath)) {
            echo "Criando diretório $directoryPath...\n";
            $this->createDirectory($directoryName);
        }

        if (file_exists($fullPath)) {
            if ($shouldForce) {
                echo "Forçando substituição do arquivo $filePath...\n";
                unlink($fullPath);
                echo "Arquivo removido. Baixando novamente...\n";
            } else {
                echo "Arquivo $filePath já existe. Skipping...\n";
                return;
            }
        }

        echo "Baixando arquivo $filePath...\n";
        $this->downloadFile($fileName, $downloadUrl, $directoryPath);
    }

    private function replaceFile(string $filePath, bool $shouldForce = false): void
    {
        $downloadUrl = $this->githubBaseUrl . '/' . $filePath;
        $this->createDirectoryAndFile($filePath, $downloadUrl, $shouldForce);
    }

    private  function checkMiddlewareImports(string $content): bool
    {
        return (strpos($content, 'use App\\Http\\Middleware\\LogRequest;') !== false) &&
            (strpos($content, 'use App\\Http\\Middleware\\LogResponse;') !== false);
    }

    private  function addMiddlewareImports(string $filePath, string $content): void
    {
        echo 'Importações de middleware não encontradas. Adicionando...' . PHP_EOL;

        $importLines = PHP_EOL . 'use App\\Http\\Middleware\\LogRequest;' . PHP_EOL . 'use App\\Http\\Middleware\\LogResponse;' . PHP_EOL;

        $content = preg_replace('/<\?php/', "<?php" . $importLines, $content, 1);

        file_put_contents($filePath, $content);

        echo 'Importações de middleware adicionadas com sucesso.' . PHP_EOL;
    }

    private function addMiddlewareInWithMiddleware(string $filePath): void
    {
        echo "Lendo conteúdo do arquivo $filePath..." . PHP_EOL;

        $lines = $this->readBootstrapFileLines($filePath);
        $middlewareIndex = $this->findMiddlewareBlockIndex($lines);

        if ($middlewareIndex === null) {
            echo "[ERRO] Bloco 'withMiddleware' não encontrado no arquivo." . PHP_EOL;
            exit(1);
        }

        $insertIndex = $this->findMiddlewareInsertIndex($lines, $middlewareIndex);
        if ($insertIndex === null) {
            echo "[ERRO] Não foi possível encontrar o local correto para inserir os middlewares." . PHP_EOL;
            exit(1);
        }

        $lines = $this->insertMiddlewareInBootstrap($lines, $insertIndex);
        $this->saveModifiedBootstrapFile($filePath, $lines);
    }

    /**
     * Lê o conteúdo do arquivo `bootstrap/app.php` e retorna as linhas tratadas.
     */
    private function readBootstrapFileLines(string $filePath): array
    {
        $content = file_get_contents($filePath);
        return preg_split('/\r\n|\r|\n/', $content); // Suporte para diferentes sistemas operacionais
    }

    /**
     * Encontra a linha onde `withMiddleware` começa dentro do arquivo `bootstrap/app.php`.
     */
    private function findMiddlewareBlockIndex(array $lines): ?int
    {
        foreach ($lines as $i => $line) {
            if (preg_match('/->withMiddleware\s*\(\s*function\s*\(Middleware\s*\$middleware\s*\)\s*\{/', $line)) {
                return $i;
            }
        }
        return null;
    }

    /**
     * Encontra a posição correta para inserir os middlewares antes do fechamento `}` dentro do bloco `withMiddleware`.
     */
    private function findMiddlewareInsertIndex(array $lines, int $middlewareIndex): ?int
    {
        for ($i = $middlewareIndex + 1; $i < count($lines); $i++) {
            if (preg_match('/^\s*\}/', $lines[$i])) { // Encontrar a linha de fechamento `}`
                return $i;
            }
        }
        return null;
    }

    /**
     * Insere os middlewares dentro do bloco `withMiddleware` antes do fechamento `}`.
     */
    private function insertMiddlewareInBootstrap(array $lines, int $insertIndex): array
    {
        echo "Adicionando os middlewares..." . PHP_EOL;
        $middlewareString = "        \$middleware->append(LogRequest::class);\n        \$middleware->append(LogResponse::class);";

        array_splice($lines, $insertIndex, 0, $middlewareString);
        return $lines;
    }

    /**
     * Salva as alterações feitas no arquivo `bootstrap/app.php`.
     */
    private  function saveModifiedBootstrapFile(string $filePath, array $lines): void
    {
        file_put_contents($filePath, implode(PHP_EOL, $lines));
        echo "Alterações no arquivo 'bootstrap/app.php' feitas com sucesso." . PHP_EOL;
    }

    private function modifyAppPHP(): void
    {
        $filePath = $this->rootDir . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';
        echo "Modificando $filePath..." . PHP_EOL;

        $this->addMiddlewareInWithMiddleware($filePath);

        $content = file_get_contents($filePath);

        if ($this->checkMiddlewareImports($content)) {
            echo 'Importações de middleware já configuradas. Nenhuma alteração necessária.' . PHP_EOL;
        } else {
            $this->addMiddlewareImports($filePath, $content);
        }
    }

    private function runCommand(string $command): void
    {
        echo "Executando $command no diretório: $this->rootDir" . PHP_EOL;
        exec("cd {$this->rootDir} && echo n | $command");
    }

    private  function installBugsnag(): void
    {
        echo "Instalando Bugsnag..." . PHP_EOL;
        $this->runCommand("composer require bugsnag/bugsnag-laravel");
        $this->addBugsnagApiKeyToEnv();
        $this->addBugsnagLoggingChannel();
        $this->modifyLogStackVariable();
        $this->addBugsnagBootstrapper();
        $this->addBugsnagServiceProvider();
    }

    private  function addBugsnagApiKeyToEnv(): void
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

    private function addBugsnagBootstrapper(): void
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
        $pattern = '/use Illuminate\\\\Foundation\\\\Configuration\\\\Middleware;/';

        $replacement = 'use Illuminate\Foundation\Configuration\Middleware;' . PHP_EOL . PHP_EOL . '(new \Bugsnag\BugsnagLaravel\OomBootstrapper())->bootstrap();' . PHP_EOL;

        return preg_replace($pattern, $replacement, $content);
    }

    private function addBugsnagServiceProvider(): void
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
        $pattern = '/(App\\\\Providers\\\\AppServiceProvider::class,)/';
        $replacement = $providerLine . PHP_EOL . '$1';
        return preg_replace($pattern, $replacement, $content);
    }

    private  function modifyLogStackVariable(): void
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

    private function addBugsnagLoggingChannel(): void
    {
        echo "Adicionando canal Bugsnag ao arquivo de configuração de logging..." . PHP_EOL;
        $loggingConfigPath = $this->rootDir . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "logging.php";

        if (!$this->fileExists($loggingConfigPath)) {
            return;
        }

        if ($this->hasBugsnagChannel($loggingConfigPath)) {
            echo "Canal Bugsnag já está presente no arquivo de configuração de logging." . PHP_EOL;
            return;
        }

        $this->appendBugsnagChannel($loggingConfigPath);
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
        $content = file_get_contents($filePath);

        $pattern = "/'channels'\s*=>\s*\[\s*.*?'bugsnag'\s*=>\s*\[[^]]*\]/s";

        return preg_match($pattern, $content) === 1;
    }

    private function appendBugsnagChannel(string $filePath): void
    {
        echo "Lendo conteúdo do arquivo $filePath..." . PHP_EOL;

        $bugsnagChannel = "\n\n        'bugsnag' => [\n" .
            "            'driver' => 'bugsnag',\n" .
            "        ],\n";

        $content = file_get_contents($filePath);

        $pattern = '/(\s*\],\s*\n\s*\];)/m';

        if (preg_match($pattern, $content, $matches)) {
            echo "'channels' encontrado, adicionando Bugsnag..." . PHP_EOL;
            $updatedContent = preg_replace($pattern, $bugsnagChannel . '$1', $content, 1);
            file_put_contents($filePath, $updatedContent);
            echo "Bugsnag adicionado com sucesso!" . PHP_EOL;
        } else {
            echo "[ERRO] Não foi possível encontrar o fechamento correto do array 'channels'." . PHP_EOL;
        }
    }

    private function addFilesToComposerJson(): void
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

    private function handleFirstOption(): void
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

    private function handleSecondOption(): void
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
        $this->rootDir = "C:\\Users\\jmbor\\dev\\interno\\laravel-setup\\teste12"; //getcwd();
        $this->githubBaseUrl = "https://raw.githubusercontent.com/AN-Tecnologia/laravel-setup/main";
        $this->githubApiUrl = "https://api.github.com/repos/AN-Tecnologia/laravel-setup/contents";
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
        $this->downloadFiles();
        $this->replaceFile("app/Helpers/global.php");
        $this->runCommand("php artisan install:api");
        $this->runCommand("composer require spatie/laravel-medialibrary");
        $this->replaceFile("routes/api.php", true);
        $this->modifyAppPHP();
        $this->installBugsnag();
        $this->addFilesToComposerJson();
        $this->printConclusionMessage();
    }
}

$setup = new Setup;
$setup->main();
