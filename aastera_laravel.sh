#!/bin/bash

# Verificar se o PHP está instalado
phpExecutable="php"
phpVersion=$($phpExecutable -v)

if [ $? -ne 0 ]; then
    echo "Erro: O PHP não está instalado ou não está acessível no PATH."
    exit 1
fi

# URL do arquivo PHP no GitHub
githubUrl="https://raw.githubusercontent.com/AN-Tecnologia/laravel-setup/main/setup.php"

# Caminho do arquivo temporário onde o arquivo PHP será salvo
phpTempFile=$(mktemp /tmp/setup.XXXXXX.php)

# Baixar o arquivo PHP do GitHub e salvar no arquivo temporário
echo "Baixando o arquivo PHP do GitHub..."
curl -s -o "$phpTempFile" "$githubUrl"

if [ $? -ne 0 ]; then
    echo "Erro ao baixar o arquivo."
    exit 1
fi

echo "Arquivo 'setup.php' baixado com sucesso!"

# Executar o script PHP
echo "Executando o código PHP..."
$phpExecutable "$phpTempFile"

# Verificar se o script PHP foi executado corretamente
if [ $? -eq 0 ]; then
    echo "O código PHP foi executado com sucesso!"
else
    echo "Erro ao executar o código PHP."
fi

# Excluir o arquivo temporário após execução
echo "Excluindo o arquivo temporário..."
rm -f "$phpTempFile"
