$phpExecutable = "php" 

# Verificar se o PHP está instalado
$phpVersion = & $phpExecutable -v
if (-not $phpVersion) {
    Write-Host "Erro: O PHP não está instalado ou não está acessível no PATH."
    exit 1
}

# URL do arquivo PHP no GitHub
$githubUrl = "https://raw.githubusercontent.com/AN-Tecnologia/laravel-setup/main/setup.php"

# Caminho do arquivo temporário onde o arquivo PHP será salvo
$phpTempFile = [System.IO.Path]::GetTempFileName() + ".php"

# Baixar o arquivo PHP do GitHub e salvar no arquivo temporário
try {
    Write-Host "Baixando o arquivo PHP do GitHub..."
    Invoke-WebRequest -Uri $githubUrl -OutFile $phpTempFile
    Write-Host "Arquivo 'setup.php' baixado com sucesso!"
} catch {
    Write-Host "Erro ao baixar o arquivo: $_"
    exit 1
}

# Executar o script PHP
Write-Host "Executando o código PHP..."
& $phpExecutable $phpTempFile

# Verificar se o script PHP foi executado corretamente
if ($LASTEXITCODE -eq 0) {
    Write-Host "O código PHP foi executado com sucesso!"
} else {
    Write-Host "Erro ao executar o código PHP."
}

# Excluir o arquivo temporário após execução
Write-Host "Excluindo o arquivo temporário..."
Remove-Item -Path $phpTempFile -Force
