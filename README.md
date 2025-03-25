# Aastera Laravel Setup

Este repositório contém scripts para automatizar a configuração de ambientes Laravel. O objetivo é facilitar a execução do setup de projetos Laravel, realizando as configurações necessárias rapidamente.

## Arquivos Disponíveis

### 1. `aastera_laravel.ps1` (PowerShell)

Este arquivo é um script PowerShell para sistemas Windows. Ele realiza os seguintes passos:

- **Verifica se o PHP está instalado**: O script tenta executar o PHP e, se não for encontrado, exibe uma mensagem de erro.
- **Baixa o arquivo `setup.php` do repositório**: O script baixa o arquivo PHP de configuração diretamente do repositório no GitHub.
- **Executa o arquivo PHP**: Após o download, o arquivo PHP é executado no ambiente.
- **Exclui o arquivo temporário**: Após a execução, o arquivo temporário baixado é excluído.

### 2. `aastera_laravel.sh` (Bash)

Este arquivo é um script Bash para sistemas Unix (Linux e macOS). Ele realiza os mesmos passos que o script PowerShell, mas em um ambiente Bash. A única diferença está na sintaxe e nos comandos usados para realizar as mesmas tarefas.

## Como Usar

1. **Adicionando o Script ao PATH**

   Para facilitar o uso dos scripts de qualquer lugar, é sugerido adicionar o script `aastera_laravel` ao PATH da sua máquina.

   - No **Windows**:
     1. Copie o arquivo `aastera_laravel.ps1` para uma pasta, por exemplo, `C:\scripts\`.
     2. Adicione o caminho `C:\scripts\` ao PATH do sistema.
     3. Agora você pode executar o script de qualquer diretório via terminal PowerShell com o comando `aastera_laravel`.

   - No **Linux/macOS**:
     1. Copie o arquivo `aastera_laravel.sh` para uma pasta, por exemplo, `~/scripts/`.
     2. Adicione o caminho `~/scripts/` ao PATH, editando o arquivo `~/.bashrc` ou `~/.zshrc` (dependendo do shell utilizado):
        ```bash
        export PATH="$PATH:~/scripts"
        ```
     3. Depois de editar o arquivo, execute o comando:
        ```bash
        source ~/.bashrc  # ou source ~/.zshrc
        ```
     4. Agora você pode executar o script de qualquer diretório via terminal com o comando `aastera_laravel`.

2. **Executando o Script**

   - No **Windows** (PowerShell):
     - Abra o PowerShell e execute o comando:
       ```powershell
       aastera_laravel
       ```

   - No **Linux/macOS** (Bash):
     - Abra o terminal e execute o comando:
       ```bash
       aastera_laravel
       ```

   O script irá automaticamente verificar se o PHP está instalado, baixar o arquivo `setup.php` e executá-lo. Após a execução, o arquivo temporário será excluído.

## Pré-requisitos

- **PHP** deve estar instalado e acessível no PATH do sistema.
  
  - Para verificar se o PHP está instalado, execute o comando:
    ```bash
    php -v  # Linux/macOS
    ```

    ```powershell
    php -v  # Windows (PowerShell)
    ```

  Caso o PHP não esteja instalado, você pode [baixar e instalar o PHP](https://www.php.net/downloads.php).

## Problemas Comuns

- **PHP não encontrado**: Certifique-se de que o PHP está instalado e que seu executável (`php`) está acessível no PATH do sistema.
- **Erro ao baixar o arquivo do GitHub**: Verifique sua conexão com a internet. Se o GitHub estiver temporariamente fora do ar, o download pode falhar.

---

## Explicação do Script `setup.php`

O script `setup.php` é responsável por configurar e personalizar a instalação de um projeto Laravel. Ele realiza diversas tarefas automatizadas, como criar diretórios, baixar arquivos de um repositório GitHub e aplicar templates. Abaixo estão as principais ações do script:

### 1. **Verificar a instalação do Composer**
O script começa verificando se o Composer, a ferramenta de dependências do PHP, está instalado no sistema. Caso contrário, ele exibe uma mensagem de erro e termina a execução.

```php
$this->checkComposer();
```

### 2. **Escolher entre criar um novo projeto ou aplicar um template**
Após verificar o Composer, o script oferece duas opções para o usuário:
- **Opção 1:** Criar um novo projeto Laravel do zero.
- **Opção 2:** Aplicar um template Laravel em um diretório existente (se já houver uma instalação Laravel).

```php
$this->setBaseProject();
```

### 3. **Criar Diretórios Necessários**
O script cria vários diretórios dentro do projeto Laravel para organizar a estrutura de arquivos. Alguns dos diretórios criados incluem:
- `app/Models/Support`
- `app/Services/Support`
- `app/Http/Middleware`
- `app/ApiClients`
- `app/Console/Commands`
- `app/Helpers`
- `database/migrations`

```php
$this->createDirectories();
```

### 4. **Baixar Arquivos de Templates**
Após criar os diretórios, o script baixa arquivos específicos do repositório GitHub e os coloca nas pastas correspondentes. Ele utiliza a API do GitHub para listar os arquivos e obter as URLs de download.

```php
$this->downloadFiles();
```

### 5. **Substituir ou Criar Arquivos Específicos**
O script verifica e substitui arquivos existentes, como o arquivo `app/Helpers/global.php`, e também pode modificar o arquivo `routes/api.php` para aplicar ajustes personalizados.

```php
$this->replaceFile("app/Helpers/global.php");
$this->replaceFile("routes/api.php", true);
```

### 6. **Executar Comandos no Projeto Laravel**
O script executa vários comandos do Laravel para completar a configuração do projeto, como a instalação de pacotes e a execução de comandos artisan personalizados. Ele também instala a biblioteca `spatie/laravel-medialibrary` e configura o Bugsnag para monitoramento de erros.

```php
$this->runCommand("php artisan install:api");
$this->runCommand("composer require spatie/laravel-medialibrary");
$this->installBugsnag();
```

### 7. **Adicionar Arquivos ao `composer.json`**
O script também pode adicionar novos arquivos ao arquivo `composer.json` do projeto, garantindo que dependências adicionais sejam carregadas corretamente.

```php
$this->addFilesToComposerJson();
```

### 8. **Mensagem de Conclusão**
Após a execução de todas as etapas, o script exibe uma mensagem informando que a configuração foi concluída com sucesso.

```php
$this->printConclusionMessage();
```

### 9. **URLs e Configuração**
O script faz uso de URLs base para o repositório GitHub, onde os arquivos e templates necessários são armazenados. Ele também configura o diretório raiz onde o projeto será instalado.

```php
$this->setUrls();
```
